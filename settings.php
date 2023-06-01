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
     * @copyright  2017 onward Appalachian State University
     * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
     */

    defined('MOODLE_INTERNAL') || die();

    // Use custom admin settings for IdP page (select) and list (textarea).
    require_once(__DIR__ . '/auth.php');
    require_once(__DIR__ . '/classes/admin_setting_special_wayf_select.php');
    require_once(__DIR__ . '/classes/admin_setting_special_idp_configtextarea.php');



    if ($ADMIN->fulltree) {

        $authplugin = get_auth_plugin('shibbuncif');

        // Introductory explanation.
        $settings->add(new admin_setting_heading(auth_plugin_shibbuncif::PLUGIN_NAME . '/pluginname', '',
            get_string('auth_shibbuncifdescription', auth_plugin_shibbuncif::PLUGIN_NAME)));

        // Username attribute (server variable).
        $settings->add(new admin_setting_configtext(auth_plugin_shibbuncif::PLUGIN_NAME . '/username_attr',
            get_string('username'),
            get_string('auth_shibbuncif_username_attr_desc', auth_plugin_shibbuncif::PLUGIN_NAME),
            auth_plugin_shibbuncif::DEFAULT_USERNAME_ATTR, PARAM_ALPHANUMEXT));

        // SP requires SSL.
        $settings->add(new admin_setting_configselect(auth_plugin_shibbuncif::PLUGIN_NAME . '/spssl',
            get_string('auth_shibbuncif_spssl', auth_plugin_shibbuncif::PLUGIN_NAME),
            get_string('auth_shibbuncif_spssl_desc', auth_plugin_shibbuncif::PLUGIN_NAME),
            'on', array('off' => get_string('no'), 'on' => get_string('yes'))));

        // SP login handler.
        $settings->add(new admin_setting_configtext(auth_plugin_shibbuncif::PLUGIN_NAME . '/login_handler',
            get_string('auth_shibbuncif_login_handler', auth_plugin_shibbuncif::PLUGIN_NAME),
            get_string('auth_shibbuncif_login_handler_desc', auth_plugin_shibbuncif::PLUGIN_NAME), '', PARAM_URL));

        // SP logout handler.
        $settings->add(new admin_setting_configtext(auth_plugin_shibbuncif::PLUGIN_NAME . '/logout_handler',
            get_string('auth_shibbuncif_logout_handler', auth_plugin_shibbuncif::PLUGIN_NAME),
            get_string('auth_shibbuncif_logout_handler_desc', auth_plugin_shibbuncif::PLUGIN_NAME), '', PARAM_URL));

        // Logout return URL.
        $settings->add(new admin_setting_configtext(auth_plugin_shibbuncif::PLUGIN_NAME . '/logout_return_url',
            get_string('auth_shibbuncif_logout_return_url', auth_plugin_shibbuncif::PLUGIN_NAME),
            get_string('auth_shibbuncif_logout_return_url_desc', auth_plugin_shibbuncif::PLUGIN_NAME), '', PARAM_URL));

        // IdP logout attribute (server variable).
        $settings->add(new admin_setting_configtext(auth_plugin_shibbuncif::PLUGIN_NAME . '/idp_logout_attr',
            get_string('auth_shibbuncif_idp_logout_attr', auth_plugin_shibbuncif::PLUGIN_NAME),
            get_string('auth_shibbuncif_idp_logout_attr_desc', auth_plugin_shibbuncif::PLUGIN_NAME),
            auth_plugin_shibbuncif::DEFAULT_IDP_LOGOUT_ATTR, PARAM_ALPHANUMEXT));

        // Display our WAYF page.
        $settings->add(new auth_shibbuncif_admin_setting_special_wayf_select(auth_plugin_shibbuncif::PLUGIN_NAME . '/wayf',
            get_string('auth_shibbuncif_wayf', auth_plugin_shibbuncif::PLUGIN_NAME),
            get_string('auth_shibbuncif_wayf_desc', auth_plugin_shibbuncif::PLUGIN_NAME),
            'on', array('off' => get_string('no'), 'on' => get_string('yes')),
            array('auth_plugin_shibbuncif', 'write_setting_hook')));

        // Display our forgot password link.
        $settings->add(new admin_setting_configcheckbox(auth_plugin_shibbuncif::PLUGIN_NAME . '/forgot_password_show',
            get_string('auth_shibbuncif_forgot_password_show', auth_plugin_shibbuncif::PLUGIN_NAME),
            get_string('auth_shibbuncif_forgot_password_show_desc', auth_plugin_shibbuncif::PLUGIN_NAME), "1"));

        // Set our forgot password link.
        $settings->add(new admin_setting_configtext(auth_plugin_shibbuncif::PLUGIN_NAME . '/forgot_password_url',
            get_string('auth_shibbuncif_forgot_password_url', auth_plugin_shibbuncif::PLUGIN_NAME),
            get_string('auth_shibbuncif_forgot_password_url_desc', auth_plugin_shibbuncif::PLUGIN_NAME), '', PARAM_URL));

        // WAYF IdP list.
        $settings->add(new auth_shibbuncif_admin_setting_special_idp_configtextarea(auth_plugin_shibbuncif::PLUGIN_NAME . '/wayf_idp_list',
            get_string('auth_shibbuncif_wayf_idp_list', auth_plugin_shibbuncif::PLUGIN_NAME),
            get_string('auth_shibbuncif_wayf_idp_list_desc', auth_plugin_shibbuncif::PLUGIN_NAME),
            '', PARAM_RAW, '60', '8', array('auth_plugin_shibbuncif', 'write_setting_hook')));

        // WAYF page heading.
        $settings->add(new admin_setting_configtext(auth_plugin_shibbuncif::PLUGIN_NAME . '/wayf_heading',
            get_string('auth_shibbuncif_wayf_heading', auth_plugin_shibbuncif::PLUGIN_NAME),
            get_string('auth_shibbuncif_wayf_heading_desc', auth_plugin_shibbuncif::PLUGIN_NAME), 'UNC Id Federation (Shibboleth)', PARAM_RAW_TRIMMED));

        // Display locking / mapping of profile fields.
        display_auth_lock_options($settings, $authplugin->authtype, $authplugin->userfields,
            '', true, false, array());

    }
