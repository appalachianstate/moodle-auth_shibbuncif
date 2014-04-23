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

    require_once '../../config.php';
    require_once './auth.php';

     $PAGE->set_url('/auth/shibbuncif/index.php');
     $PAGE->set_context(context_system::instance());

    /*
     * For an actively Shibboleth protected Moodle site, this is the
     * page that is protected, so the server variables injected into
     * the request by the Apache mod or ISAPI filter will be present
     * here (and not visible elsewhere in the site).
     */

    $plugin_configs = get_config(auth_plugin_shibbuncif::PLUGIN_NAME);

    // Must have the name of the server variable that contains the
    // authenticated username and that server variable must exist
    if (empty($plugin_configs->username_attr) || empty($_SERVER[$plugin_configs->username_attr])) {
        print_error('auth_shib_err_misconfigured', auth_plugin_shibbuncif::PLUGIN_NAME, null,  get_admin()->email);
    }

    // If the user (identified by Shibboleth) already logged in by
    // looking at session state, then send them off to either home
    // page or desired url, BUT VERIFY THE SHIBBOLETH USERNAME IS
    // THE SAME AS THE USERNAME STORED IN $SESSION.
    if (isloggedin() && !isguestuser()) {

        if ($USER->username != strtolower($_SERVER[$auth_plugin->config->username_attr])) {

            // Destroy old session
            require_logout();

        } else {

            // Already authenticated, send user on to standard home
            // page by default
            $urltogo = $CFG->wwwroot . '/';

            if (isset($SESSION->wantsurl) && (strpos($SESSION->wantsurl, $CFG->wwwroot) === 0)) {
                // If desired URL already indicated and it's an
                // address in this site, oblige
                $urltogo = $SESSION->wantsurl;
                unset($SESSION->wantsurl);
            }
            redirect($urltogo);

        }

    } // if (isloggedin() && !isguestuser())


    // Shibboleth appears to be configured and user not already logged
    // in. Associate Shibboleth session with user for SLO preparation
    $shibboleth_session_id = '';
    if (isset($_SERVER['Shib-Session-ID'])) {
        // This is only available for Shibboleth 2.x SPs
        $shibboleth_session_id = $_SERVER['Shib-Session-ID'];
    } else {
        // Try to find out using the user's cookie
        foreach ($_COOKIE as $cookie_name => $cookie_value) {
            if (preg_match('/_shibsession_/i', $cookie_name)) {
                $shibboleth_session_id = $cookie_value;
                break;
            }
        }
    }
    // Set Shibboleth session ID for logout
    $SESSION->shibboleth_session_id = $shibboleth_session_id;


    // If IdP logout URL is available put in $SESSION
    // for later use as it might not be visible later
    if (!empty($_SERVER[$plugin_configs->idp_logout_attr])) {
        $SESSION->shibboleth_idp_logout = $_SERVER[$plugin_configs->idp_logout_attr];
    }


    // The call to authenticate_user_login will either fetch the
    // existing user record or generate a new one if needed and
    // return it. Because our auth plugin indicates passwords not
    // stored locally, when new user created the password passed
    // here is discarded.
    if (false === ($user = authenticate_user_login(strtolower($_SERVER[$plugin_configs->username_attr]), '', true))) {
        // But if the Shibboleth user couldn't be mapped to a
        // valid Moodle user
        print_error('auth_shib_err_user_fail', auth_plugin_shibbuncif::PLUGIN_NAME);
    }

    // This will put $user into $_SESSION['USER'] to which the
    // global $USER is referenced
    session_set_user($user);

    $USER->loggedin = true;
    $USER->site     = $CFG->wwwroot;

    update_user_login_times();
    set_login_session_preferences();
    add_to_log(SITEID, 'user', 'login', "view.php?id={$USER->id}&amp;course=" . SITEID, $USER->id, 0, $USER->id);


    if (user_not_fully_set_up($USER)) {
        // We don't delete $SESSION->wantsurl yet, so we get there later
        $urltogo = "{$CFG->wwwroot}/user/edit.php?id={$USER->id}&amp;course=" . SITEID;
    } else if (isset($SESSION->wantsurl) && (strpos($SESSION->wantsurl, $CFG->wwwroot) === 0)) {
        $urltogo = $SESSION->wantsurl;
        unset($SESSION->wantsurl);
    } else {
        $urltogo = "{$CFG->wwwroot}/";
        unset($SESSION->wantsurl);
    }

    // If not a site admin and defaulthomepage enabled go to
    // my-moodle page instead of front page
    if (!empty($CFG->defaulthomepage) && $CFG->defaulthomepage == HOMEPAGE_MY && !isguestuser() && !has_capability('moodle/site:config', context_system::instance())) {
        if ($urltogo == $CFG->wwwroot || $urltogo == $CFG->wwwroot.'/' || $urltogo == $CFG->wwwroot.'/index.php') {
            $urltogo = $CFG->wwwroot.'/my/';
        }
    }

    redirect($urltogo);
