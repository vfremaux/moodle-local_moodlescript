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

class handle_unenrol extends handler {

    public function execute(&$results, &$context, &$stack) {
        global $DB, $USER;

        $this->stack = $stack;

        if ($context->unenroluserid == 'current') {
            $context->unenroluserid = $USER->id;
        }

        if ($context->unenrolcourseid == 'current') {
            $context->unenrolcourseid = $context->courseid;
        }

        if (empty($context->params)) {
            $context->params = new \StdClass;
        }

        if (empty($context->params->enrol)) {
            $context->params->enrol = 'manual';
        }

        $course = $DB->get_record('course', array('id' => $context->unenrolcourseid));

        if ($context->params->enrol != 'sync') {
            $params = array('courseid' => $context->unenrolcourseid, 'enrol' => $context->params->enrol, 'status' => 0);
            $enrols = $DB->get_records('enrol', $params);
            $enrol = array_shift($enrols);
            $enrolplugin = enrol_get_plugin($context->params->enrol);
            $enrolplugin->unenrol_user($enrol, $context->unenroluserid);
        } else {
            include_once($CFG->dirroot.'/enrol/sync/lib.php');
            \enrol_sync_plugin::static_unenrol_user($course, $context->unenroluserid);
        }
    }

    public function check(&$context, &$stack) {
        global $DB, $USER;

        $this->stack = $stack;

        if (empty($context->unenroluserid)) {
            $this->error('Empty user id');
        }

        if ($context->unenroluserid == 'current') {
            $context->unenroluserid = $USER->id;
        }

        if (!$DB->get_record('user', array('id' => $context->unenroluserid))) {
            $this->error('Missing user');
        }

        if (empty($context->unenrolcourseid)) {
            $this->error('Empty course id');
        }

        if ($context->unenrolcourseid == 'current') {
            $context->unenrolcourseid = $context->courseid;
        }

        if (empty($context->method)) {
            $this->error('No enrol method');
        }
    }
}