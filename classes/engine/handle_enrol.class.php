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

use \StdClass;

require_once($CFG->dirroot.'/local/moodlescript/classes/exceptions/execution_exception.class.php');
require_once($CFG->dirroot.'/lib/enrollib.php');

defined('MOODLE_INTERNAL') || die;

class handle_enrol extends handler {

    public function execute(&$results, &$context, &$stack) {
        global $DB, $CFG;

        if ( function_exists('debug_trace')) {
            debug_trace("Enrol Handler input context");
            debug_trace($context);
        }

        $this->stack = $stack;

        if ($context->enrolcourseid == 'current') {
            $course = $DB->get_record('course', array('id' => $context->courseid));
        } else {
            $course = $DB->get_record('course', array('id' => $context->enrolcourseid));
        }
        if (!$course) {
            throw new execution_exception('Enrol Runtime : Target course does not exist');
        }

        if ($this->dynamiccheckstatus < 0) {
            $resolved = new StdClass;
            $resolved->course = $course;
            $this->dynamic_check($context, $stack, $resolved, true);
            // TODO:  process error case
        }

        $enrolplugin = enrol_get_plugin($context->method);
        $params = array('enrol' => $context->method, 'courseid' => $course->id, 'status' => ENROL_INSTANCE_ENABLED);
        $enrols = $DB->get_records('enrol', $params);
        $enrol = reset($enrols);

        $starttime = time();
        if (!empty($context->params->starttime)) {
            $starttime = $context->params->starttime;
        }

        $endtime = 0;
        if (!empty($context->params->endtime)) {
            $endtime = $context->params->endtime;
        }

        if (function_exists('debug_trace')) {
            debug_trace("Enrol handler - pre-enrol");
            debug_trace($enrolplugin);
        }

        if ($context->method == 'sync') {
            if (is_dir($CFG->dirroot.'/enrol/sync')) {
                include_once($CFG->dirroot.'/enrol/sync/lib.php');
                $result = \enrol_sync_plugin::static_enrol_user($course, $context->userid, $context->roleid, $starttime, $endtime);
                $this->log("User {$context->userid} enrolled in course {$course->id} using sync plugin");
            } else {
                $this->log("Missing sync plugin (not installed)");
            }
        } else {
            $enrolplugin->enrol_user($enrol, $context->userid, $context->roleid, $starttime, $endtime, ENROL_USER_ACTIVE);
            $this->log("User {$context->userid} enrolled in course {$course->id} using {$enrol->enrol} plugin");
            $params = array('userid' => $context->userid, 'enrolid' => $enrol->id);
            $result = $DB->get_field('user_enrolments', 'id', $params);
        }

        if (function_exists('debug_trace')) {
            debug_trace("Enrolled handler finished");
        }
        $results[] = $result;
    }

    public function check(&$context, &$stack) {
        global $DB;

        $this->stack = $stack;
        $this->context = $context;

        if (empty($context->enrolcourseid)) {
            $this->error('Enrol : Empty course id');
        }

        if ($context->enrolcourseid != 'current') {
            if (!$course = $DB->record_exists('course', array('id' => $context->enrolcourseid))) {
                $this->error('Enrol : Target course does not exist');
            }
        }

        if (!$role = $DB->record_exists('role', array('id' => $context->roleid))) {
            $this->error('Handle_enrol : Target role '.$context->roleid.' does not exist');
            $this->error('On : '.$this->statement);
        }

        if (empty($context->method)) {
            $this->error('Enrol : No enrol method');
        }

        if (empty($context->userid)) {
            $this->error('Enrol : Missing target user');
        }

    }

    public function dynamic_check(&$context, &$stack, $resolved, $isruntime = false) {
        global $DB;

        if (!empty($context->method)) {
            $enrolplugin = enrol_get_plugin($context->method);
            $params = array('enrol' => $context->method, 'courseid' => $resolved->course->id, 'status' => ENROL_INSTANCE_ENABLED);
            if (!$enrols = $DB->get_records('enrol', $params)) {
                if ($isruntime) {
                    throw new execution_exception('Enrol : No available '.$context->method.' enrol instances in course '.$resolved->course->id);
                } else {
                    $this->error('Enrol : No available '.$context->method.' enrol instances in course '.$resolved->course->id);
                }
            }
        }

        $this->dynamiccheckstatus = 0;
    }
}