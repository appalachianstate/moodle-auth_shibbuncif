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

    require_once (__DIR__ . '/../../config.php');
    require_once (__DIR__ . '/auth.php');


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
    // authenticated username and that server variable must exist.
    $usernameservervar = empty($plugin_configs->username_attr)
        ? auth_plugin_shibbuncif::DEFAULT_USERNAME_ATTR : $plugin_configs->username_attr;

    if (empty($_SERVER[$usernameservervar])) {
        throw new \moodle_exception('auth_shibbuncif_err_misconfigured', auth_plugin_shibbuncif::PLUGIN_NAME, null, get_admin()->email);
    }

    // Support for WAYFless URLs.
    $target = optional_param('target', '', PARAM_LOCALURL);
    if (!empty($target)) {
        $SESSION->wantsurl = $target;
    }

    // If the user (identified by Shibboleth) already logged in by
    // looking at session state, then send them off to either home
    // page or desired url, BUT VERIFY THE SHIBBOLETH USERNAME IS
    // THE SAME AS THE USERNAME STORED IN $SESSION.
    if (isloggedin() && !isguestuser()) {

        if ($USER->username !== strtolower($_SERVER[$usernameservervar])) {

            // Destroy old session
            require_logout();

        } else {

            // Already authenticated, send user on to standard home
            // page by default, unless destination otherwise indicated
            $gotourl = $CFG->wwwroot . '/';

            if (isset($SESSION->wantsurl) && (strpos($SESSION->wantsurl, $CFG->wwwroot) === 0)) {
                // If desired URL already indicated and it's an
                // address in this site, oblige
                $gotourl = $SESSION->wantsurl;
                unset($SESSION->wantsurl);
            }
            redirect($gotourl);

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
    $logoutservervar = empty($plugin_configs->idp_logout_attr)
        ? auth_plugin_shibbuncif::DEFAULT_IDP_LOGOUT_ATTR : $plugin_configs->idp_logout_attr;

    if (!empty($_SERVER[$logoutservervar])) {
        $SESSION->shibboleth_idp_logout = $_SERVER[$logoutservervar];
    }


    // The call to authenticate_user_login will either fetch the
    // existing user record or generate a new one if needed and
    // return it. Because our auth plugin indicates passwords not
    // stored locally, when new user created the password passed
    // here is discarded.
    if (false === ($user = authenticate_user_login(strtolower($_SERVER[$usernameservervar]), '', true))) {
        // But if the Shibboleth user couldn't be mapped to a
        // valid Moodle user
        throw new \moodle_exception('auth_shibbuncif_err_user_fail', auth_plugin_shibbuncif::PLUGIN_NAME);
    }

    // This will put $user into $_SESSION['USER'] to which the
    // global $USER is referenced
    complete_user_login($user);

    if (user_not_fully_set_up($USER)) {
        // We don't delete $SESSION->wantsurl yet, so we get there later
        $gotourl = "{$CFG->wwwroot}/user/edit.php?id={$USER->id}&amp;course=" . SITEID;
    } else if (isset($SESSION->wantsurl) && (strpos($SESSION->wantsurl, $CFG->wwwroot) === 0)) {
        $gotourl = $SESSION->wantsurl;
        unset($SESSION->wantsurl);
    } else {
        $gotourl = "{$CFG->wwwroot}/";
        unset($SESSION->wantsurl);
    }

    // If not a site admin and defaulthomepage enabled go to
    // my-moodle page instead of front page
    if (!empty($CFG->defaulthomepage) && $CFG->defaulthomepage == HOMEPAGE_MY && !isguestuser() && !has_capability('moodle/site:config', context_system::instance())) {
        if ($gotourl == $CFG->wwwroot || $gotourl == $CFG->wwwroot . '/' || $gotourl == $CFG->wwwroot . '/index.php') {
            $gotourl = $CFG->wwwroot . '/my/';
        }
    }

    redirect($gotourl);
