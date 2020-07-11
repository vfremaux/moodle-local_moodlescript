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

require_once($CFG->dirroot.'/local/moodlescript/classes/exceptions/execution_exception.class.php');

defined('MOODLE_INTERNAL') || die;

class handle_remove_enrol_method extends handler {

    public function execute(&$results, &$context, &$stack) {

        $this->stack = $stack;

        if ($context->enrolcourseid == 'current') {
            $context->enrolcourseid = $context->courseid;
        }

        if ($this->dynamiccheckstatus < 0) {
            $this->dynamic_check($context, $stack, array());
            if ($this->stack->has_errors()) {
                throw new execution_exception($this->stack->print_errors());
            }
        }

        $plugin = enrol_get_plugin($context->method);
        $enabledinstances = enrol_get_instances($context->enrolcourseid, true);
        $todelete = array();
        if ($enabledinstances) {
            foreach ($enabledinstances as $enrolinstance) {
                if ($enrolinstance->enrol == $context->method) {
                    $todelete[] = $enrolinstanc;
                }
            }
        }
        if (!empty($todelete)) {
            $i = 0;
            foreach ($todelete as $td) {
                $plugin->delete_instance($td);
                $i++;
            }
            $this->log("Deleted $i instances of {$context->method} enrol instances");
        } else {
            $this->log('Nothing done');
        }

        return true;
    }

    public function check(&$context, &$stack) {
        global $DB;

        $this->stack = $stack;
        $this->context = $context;

        if (empty($context->method)) {
            $this->error('Remove enrol method : Missing enrol method for deletion');
        }

        if ($context->enrolcourseid != 'current') {
            if (!$DB->record_exists('course', array('id' => $context->enrolcourseid))) {
                $this->error('Remove Enrol method : Target course does not exist');
            }
        }
    }

    public function dynamic_check(&$context, &$stack, $resolved) {
        global $DB;

        if (!$DB->record_exists('course', array('id' => $context->enrolcourseid))) {
            $this->error('Remove enrol method : Target for current course '.$context->enrolcourseid.' does not exist');
            return false;
        }

        $this->dynamiccheckstatus = 0;
    }
}