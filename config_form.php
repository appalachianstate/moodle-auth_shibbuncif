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



    // config_form() arguments: $frm, $err, $user_fields
    // globals: $CFG, $PAGE, $OUTPUT, $authplugin
    // $this: instance of auth_plugin_shibbuncif

    // Currently the IdP image requires SSL
    if (!isset($frm->spssl)) $frm->spssl = 'on';

    // Set defaults for Shibboleth server variable associated
    // configs if they are not already set
    if (!isset($frm->username_attr)) {
        $frm->username_attr = self::DEFAULT_USERNAME_ATTR;
    }
    if (!isset($frm->idp_logout_attr)) {
        $frm->idp_logout_attr = self::DEFAULT_IDP_LOGOUT_ATTR;
    }

    // Got to cheese it here because auth_config manages the fetching
    // and display of the userfields, and it uses the legacy style of
    // "auth/pluginname" in calls to get_config/set_config
    foreach($user_fields as $fieldname) {
        if (!isset($this->config->{"field_map_$fieldname"}) && array_key_exists($fieldname, self::$default_usermaps)) {
            set_config("field_map_$fieldname", self::$default_usermaps[$fieldname], self::PLUGIN_PATH);
        }
    }

    // The remainder are simple defaults
    if (!isset($frm->login_handler))       $frm->login_handler       = '';
    if (!isset($frm->logout_handler))      $frm->logout_handler      = '';
    if (!isset($frm->logout_return_url))   $frm->logout_return_url   = '';
    if (!isset($frm->wayf_idp_list))       $frm->wayf_idp_list       = '';
    if (!isset($frm->wayf_heading))        $frm->wayf_heading        = '';

?>

