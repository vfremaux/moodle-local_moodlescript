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

use \context_course;
use \StdClass;

defined('MOODLE_INTERNAL') || die;

class handle_remove_block extends handler {

    public function execute(&$results, &$stack) {
        global $DB, $COURSE;

        $this->stack = $stack;
        $context = $this->stack->get_current_context();

        if ($context->blockcourseid == 'current') {
            if (isset($context->courseid)) {
                $parentcontext = context_course::instance($context->courseid);
                $context->blockcourseid = $context->courseid;
            } else {
                $parentcontext = context_course::instance($COURSE->id);
                $context->blockcourseid = $COURSE->id;
            }
        } else {
            $parentcontext = context_course::instance($context->blockcourseid);
        }

        if ($this->dynamiccheckstatus < 0) {
            $resolved = new StdClass;
            $this->dynamic_check($context, $stack, $resolved);
            if ($this->stack->has_errors()) {
                throw new execution_exception($this->stack->print_errors());
            }
        }

        $params = array('blockname' => $context->blockname, 'parentcontextid' => $parentcontext->id);
        $instances = $DB->get_records('block_instances', $params);
        if ($instances) {
            foreach ($instances as $instance) {
                blocks_delete_instance($instance);
            }
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

        if (empty($context->blockname)) {
            $this->error('empty blockname');
        }

        $block = $DB->get_record('block', array('name' => $context->blockname));
        if (empty($block)) {
            $this->error('Remove block : Block not installed');
        }
        if (!$block->visible) {
            $this->error('Remove block : Block not enabled');
        }

        if ($context->blockcourseid == 'current') {
            if (isset($context->courseid)) {
                $blockcourseid = $context->courseid;
            }
        } else {
            $blockcourseid = $context->blockcourseid;
        }

        if (!$this->is_runtime($context->blockcourseid)) {
            if (!$DB->get_record('course', array('id' => $blockcourseid))) {
                $this->error('Remove block : Target course '.$context->blockcourseid.' does not exist');
            }
        } else {
            $this->warn('Remove block : Course id is runtime and thus unchecked. It may fail on execution.');
        }
    }
}