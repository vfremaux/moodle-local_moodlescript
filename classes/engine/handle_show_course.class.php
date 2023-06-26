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

class handle_show_course extends handler {

    /**
     * Hide a course.
     */
    public function execute(&$results, &$stack) {
        global $DB, $COURSE;

        $this->stack = $stack;
        $context = $this->stack->get_current_context();

        if ($context->showcourseid == 'current') {
            if (isset($context->courseid)) {
                $context->showcourseid = $context->courseid;
            } else {
                $context->showcourseid = $COURSE->id;
            }
        }

        if (!$course = $DB->get_record('course', array('id' => $context->showcourseid))) {
            $this->error("Show course : Not existing course $context->showcourseid ");
            return;
        }
        $DB->set_field('course', 'visible', true, ['id' => $context->showcourseid]);

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

        if (empty($context->showcourseid)) {
            $this->error('Check Show course : Empty course id');
        }

        if (!$this->is_runtime($context->showcourseid)) {
            if ($context->showcourseid == 'current') {
                $showcourseid = $context->courseid;
            } else {
                $showcourseid = $context->showcourseid;
            }

            if (!is_numeric($context->showcourseid)) {
                $this->error('Check Show course : Target course id is not a number');
            }

            if (!$course = $DB->get_record('course', array('id' => $context->showcourseid))) {
                $this->error('Check Show course : Target course does not exist');
            }
        } else {
            $this->warn('Check Show course : Course id is runtime and thus unchecked. It may fail on execution.');
        }
    }
}