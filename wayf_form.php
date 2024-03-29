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



    $config = get_config(auth_plugin_shibbuncif::PLUGIN_NAME);

    if (!empty($CFG->loginpasswordautocomplete)) {
        $autocomplete = 'autocomplete="off"';
    } else {
        $autocomplete = '';
    }

    // Forgot password for Shibb accounts.
    $forgotConfig = $config->forgot_password_url;
    if (!empty($forgotConfig)) {
        $forgotShib = $forgotConfig;
    } else {
        if (empty($CFG->forgottenpasswordurl)) {
            $forgotShib = $CFG->wwwroot . '/login/forgot_password.php';
        } else {
            $forgotShib = $CFG->forgottenpasswordurl;
        }
    }

    // Forgot password for manual accounts.
    if (empty($CFG->forgottenpasswordurl)) {
        $forgotMan = $CFG->wwwroot . '/login/forgot_password.php';
    } else {
        $forgotMan = $CFG->forgottenpasswordurl;
    }

?>

<div class="clearfix row">

    <div class="col-lg-6 card">
    
    	<div class="card-body">
    		
    		<h2><?php echo (!empty($config->wayf_heading) ? $config->wayf_heading : 'Login using the UNC Identity Federation'); ?></h2>

                <div class="subcontent">
        
                    <label for="idp"><?php echo get_string("auth_shibbuncif_wayf_instructions", auth_plugin_shibbuncif::PLUGIN_NAME); ?></label>
        
                    <form action="wayf.php" method="post" id="guestlogin">
        
                        <select id="idp" class="form-control" name="idp">
                        <option value="-"><?php echo get_string("auth_shibbuncif_wayf_select_prompt", auth_plugin_shibbuncif::PLUGIN_NAME); ?></option>
                        <?php
                        $preferred_idp = auth_plugin_shibbuncif::get_common_domain_cookie();
                        $selected_set = false;
                        foreach($idp_list as $idp_entity_id => $idp_values_array) {
                            $idp_label = array_shift($idp_values_array);
                            $selected_attr = '';
                            if (!$selected_set && $idp_entity_id === $preferred_idp) {
                                $selected_attr = ' selected';
                                $selected_set = true;
                            }
                            echo "<option value=\"{$idp_entity_id}\"{$selected_attr}>{$idp_label}</option>\n";
                        }
                        ?>
                        </select>
                        <br />
                        <p><input class="btn btn-secondary" type="submit" value="<?php echo get_string("select"); ?>" accesskey="s" /></p>
        
                    </form>
                    <?php if (!empty($config->forgot_password_show) &&$config->forgot_password_show):  ?>
                    <p class="forgetpass"><a href="<?php echo $forgotShib; ?>"><?php echo get_string("forgotaccount"); ?></a></p>
                    <?php endif; ?>

                    <p><?php echo get_string('auth_shibbuncif_contact_administrator', auth_plugin_shibbuncif::PLUGIN_NAME, get_admin()->email); ?></p>
        
                </div>
    		
    	</div>

    </div>


    <div class="col-lg-6">

        <?php if (($CFG->registerauth == 'email') || !empty($CFG->registerauth)): ?>
        <div class="skiplinks">
            <a class="skip" href="signup.php"><?php echo get_string("tocreatenewaccount"); ?></a>
        </div>
        <?php endif; ?>

        <h2><?php echo get_string('auth_shibbuncif_internal_login_heading', auth_plugin_shibbuncif::PLUGIN_NAME); ?></h2>

        <div class="subcontent loginsub">

            <div class="desc">
            <?php
            echo get_string("loginsite");
            echo '<br/>';
            echo '(' . get_string("cookiesenabled") . ')';
            echo $OUTPUT->help_icon('cookiesenabled');
            ?>
            </div>

            <?php if (!empty($errormsg)): ?>
                <div class="loginerrors">
                <a id="loginerrormessage" href="#" class="accesshide"><?php echo $errormsg; ?></a>
                <span class="notifyproblem"><?php echo $OUTPUT->error_text($errormsg); ?></span>
                </div>
            <?php endif; ?>

            <form action="<?php echo "{$CFG->wwwroot}/login/index.php"; ?>" method="post" id="login" <?php echo $autocomplete; ?>>

                <div class="loginform">
                    <div class="form-group">
                        <label for="username"><?php echo get_string("username"); ?></label>
                        <input type="text" class="form-control" name="username" id="username" size="15" value="<?php p($frm->username); ?>"/>
                    </div>
                    <div class="form-group">
                        <label for="password"><?php echo get_string("password"); ?></label>
                        <input class="form-control" type="password" name="password" id="password" size="15" value="" <?php echo $autocomplete; ?> />
                    </div>
                    <input class="btn btn-secondary" type="submit" id="loginbtn" value="<?php echo get_string("login"); ?>" />
                </div>
                <div class="clearer"><!-- --></div>

                <?php if (isset($CFG->rememberusername) and $CFG->rememberusername == 2) { ?>
                <div class="rememberpass">
                    <input type="checkbox" name="rememberusername" id="rememberusername" value="1" <?php echo (empty($frm->username) ? '' : 'checked'); ?> />
                    <label for="rememberusername"><?php echo get_string('rememberusername', 'admin'); ?></label>
                </div>
                <?php } ?>

                <div class="clearer"><!-- --></div>
                <div class="forgetpass"><a href="<?php echo $forgotMan; ?>"><?php echo get_string("forgotten"); ?></a></div>
                <input type="hidden" name="logintoken" value="<?php echo s(\core\session\manager::get_login_token()); ?>" />

            </form>

        </div>

        <?php if ($CFG->guestloginbutton and !isguestuser()):  ?>
        <div class="subcontent guestsub">
        <div class="desc">
          <?php echo get_string("someallowguest"); ?>
        </div>
        <form action="<?php echo "{$CFG->wwwroot}/login/index.php"; ?>" method="post" id="guestlogin">
          <div class="guestform">
            <input type="hidden" name="logintoken" value="<?php echo s(\core\session\manager::get_login_token()); ?>" />
            <input type="hidden" name="username" value="guest" />
            <input type="hidden" name="password" value="guest" />
            <input type="submit" class="btn btn-secondary" value="<?php echo get_string("loginguest"); ?>" />
          </div>
        </form>
        </div>
        <?php endif; ?>

    </div>

</div>
