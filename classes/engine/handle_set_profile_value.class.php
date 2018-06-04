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
 * @package local_moodlescript
 * @category local
 * @author Valery Fremaux (valery.fremaux@gmail.com)
 * @copyright (c) 2017 onwards Valery Fremaux (http://www.mylearningfactory.com)
 */
namespace local_moodlescript\engine;

defined('MOODLE_INTERNAL') || die;

class handle_set_profile_value extends handler {

    public function execute($result, &$context, &$stack) {
        global $DB, $USER;

        $this->stack = $stack;

        if ($context->setuserid == 'current') {
            $context->setuserid = $context->userid;
        }

        $data = $DB->get_record('user_info_data', array('fieldid' => $context->fieldid, 'userid' => $context->setuserid));
        if ($data) {
            $data->data = $context->profilefieldvalue;
            $DB->update_record('user_info_data', $data);
        } else {
            $data = new StdClass;
            $data->fieldid = $context->profilefieldid;
            $data->userid = $context->setuserid;
            $data->data = $context->profilefieldvalue;
            $data->id = $DB->insert_record('user_info_data', $data);
        }

        $result[] = $data->id;
        return $result;
    }

    public function check(&$context, &$stack) {
        global $DB, $CFG;

        $this->stack = $stack;

        if ($context->setuserid == 'current') {
            $context->setuserid = $context->userid;
        }

        if (!$field = $DB->get_record('user', array('id' => $context->setuserid))) {
            $this->error('User {$context->setuserid} not found. '.print_r($e, true));
            return;
        }

        if (!$field = $DB->get_record('user_info_field', array('id' => $context->profilefieldid))) {
            $this->error('Field $fieldid not found. '.print_r($e, true));
            return;
        }
    }
}