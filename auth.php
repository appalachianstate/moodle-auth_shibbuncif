<?php

    // This file is part of Moodle - http://moodle.org/
    //
    // Moodle is free software: you can redistribute it and/or modify
    // it under the terms of the GNU General Public License as published by
    // the Free Software Foundation, either version 3 of the License, or
    // (at your option) any later version.
    //
    // Moodle is distributed in the hope that it will be useful,
    // but WITHOUT ANY WARRANTY; without even the implied warranty of
    // MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    // GNU General Public License for more details.
    //
    // You should have received a copy of the GNU General Public License
    // along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

    /**
     *  UNC Id Federation (Shibboleth) authentication plugin
     *
     * @package    auth_shibbuncif
     * @author     Fred Woolard (based on auth_shibboleth plugin)
     * @copyright  2013 onward Appalachian State University
     * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
     */

    defined('MOODLE_INTERNAL') || die();

    require_once($CFG->libdir.'/authlib.php');



    /**
     * UNC Federation Shibboleth authentication plugin.
     */
    class auth_plugin_shibbuncif extends auth_plugin_base
    {

        // Inherited member vars

        // The configuration details for the plugin.
        // @var object
        //
        // var $config;

        //
        // Authentication plugin type - the same as db field.
        // @var string
        //
        // var $authtype;

        //
        // The fields we can lock and update from/to external authentication backends
        // @var array
        //
        // var $userfields = array(
        //      'firstname', 'lastname', 'email',
        //      'city', 'country',
        //      'lang', 'description', 'url',
        //      'idnumber', 'institution', 'department',
        //      'phone1', 'phone2', 'address');

        /*
         * Class constants
         */

        const PLUGIN_NAME                           = 'auth_shibbuncif';

        const COMMON_DOMAIN_COOKIE_NAME             = '_saml_idp';

        const DEFAULT_USERNAME_ATTR                 = 'HTTP_SHIB_EP_PRINCIPALNAME';
        const DEFAULT_IDP_LOGOUT_ATTR               = 'HTTP_SHIB_LOGOUTURL';

        const DEFAULT_LOGIN_HANDLER                 = '/Shibboleth.sso/Login';
        const DEFAULT_LOGOUT_HANDLER                = '/Shibboleth.sso/Logout';



        /*
         * Class member vars
         */

        /**
         * @var array Default values to use for configuring userfield mapping.
         */
        private static $userfieldmaps               = array('firstname'   => 'HTTP_SHIB_INETORGPERSON_GIVENNAME',
                                                            'lastname'    => 'HTTP_SHIB_PERSON_SURNAME',
                                                            'email'       => 'HTTP_SHIB_INETORGPERSON_MAIL',
                                                            'idnumber'    => 'HTTP_SHIB_CAMPUSPERMANENTID');

        /**
         * Override
         * @see auth_plugin_base
         *
         */
        public $userfields                          = array('firstname', 'lastname', 'email', 'idnumber');



        /**
         * Constructor
         *
         */
        function __construct()
        {

            $this->authtype   = 'shibbuncif';
            $this->config     = get_config(self::PLUGIN_NAME);

        } // __construct



        /**
         * The URL of the Shibboleth protected resource (PHP file)
         *
         * @access public
         * @static
         * @return string
         * @uses $CFG
         */
        public static function get_protected_resource_url()
        {
            global $CFG;


            return "{$CFG->wwwroot}/auth/shibbuncif/index.php";

        } // get_protected_resource_url



        /**
         * The URL of the internal WAYF-Discovery page
         *
         * @access public
         * @static
         * @return string
         * @uses $CFG
         */
        public static function get_wayf_url()
        {
            global $CFG;

            return "{$CFG->wwwroot}/auth/shibbuncif/wayf.php";

        } // get_wayf_url



        /**
         * Override
         * @see auth_plugin_base::user_login()
         *
         * Returns true if a value for the authenticated userid is present in
         * the configured server variable and it matches the $username passed,
         * false otherwise.
         */
        public function user_login($username, $password)
        {

            $servervar = empty($this->config->username_attr)
                       ? self::DEFAULT_USERNAME_ATTR : $this->config->username_attr;

           return isset($_SERVER[$servervar])
               && strtolower($_SERVER[$servervar]) === strtolower($username);

        } // user_login



        /**
         * Override
         * @see auth_plugin_base::get_userinfo()
         *
         * Called from create_user_record() and update_user_record(),
         * can not assume that ALWAYS will have the server variables
         * available since they only appear when accessing protected
         * resources in active Shibboleth mode.
         */
        public function get_userinfo($username)
        {

            $attrmap         = $this->get_attributes();
            $result          = array();
            $search_attribs  = array();

            foreach ($attrmap as $key => $value) {

                // Check for presence of attribute and if not there
                // omit it from results array altogether
                if (!isset($_SERVER[$value])) {
                    continue;
                }

                // If multi-value attributes, just take the first
                $temparray = explode(';', $_SERVER[$value]);
                $result[$key] = trim(array_shift($temparray));
                unset($temparray);

                // Massage any data items
                switch ($key) {
                    case 'username' :
                        // Keep Shibboleth canonical user@domain username
                        $result[$key] = strtolower($result[$key]);
                        break;
                    case 'idnumber' :
                        // To match Banner/LMB, strip off domain
                        $temparray = explode('@', $result[$key]);
                        $result[$key] = array_shift($temparray);
                        break;
                }

            }

            return $result;

        } // get_userinfo



        /**
         * Returns array containg the configured attribute mappings
         * (server variables) between Moodle and Shibboleth.
         *
         * @access private
         * @return array
         */
        private function get_attributes()
        {

            $mapped_attrs = array(
                'username' => empty($this->config->username_attr)
                            ? self::DEFAULT_USERNAME_ATTR : $this->config->username_attr);

            foreach (array_keys(self::$userfieldmaps) as $fieldname) {
                $mapped_attrs[$fieldname] = empty($this->config->{"field_map_$fieldname"})
                                          ? self::$userfieldmaps[$fieldname]
                                          : $this->config->{"field_map_$fieldname"};
            }

            return $mapped_attrs;

        } // get_attributes



        /**
         * Override
         * @see auth_plugin_base::is_internal()
         */
        public function is_internal()
        {

            return false;

        } // is_internal



        /**
         * Override
         * @see auth_plugin_base::is_synchronised_with_external()
         *
         * We want to suppress default behavior so when executing
         * authenticate_login_user(), update_user_record() is not
         * called.
         */
        public function is_synchronised_with_external()
        {

            return false;

        } // is_synchronised_with_external



        /**
         * Override
         * @see auth_plugin_base::loginpage_hook()
         *
         * @uses $SESSION, $CFG
         */
        public function loginpage_hook()
        {
            global $SESSION, $CFG;


            // Setting this config prevents the setting of the username in the
            // Moodle cookie, but it also prevents the clearing of the cookie
            // if one happens to already be there.
            // $CFG->nolastloggedin = true;

            // Will get called from the login/index.php script before the login
            // page is shown; need to determine if conditions are such that the
            // user has been redirected here due to a require_login() on a page
            // after following a deep link. If that is the case, want to send
            // them to the protected resource to invoke the Shibboleth SP, so
            // determine based on:
            // * not interested in any POSTs to the login/index.php page,
            //   so it has to be a GET;
            // * if they're guesting or not logged in;
            // * and they've indicated a destination to which to go
            if ($_SERVER['REQUEST_METHOD'] === 'GET' && (isguestuser() || !isloggedin()) && !empty($SESSION->wantsurl)) {

                // If there a common domain cookie (preferred IdP) and it has
                // an IdP we know about, use it.
                $wayf_idp_list = self::get_wayf_idp_list();
                $preferred_idp = self::get_common_domain_cookie();

                if (!empty($preferred_idp) && !array_key_exists($preferred_idp, $wayf_idp_list)) {
                    $preferred_idp = null;
                }

                if (null != $preferred_idp) {
                    // Send the user to the Shibboleth login handler with a
                    // query string param indicating which IdP should be used
                    // and target to which the IdP should return the user
                    $redirect_url = auth_plugin_shibbuncif::get_login_url($preferred_idp);
                } else {
                    // Local user, so send them to the Shibboleth protected
                    // resource, and let the SP handle session initiation
                    $redirect_url = self::get_protected_resource_url();
                }

                redirect($redirect_url);

            }

        } // loginpage_hook



        /**
         * Override
         * @see auth_plugin_base::logoutpage_hook()
         *
         * @uses $SESSION, $USER, $redirect
         */
        public function logoutpage_hook()
        {
            global $SESSION, $USER, $redirect;


            // Only do this if logout handler is defined, and
            // the user is actually logged in via Shibboleth
            if (empty($SESSION->shibboleth_session_id) || $USER->auth !== $this->authtype) {
                return;
            }

            // This will take the user to the SP's logout handler
            // which is usually /Shibboleth.sso/Logout
            $logout_url = self::get_logout_url() . "?return=";

            // If the IdP provided a logout URL to clear the auth
            // ticket, then arrange for SP to send user-agent next
            if (!empty($SESSION->shibboleth_idp_logout)) {
                $logout_url .= "{$SESSION->shibboleth_idp_logout}?return_url=";
            }

            // Check for alternative logout return url
            if (!empty($this->config->logout_return_url)) {
                // Set temp_redirect to alternative return url
                $logout_url .= urlencode($this->config->logout_return_url);
            } else {
                // Use the global redirect value
                $logout_url .= urlencode($redirect);
            }

            // Overwrite redirect in order to send user to
            // Shibboleth logout page and return back
            $redirect = $logout_url;

        } // logoutpage_hook



        /**
         * Generate array of IdPs from settings
         *
         * @access public
         * @static
         * @return array Assoc. array of IdPs (URL => array(Label, optional SP session initiator URL)
         */
        public static function get_wayf_idp_list()
        {

            static $cached_value = null;


            // If cached value on hand return it.
            if ($cached_value != null) {
                return $cached_value;
            }

            // List of IdPs is stored one per line, each line containing
            // a comma separated list of values ordered: URL, label, and
            // an optional SP session initiator

            $idp_list = array();

            $wayf_idp_list = get_config(self::PLUGIN_NAME, 'wayf_idp_list');
            if (!$wayf_idp_list) {
                return $idp_list;
            }

            foreach (array_map('trim', explode("\n", $wayf_idp_list)) as $idp_line) {

                // Blank line
                if (empty($idp_line)) continue;

                $idp_parts = array_map('trim', explode(',', $idp_line));

                // Only an entityId present, no corresponding label
                if (count($idp_parts) <= 1) {
                    continue;
                }

                $entity_id = trim(array_shift($idp_parts));
                if (empty($entity_id)) {
                    continue;
                }

                $idp_list[$entity_id] = array();
                while (count($idp_parts)) {
                    $idp_list[$entity_id][] = trim(array_shift($idp_parts));
                }

            }
            $cached_value = $idp_list;

            return $idp_list;

        }  // get_wayf_idp_list



        /**
         * Validate the user-input list of IdPs
         *
         * @access public
         * @static
         * @param string    $idp_list
         * @return boolean
         */
        public static function valid_idp_list($idp_list)
        {

            // List of IdPs is stored one per line, each line containing
            // a comma separated list of values ordered: URL, label, and
            // an optional SP session initiator

            foreach (array_map('trim', explode("\n", $idp_list)) as $idp_line) {

                // Blank line
                if (empty($idp_line)) continue;

                $idp_parts = array_map('trim',explode(',', $idp_line));

                // Only an entityId present, no corresponding label
                if (count($idp_parts) <= 1) {
                    return false;
                }

                $entity_id = clean_param($idp_parts[0], PARAM_URL);
                if (empty($entity_id)) {
                    return false;
                }

                $entity_label = trim(clean_param($idp_parts[1], PARAM_TEXT));
                if (empty($entity_label)) {
                    return false;
                }

                if (isset($idp_parts[2])) {
                    $entity_sessinit = clean_param($idp_parts[2], PARAM_URL);
                    if (empty($entity_sessinit)) {
                        return false;
                    }
                }

            } // foreach

            return true;

        } // valid_idp_list



        /**
         * Sets the standard SAML common domain cookie that is also used to preselect an entry on the local WAYF
         *
         * @access public
         * @static
         * @param string    $idp        IdP entityId to set in the cookie value
         * @return void
         */
        public static function set_common_domain_cookie($idp)
        {

            list($host, $path) = self::split_wwwroot();
            setcookie(self::COMMON_DOMAIN_COOKIE_NAME, base64_encode($idp), time() + (100*24*3600), $path);

        } // set_common_domain_cookie



        /**
         * Generates array of IdP entityId values from the common domain cookie value
         *
         * @access public
         * @static
         * @return array
         */
        public static function get_common_domain_cookie()
        {

            if (!isset($_COOKIE[self::COMMON_DOMAIN_COOKIE_NAME]) || empty($_COOKIE[self::COMMON_DOMAIN_COOKIE_NAME])) {
                return null;
            }

            $cookie_array = array_map('base64_decode', explode(' ', $_COOKIE[self::COMMON_DOMAIN_COOKIE_NAME]));
            return array_pop($cookie_array);

        } // get_common_domain_cookie



        /**
         * Fix up a session initiator (login) URL for the specified Identity Provider
         *
         * @access public
         * @static
         * @param string  $idp   The selected IdP, from the WAYF list, if null only the login handler URL is returned
         * @return string
         */
        public static function get_login_url($idp = null)
        {
            global $CFG;


            $idp_list = self::get_wayf_idp_list();
            $config   = get_config(self::PLUGIN_NAME);

            if (!empty($idp) && !empty($idp_list[$idp][1])) {

                // Session initiator configured for this selection
                $url = $idp_list[$idp][1] . '?entityID=' . urlencode($idp) . '&target=' . urlencode(auth_plugin_shibbuncif::get_protected_resource_url());

            } else {

                // No session initiator URL configured, use our SP
                // login handler, assuming <SSO> shorthand configs,
                // unless login handler overriden

                if (empty($config->login_handler)) {
                    $url = self::DEFAULT_LOGIN_HANDLER;
                    if (isset($config->spssl) && $config->spssl === 'on') {
                        $url = 'https://' . get_host_from_url($CFG->wwwroot) . $url;
                    }
                } else {
                    $url = $config->login_handler;
                }

                if (!empty($idp)) {
                    $url .= "?entityID=" . urlencode($idp) . "&target=" . urlencode(auth_plugin_shibbuncif::get_protected_resource_url());
                }

            }

            return $url;

        } // get_login_url



        /**
         * Fix up a logout handler URL
         *
         * @access public
         * @static
         * @return string    The SP logout handler, adjusted for SSL if needed
         */
        public static function get_logout_url()
        {
            global $CFG;


            $config = get_config(self::PLUGIN_NAME);

            if (empty($config->logout_handler)) {
                $url = self::DEFAULT_LOGOUT_HANDLER;
                if (isset($config->spssl) && $config->spssl === 'on') {
                    $url = 'https://' . get_host_from_url($CFG->wwwroot) . $url;
                }
            } else {
                $url = $config->logout_handler;
            }

            return $url;

        } // get_logout_url



        /**
         * Parse the $CFG->wwwroot into the hostname and application root path
         *
         * @access public
         * @static
         * @return array    The hostname (with port if present), and application root path
         */
        public static function split_wwwroot()
        {
            global $CFG;
            static $result = null;


            if (null != $result) {
                return $result;
            }

            $result = array();
            if (preg_match('/^(?:https?:\/\/)?([a-z\d][a-z\d-]+(?:\.[a-z\d][a-z\d-]+)*(?::\d{1,5})?)(\/[a-z0-9_+%\\/\.-]*)?\/?$/i', $CFG->wwwroot, $result)) {
                array_shift($result);
            }
            if (!isset($result[1])) {
                $result[] = '/';
            }

            return $result;

        } // split_wwwroot



        public static function write_setting_hook(admin_setting $settinginstance, &$arraywrappeddata)
        {

            switch ($settinginstance->name)
            {

                case 'wayf':
                    return self::write_setting_wayf($arraywrappeddata[0]);
                    break;
                case 'wayf_idp_list':
                    return self::write_setting_wayf_idp_list($arraywrappeddata[0]);
                    break;
                default:
                    return false;

            }

        }



        private static function write_setting_wayf(&$data)
        {
            global $CFG;


            if (isset($data) && $data === 'on') {

                set_config('alternateloginurl', self::get_wayf_url());

            } else {

                // Not set, empty, or anything not 'on' same as off
                $data = 'off';

                // If WAYF was enabled and now off, reset (clear) the
                // Moodle alternate login URL.
                $oldsetting = get_config(self::PLUGIN_NAME, 'wayf');
                if (isset($oldsetting) && $oldsetting === 'on') {
                    set_config('alternateloginurl', '');
                }

            }

            return true;

        }



        private static function write_setting_wayf_idp_list(&$data)
        {

            if (empty($data)) {
                $wayf = get_config(self::PLUGIN_NAME, 'wayf');
                if ($wayf === 'on') {
                    return get_string('auth_shibbuncif_err_wayf_idp_list_empty', self::PLUGIN_NAME);
                }
            } elseif (!self::valid_idp_list($data)) {
                return get_string('auth_shibbuncif_err_wayf_idp_list_invalid', self::PLUGIN_NAME);
            }

            return true;

        }


    } // auth_plugin_shibbuncif
