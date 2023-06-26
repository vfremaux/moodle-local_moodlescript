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

    public function execute(&$results, &$stack) {
        global $DB, $COURSE;

        $this->stack = $stack;
        $context = $this->stack->get_current_context();

        if (!$this->is_runtime($context->movecourseid)) {
            if ($context->movecourseid == 'current') {
                if (isset($context->courseid)) {
                    $context->movecourseid = $context->courseid;
                } else {
                    $context->movecourseid = $COURSE->id;
                }
            }
        } else {
            $identifier = new parse_identifier('course', $this->logger);
            $context->movecourseid = $identifier->parse($context->movecourseid);
        }

        if (!$course = $DB->get_record('course', array('id' => $context->movecourseid))) {
            $this->error("Move course : Not existing course $context->movecourseid ");
            return;
        }

        // Update course cleanly, purging caches and making all required fixes everywhere.
        $updatedcourse = new \StdClass;
        $updatedcourse->id = $course->id;

        if ($this->is_runtime($context->coursecatid)) {
            // We admit the category might be resolved at runtime as result of a previous instruction.
            $identifier = new parse_identifier('course_categories', $this->logger);
            $context->coursecatid = $identifier->parse($context->coursecatid, '', 'runtime');
        }

        if (empty($context->coursecatid)) {
            if (!empty($context->options->ifexists)) {
                $this->log('Move course : Not moving course '.$course->id.' as category '.$context->coursecatid.' not found');
                return;
            } else {
                $this->error('Move course : category '.$context->coursecatid.' not found');
                return;
            }
        }
        $updatedcourse->category = $context->coursecatid;
        $this->log('Move course : Course '.$course->id.' to category '.$context->coursecatid);
        update_course($updatedcourse);

        if (!$this->stack->is_context_frozen()) {
            $this->stack->update_current_context('courseid', $course->id);
            $this->stack->update_current_context('coursecatid', $updatedcourse->category);
        }

        return true;
    }

    /**
     * Remind that Check MUST NOT alter the context. Just execute any pre-execution tests that might 
     * be necessary.
     * @param $array &$stack the script stack.
     */
    public function check(&$stack) {
        global $DB;

        $this->stack = $stack;
        $context = $this->stack->get_current_context();

        if ($context->movecourseid == 'current') {
            $movecourseid = $context->courseid;
        } else {
            $movecourseid = $context->movecourseid;            
        }

        if (!$course = $DB->get_record('course', array('id' => $movecourseid))) {
            $this->error('Move course : Target course does not exist');
        }

        if (empty($context->coursecatid)) {
            if (empty($context->options->ifexists)) {
                $this->error('Move course : Missing or empty coursecat id');
            }
        }

        if (!$this->is_runtime($context->coursecatid) && empty($context->options->ifexists)) {
            if (!$DB->record_exists('course_categories', array('id' => $context->coursecatid))) {
                $this->error('Move course : Target course category does not exist');
            }
        } else {
            if (empty($context->options->ifexists)) {
                $this->warn('Move course : Course category id is runtime and thus unchecked. It may fail on execution.');
            } else {
                $this->warn('Move course : Course category id is runtime and thus unchecked. It will not fail on execution.');
            }
        }
    }
}