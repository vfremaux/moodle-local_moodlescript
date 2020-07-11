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

class handle_move_course extends handler {

    public function execute(&$results, &$context, &$stack) {
        global $DB;

        $this->stack = $stack;

        if (!$this->is_runtime($context->movecourseid)) {
            if ($context->movecourseid == 'current') {
                $context->movecourseid = $context->courseid;
            }
        } else {
            $identifier = new parse_identifier('course', $this->logger);
            $context->movecourseid = $identifier->parse($context->movecourseid);
        }
        $course = $DB->get_record('course', array('id' => $context->movecourseid));

        // Update course cleanly, purging caches and making all required fixes everywhere.
        $updatedcourse = new \StdClass;
        $updatedcourse->id = $course->id;

        if ($this->is_runtime($context->coursecatid)) {
            // We admit the category might be resolved at runtime as result of a previous instruction.
            $identifier = new parse_identifier('course_categories', $this->logger);
            $context->coursecatid = $identifier->parse($context->coursecatid, '', 'runtime');
        }

        if (empty($context->coursecatid)) {
            if (!empty($context->config->ifexists)) {
                $this->log('Move course : Not moving course '.$course->id.' as category '.$context->coursecatid.' not found');
                return;
            } else {
                $this->error('Move Course Runtime : category '.$context->coursecatid.' not found');
                return;
            }
        }
        $updatedcourse->category = $context->coursecatid;
        $this->log('Move Course Runtime : Course '.$course->id.' to category '.$context->coursecatid);
        update_course($updatedcourse);

        return true;
    }

    public function check(&$context, &$stack) {
        global $DB;

        $this->stack = $stack;

        if ($context->movecourseid != 'current') {
            if (!$this->is_runtime($context->movecourseid)) {
                $context->movecourseid = $context->courseid;
                if (!$course = $DB->get_record('course', array('id' => $context->movecourseid))) {
                    $this->error('Move course : Target course does not exist');
                }
            }
        }

        if (empty($context->coursecatid)) {
            if (empty($context->options->ifexists)) {
                $this->error('Check Move Course : Missing or empty coursecat id');
            }
        }

        if (!$this->is_runtime($context->coursecatid) && empty($context->options->ifexists)) {
            if (!$DB->record_exists('course_categories', array('id' => $context->coursecatid))) {
                $this->error('Check Move Course : Target course category does not exist');
            }
        } else {
            if (empty($context->options->ifexists)) {
                $this->warn('Check Move Course : Course category id is runtime and thus unchecked. It may fail on execution.');
            } else {
                $this->warn('Check Move Course : Course category id is runtime and thus unchecked. It will not fail on execution.');
            }
        }
    }
}