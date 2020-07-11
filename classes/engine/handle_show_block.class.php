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

    public function execute(&$results, &$context, &$stack) {
        global $DB;

        $this->stack = $stack;

        if ($context->showcourseid == 'current') {
            $context->showcourseid = $context->courseid;
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

    public function check(&$context, &$stack) {
        global $DB;

        $this->stack = $stack;

        if (empty($context->blockname)) {
            $this->error('Empty block name');
        }

        $block = $DB->get_record('block', array('name' => $context->blockname));
        if (empty($block)) {
            $this->error('Block is not installed');
        } else {
            if ($block->visible) {
                $this->error('Block is not enabled');
            }
        }

        if (empty($context->showcourseid)) {
            $this->error('Empty course id');
        }

        if ($context->showcourseid != 'current') {
            if (!$course = $DB->get_record('course', array('id' => $context->showcourseid))) {
                $this->error('Missing target course for block insertion');
            }
        }

        if (!is_numeric($context->showcourseid)) {
            $this->error('Target course id is not a number');
        }
    }
}