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
     * @author     Fred Woolard (based on auth_shibboleth plugin {@link http://moodle.org})
     * @copyright  2013 Appalachian State University
     * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
     */

    defined('MOODLE_INTERNAL') || die();



    $string['pluginname']                            = 'UNC Id Federation (Shibboleth)';
    $string['auth_shibbuncifdescription']            = 'Users are authenticated and created using the <a href="http://federation.northcarolina.edu/">University of North Carolina Identity Federation</a>. Be sure to read the Shibboleth <a href="../auth/shibboleth/README.txt">README</a> file for information on configuring Moodle to use Shibboleth.';


    // Configuration page
    $string['auth_shib_username_attr']               = 'Username attribute';
    $string['auth_shib_username_attr_desc']          = 'Server variable name that contains the authenticated username.';
    $string['auth_shib_spssl']                       = 'SP requires SSL';
    $string['auth_shib_spssl_desc']                  = 'When checked, redirects to the Login and Logout handlers will be done using https.';
    $string['auth_shib_login_handler']               = 'SP login handler';
    $string['auth_shib_login_handler_desc']          = 'An absolute or relative URL for the Service Provider\'s login handler. If left blank, <em><b>/Shibboleth.sso/Login</b></em> will be used.';
    $string['auth_shib_logout_handler']              = 'SP logout handler';
    $string['auth_shib_logout_handler_desc']         = 'An absolute or relative URL for the Service Provider\'s logout handler. If left blank, <em><b>/Shibboleth.sso/Logout</b></em> will be used.';
    $string['auth_shib_logout_return_url']           = 'Alt. logout return';
    $string['auth_shib_logout_return_url_desc']      = 'URL to which Shibboleth users will be redirected after logout. If blank, users will be redirected to the location Moodle would normally use.';
    $string['auth_shib_idp_logout_attr']             = 'IdP Logout Attribute';
    $string['auth_shib_idp_logout_attr_desc']        = 'Server variable name that contains the Shibboleth IdP logout URL';
    $string['auth_shib_wayf']                        = 'WAYF page';
    $string['auth_shib_wayf_desc']                   = 'When checked, Moodle will display a WAYF (discovery) page with a drop-down list of campus Identity Providers from which the user can make a selection.';
    $string['auth_shib_wayf_idp_list']               = 'WAYF IdP list';
    $string['auth_shib_wayf_idp_list_desc']          = 'List of Identity Providers from which the user can select on the WAYF (discovery) page.<br /><br />Each line should be a comma-separated tuple for the Identity Provider containing the entityID URL (see the Shibboleth metadata file), the name as it will be displayed in the drop-down list, and an optional third field with a session initiator (URL) to use for that Idenity Provider.<br /><br />Blank lines are allowed for readability';
    $string['auth_shib_wayf_heading']                = 'WAYF page heading';
    $string['auth_shib_wayf_heading_desc']           = 'Heading for the WAYF (discovery) page e.g. <em><b>UNC Identity Federation</b></em>.';


    // Configuration errors
    $string['auth_shib_err_misconfigured']           = 'Shibboleth is not configured correctly. The username attribute server variable is not found. Please contact the <a href="mailto:{$a}">site administrator</a>.';
    $string['auth_shib_err_user_fail']               = 'Unable to either find an account that matches your authenticated username, or create a new account for it.';


    // Validation errors
    $string['auth_shib_err_username_attr_empty']     = 'Username attribute is required.';
    $string['auth_shib_err_wayf_idp_list_empty']     = 'IdP list required when WAYF is on.';
    $string['auth_shib_err_wayf_idp_list_invalid']   = 'IdP list is incorrectly formatted.';
    $string['auth_shib_err_invalid_idp']             = 'Invalid IdP selection.';


    // WAYF page items
    $string['auth_shib_contact_administrator']       = 'If you are not affiliated with any of the listed campuses and you need access to a course on this site, please contact the <a href="mailto:{$a}">site administrator</a>.';
    $string['auth_shib_wayf_select_prompt']          = 'I\'m affiliated with ...';
    $string['auth_shib_wayf_instructions']           = 'To authenticate using UNC Id Federation please select the campus with which you are affiliated:';
    $string['internal_login_heading']                = 'Login using Moodle authentication (non-SSO)';
