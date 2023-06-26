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

    public function execute(&$results, &$stack) {
        global $DB, $USER;

        $this->stack = $stack;
        $context = $this->stack->get_current_context();

        if ($context->setuserid == 'current') {
            if (isset($context->userid)) {
                $context->setuserid = $context->userid;
            } else {
                $context->setuserid = $USER->id;
            }
        }

        if (!$user = $DB->get_record('user', ['id' => $context->setuserid])) {
            $this->error("Set profile value : Not existinct user {$context->setuserid} ");
            return;
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

        $results[] = $data->id;
        return $data->id;
    }

    /**
     * Remind that Check MUST NOT alter the context. Just execute any pre-execution tests that might 
     * be necessary.
     * @param $array &$stack the script stack.
     */
    public function check(&$stack) {
        global $DB, $CFG;

        $this->stack = $stack;
        $context = $this->stack->get_current_context();

        if (!$this->is_runtime($context->setuserid)) {
            if ($context->setuserid == 'current') {
                $setuserid = $context->userid;
            } else {
                $setuserid = $context->setuserid;
            }

            if (!$field = $DB->get_record('user', array('id' => $setuserid))) {
                $this->error('User {$context->setuserid} not found. '.print_r($e, true));
            }
        } else {
            $this->warn('Set profile value : User id is runtime and thus unchecked. It may fail on execution.');
        }

        if (!$field = $DB->get_record('user_info_field', array('id' => $context->profilefieldid))) {
            $this->error('Field $fieldid not found. '.print_r($e, true));
        }
    }
}