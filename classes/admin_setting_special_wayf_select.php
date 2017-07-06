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



    class auth_shibbuncif_admin_setting_special_wayf_select extends admin_setting_configselect
    {


        private $write_setting_hook;


        /**
         * Calls parent::__construct with specific arguments.
         */
        public function __construct($name, $visiblename, $description, $defaultsetting, array $choices, $write_setting_hook = null)
        {

            parent::__construct($name, $visiblename, $description, $defaultsetting, $choices);

            if ($write_setting_hook != null) {
                $this->write_setting_hook = $write_setting_hook;
            }

        }

        /**
         * We need to overwrite the global "alternate login url" setting if
         * wayf is enabled.
         *
         * @param  string $data Form data.
         * @return string       Empty string or error message string
         */
        public function write_setting($data)
        {
            global $CFG;


            // If user selects our WAYF to be used then overwrite Moodle's
            // alternative login URL so it points to our WAYF page.

            if ($this->write_setting_hook == null) {
                return parent::write_setting($data);
            } else {
                $hooksuccess = call_user_func($this->write_setting_hook, $this, array(&$data));
                if ($hooksuccess) {
                    return parent::write_setting($data);
                } else {
                    return false;
                }
            }

        }

    }
