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

class handle_enrol extends handler {

    public function execute($result, &$context, &$stack) {
        global $DB;

        $this->stack = &$stack;

        if ($context->enrolcourseid == 'current') {
            $course = $DB->get_record('course', array('id' => $context->courseid));
        } else {
            $course = $DB->get_record('course', array('id' => $context->enrolcourseid));
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
            $starttime = $context->params->endtime;
        }

        if ($context->method == 'sync') {
            \enrol_sync_plugin::static_enrol_user($course, $context->userid, $context->roleid, $starttime, $endtime);
            $this->log("User {$context->userid} enrolled in course {$course->id} using sync plugin");
        } else {
            $enrolplugin->enrol_user($enrol, $context->userid, $context->roleid, $starttime, $endtime, ENROL_USER_ACTIVE);
            $this->log("User {$context->userid} enrolled in course {$course->id} using {$enrol->enrol} plugin");
        }
    }

    public function check(&$context, &$stack) {
        global $DB;

        $this->stack = &$stack;

        if (empty($context->enrolcourseid)) {
            $this->error('Empty course id');
        }

        if ($context->enrolcourseid == 'current') {
            $context->enrolcourseid = $context->courseid;
        }

        if (!$course = $DB->record_exists('course', array('id' => $context->enrolcourseid))) {
            $this->error('Target course does not exist');
        }

        if (!$role = $DB->record_exists('role', array('id' => $context->roleid))) {
            $this->error('Target role does not exist');
        }

        if (empty($context->method)) {
            $this->error('No enrol method');
        }

        if (empty($context->userid)) {
            $this->error('Missing target user');
        }

        if (!empty($course) && !empty($context->method)) {
            $course = $DB->get_record('course', array('id' => $context->enrolcourseid));
            $enrolplugin = enrol_get_plugin($context->method);
            $params = array('enrol' => $context->method, 'courseid' => $course->id, 'status' => ENROL_INSTANCE_ENABLED);
            if (!$enrols = $DB->get_records('enrol', $params)) {
                $this->error('No available '.$context->method.' enrol instances in course '.$course->id);
            }
        }
    }
}