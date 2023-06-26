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

class handle_hide_course extends handler {

    /**
     * Hide a course.
     */
    public function execute(&$results, &$stack) {
        global $DB, $COURSE;

        $this->stack = $stack;
        $context = $this->stack->get_current_context();

        if ($context->hidecourseid == 'current') {
            if (isset($context->courseid)) {
                $context->hidecourseid = $context->courseid;
            } else {
                $context->hidecourseid = $COURSE->id;
            }
        }

        if (!$course = $DB->get_record('course', array('id' => $context->hidecourseid))) {
            $this->error("Hide course : Not existing course $context->hidecourseid");
            return;
        }
        $DB->set_field('course', 'visible', false, ['id' => $context->hidecourseid]);
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

        if (empty($context->hidecourseid)) {
            $this->error('Empty course id');
        }

        if ($context->hidecourseid == 'current') {
            $context->hidecourseid = $context->courseid;
        }

        if (!is_numeric($context->hidecourseid)) {
            $this->error('Hide course : Target course id is not a number');
        }

        if (!$course = $DB->get_record('course', array('id' => $context->hidecourseid))) {
            $this->error('Hide course : Target course does not exist');
        }
    }
}