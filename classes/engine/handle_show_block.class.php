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

class handle_show_block extends handler {

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

        $parentcontext = \context_course::instance($context->showcourseid);

        $params = array('blockname' => $context->blockname, 'parentcontextid' => $parentcontext->id);
        $blockinstances = $DB->get_records('block_instances', $params);
        foreach ($blockinstances as $bi) {
            $params = array('blockinstanceid' => $bi->id, 'visible' => 0);
            $hiddenpositions = $DB->get_records('block_positions', $params);
            if ($hiddenpositions) {
                foreach ($hiddenpositions as $pos) {
                    $pos->visible = 1;
                    $DB->update_record('block_positions', $pos);
                }
            }
        }
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

        if (empty($context->blockname)) {
            $this->error('Check Show Block : Empty block name');
        }

        $block = $DB->get_record('block', array('name' => $context->blockname));
        if (empty($block)) {
            $this->error('Check Show Block : Block is not installed');
        } else {
            if ($block->visible) {
                $this->error('Check Show Block : Block is not enabled');
            }
        }

        if (empty($context->showcourseid)) {
            $this->error('Check Show Block : Empty course id');
        }

        if (!$this->is_runtime($context->showcourseid)) {
            if ($context->showcourseid != 'current') {
                $showcourseid = $context->courseid;
            } else {
                $showcourseid = $context->showcourseid;
            }

            if (!$course = $DB->get_record('course', array('id' => $showcourseid))) {
                $this->error('Check Show Block : Missing target course for block insertion');
            }
        } else {
            $this->warn('check Show block : Course id is runtime and thus unchecked. It may fail on execution.');
        }

        if (!is_numeric($context->showcourseid)) {
            $this->error('Check Show Block : Target course id is not a number');
        }
    }
}