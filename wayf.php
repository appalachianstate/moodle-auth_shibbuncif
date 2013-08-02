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

    require_once("../../config.php");
    require_once("./auth.php");



    // Redirect to frontpage, needed for loginhttps
    if (optional_param('cancel', 0, PARAM_BOOL)) {
        redirect(new moodle_url('/'));
    }

    //HTTPS is required in this page when $CFG->loginhttps enabled
    $PAGE->https_required();

    // If session marked as timed out, clear that
    unset($SESSION->has_timed_out);


    $errormsg  = '';
    // This may have been set by the main login.php page
    // before redirecting here.
    $errorcode = optional_param('errorcode', 0, PARAM_INT);
    switch ($errorcode) {
        case 1: // No cookies
            $errormsg = get_string("cookiesnotenabled");
            break;
        case 3: // User not set up by loginpage_hook()
            $errormsg = get_string("invalidlogin");
            break;
        case 4: // Session timed out, no attempt to reauth
            get_string('sessionerroruser', 'error');
            break;
    }


    // Check for a POSTed IdP, if found and it is valid
    // initiate the Shibboleth session by redirecting to
    // the appropriate IdP
    $idp_list     = auth_plugin_shibbuncif::get_wayf_idp_list();
    $selected_idp = optional_param('idp', '', PARAM_URL);

    if (!empty($selected_idp) && !$errorcode) {
        if (!array_key_exists($selected_idp, $idp_list)) {
            // Bad value, set error and re-display login form
            $errormsg = get_string('auth_shib_err_invalid_idp', auth_plugin_shibbuncif::PLUGIN_NAME);
        } else {
            // Persist the selection in the common domain cookie
            auth_plugin_shibbuncif::set_common_domain_cookie($selected_idp);
            // Send 'em on their way
            redirect(auth_plugin_shibbuncif::get_login_url($selected_idp));
        }
    }


    // For the internal login parts of the wayf_form
    $frm = new stdClass();
    $frm->username = get_moodle_cookie();
    $frm->password = '';

    $site = get_site();
    $loginsite = get_string("loginsite");

    $PAGE->set_url(auth_plugin_shibbuncif::get_wayf_url());
    $PAGE->set_context(context_system::instance());
    $PAGE->navbar->add($loginsite);
    $PAGE->set_title("$site->fullname: $loginsite");
    $PAGE->set_heading($site->fullname);
    $PAGE->set_pagelayout('login');
    $PAGE->verify_https_required();

    echo $OUTPUT->header();
    include("wayf_form.php");
    if ($errormsg) {
        $PAGE->requires->js_init_call('M.util.focus_login_error', null, true);
    } elseif (!empty($CFG->loginpageautofocus)) {
        $PAGE->requires->js_init_call('M.util.focus_login_form', null, true);
    }
    echo $OUTPUT->footer();