<table>

  <thead>
    <tr><th width="17%"></th><th width="28%"></th><th width="55%"></th></tr>
  </thead>

    <!-- username_attr -->
    <tr valign="top" class="required">
        <td align="right">
            <label for="username_attr"><?php echo get_string("auth_shib_username_attr", self::PLUGIN_NAME); ?>: </label>
        </td>
        <td>
            <input id="username_attr" class="form-control" name="username_attr" type="text" size="32" value="<?php echo $frm->username_attr; ?>"/>
            <?php if (isset($err['username_attr'])) echo "<br /><span class=\"notifyproblem\">{$OUTPUT->error_text($err['username_attr'])}</span>"; ?>
        </td>
        <td>
            <?php echo get_string("auth_shib_username_attr_desc", self::PLUGIN_NAME); ?>
        </td>
    </tr>


    <!-- spssl -->
    <tr valign="top">
        <td align="right">
            <label for="spssl"><?php echo get_string("auth_shib_spssl", self::PLUGIN_NAME); ?>: </label>
        </td>
        <td>
            <input id="spssl" class="form-control" name="spssl" type="checkbox"<?php echo (isset($frm->spssl) && $frm->spssl === 'on') ? ' checked' : ''; ?>/>
        </td>
        <td style="border-top: 1px solid #ccc;">
            <?php echo get_string("auth_shib_spssl_desc", self::PLUGIN_NAME); ?>
        </td>
    </tr>


    <!-- login_handler -->
    <tr valign="top">
        <td align="right">
            <label for="login_handler"><?php echo get_string("auth_shib_login_handler", self::PLUGIN_NAME); ?>: </label>
        </td>
        <td>
            <input id="login_handler" class="form-control" name="login_handler" type="text" size="32" value="<?php echo $frm->login_handler; ?>"/>
            <?php if (isset($err['login_handler'])) echo "<br /><span class=\"notifyproblem\">{$OUTPUT->error_text($err['login_handler'])}</span>"; ?>
        </td>
        <td style="border-top: 1px solid #ccc;">
            <?php echo get_string("auth_shib_login_handler_desc", self::PLUGIN_NAME); ?>
        </td>
    </tr>


    <!-- logout_handler -->
    <tr valign="top">
        <td align="right">
            <label for="logout_handler"><?php echo get_string("auth_shib_logout_handler", self::PLUGIN_NAME); ?>: </label>
        </td>
        <td>
            <input id="logout_handler" class="form-control" name="logout_handler" type="text" size="32" value="<?php echo $frm->logout_handler; ?>"/>
            <?php if (isset($err['logout_handler'])) echo "<br /><span class=\"notifyproblem\">{$OUTPUT->error_text($err['logout_handler'])}</span>"; ?>
        </td>
        <td style="border-top: 1px solid #ccc;">
            <?php echo get_string("auth_shib_logout_handler_desc", self::PLUGIN_NAME); ?>
        </td>
    </tr>


    <!-- logout_return_url -->
    <tr valign="top">
        <td align="right">
            <label for="logout_return_url"><?php echo get_string("auth_shib_logout_return_url", self::PLUGIN_NAME); ?>: </label>
        </td>
        <td>
            <input id="logout_return_url" class="form-control" name="logout_return_url" type="text" size="32" value="<?php echo $frm->logout_return_url; ?>"/>
            <?php if (isset($err['logout_return_url'])) echo "<br /><span class=\"notifyproblem\">{$OUTPUT->error_text($err['logout_return_url'])}</span>"; ?>
        </td>
        <td style="border-top: 1px solid #ccc;">
            <?php echo get_string("auth_shib_logout_return_url_desc", self::PLUGIN_NAME); ?>
        </td>
    </tr>


    <!-- idp_logout_attr -->
    <tr valign="top">
        <td align="right">
            <label for="idp_logout_attr"><?php echo get_string("auth_shib_idp_logout_attr", self::PLUGIN_NAME); ?>: </label>
        </td>
        <td>
            <input id="idp_logout_attr" class="form-control" name="idp_logout_attr" type="text" size="32" value="<?php echo $frm->idp_logout_attr; ?>"/>
            <?php if (isset($err['idp_logout_attr'])) echo "<br /><span class=\"notifyproblem\">{$OUTPUT->error_text($err['idp_logout_attr'])}</span>"; ?>
        </td>
        <td style="border-top: 1px solid #ccc;">
            <?php echo get_string("auth_shib_idp_logout_attr_desc", self::PLUGIN_NAME); ?>
        </td>
    </tr>


    <!-- wayf -->
    <tr valign="top">
        <td align="right">
            <label for="wayf"><?php echo get_string("auth_shib_wayf", self::PLUGIN_NAME); ?>: </label>
        </td>
        <td>
            <input id="wayf" class="form-control" name="wayf" type="checkbox"<?php echo (isset($frm->wayf) && $frm->wayf === 'on') ? ' checked' : ''; ?>/>
        </td>
        <td style="border-top: 1px solid #ccc;">
            <?php echo get_string("auth_shib_wayf_desc", self::PLUGIN_NAME); ?>
        </td>
    </tr>


    <!-- wayf_idp_list -->
    <tr valign="top">
        <td align="right">
            <label for="wayf_idp_list"><?php echo get_string("auth_shib_wayf_idp_list", self::PLUGIN_NAME); ?>: </label>
        </td>
        <td>
            <textarea id="wayf_idp_list" class="form-control" name="wayf_idp_list" rows="10" cols="30" style="overflow: auto;"><?php echo $frm->wayf_idp_list; ?></textarea>
            <?php if (isset($err['wayf_idp_list'])) echo "<br /><span class=\"notifyproblem\">{$OUTPUT->error_text($err['wayf_idp_list'])}</span>"; ?>
        </td>
        <td style="border-top: 1px solid #ccc;">
            <?php echo get_string("auth_shib_wayf_idp_list_desc", self::PLUGIN_NAME); ?>
        </td>
    </tr>


    <!-- wayf_heading -->
    <tr valign="top">
        <td align="right">
            <label for="wayf_heading"><?php echo get_string("auth_shib_wayf_heading", self::PLUGIN_NAME); ?>: </label>
        </td>
        <td>
            <input id="wayf_heading" class="form-control" name="wayf_heading" type="text" size="32" value="<?php echo $frm->wayf_heading; ?>"/>
            <?php if (isset($err['wayf_heading'])) echo "<br /><span class=\"notifyproblem\">{$OUTPUT->error_text($err['wayf_heading'])}</span>"; ?>
        </td>
        <td style="border-top: 1px solid #ccc;">
            <?php echo get_string("auth_shib_wayf_heading_desc", self::PLUGIN_NAME); ?>
        </td>
    </tr>

    <?php print_auth_lock_options($this->authtype, $user_fields, '', true, false); ?>

</table>
