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
     * Special setting for auth_shibbuncif WAYF. Altogether lifted
     * from the auth_shibboleth plugin, with some changes so it is
     * not quite so specialized to one field, and will use a user
     * specified callback for overriding the write_setting method
     *
     * @package    auth_shibbuncif
     * @author     Fred Woolard (based on auth_shibboleth plugin)
     * @copyright  2017 onward Appalachian State University
     * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
     */

    defined('MOODLE_INTERNAL') || die();



    class auth_shibbuncif_admin_setting_special_idp_configtextarea extends admin_setting_configtextarea
    {


        private $write_setting_hook = null;


        /**
         * Calls parent::__construct with specific arguments.
         */
        public function __construct($name, $visiblename, $description, $defaultsetting, $paramtype = PARAM_RAW, $cols = '60', $rows = '8', $write_setting_hook = null)
        {

            parent::__construct($name, $visiblename, $description, $defaultsetting, $paramtype, $cols, $rows);

            if ($write_setting_hook != null) {
                $this->write_setting_hook = $write_setting_hook;
            }

        }

        /**
         * Clean up and format the input text into comma delimited fields,
         * expect two fields per line, with optional third field.
         *
         * @param string    $data Form data.
         * @return mixed    False if bad input, void otherwise.
         */
        public function write_setting($data)
        {
            global $CFG;


            // Custom validation before writing the config data

            if ($this->write_setting_hook == null) {
                return parent::write_setting($data);
            } else {
                $result = call_user_func($this->write_setting_hook, $this, array(&$data));
                if ($result === true) {
                    return parent::write_setting($data);
                } else {
                    return $result;
                }
            }

        }

    }
