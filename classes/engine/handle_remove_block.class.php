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

class handle_remove_block extends handler {

    public function execute($result, &$context, &$stack) {
        global $DB;

        $this->stack = &$stack;

        if ($context->blockcourseid == 'current') {
            $parentcontext = \context_course::instance($context->courseid);
        } else {
            $parentcontext = \context_course::instance($context->blockcourseid);
        }

        $params = array('blockname' => $context->blockname, 'parentcontextid' => $parentcontext->id);
        $instances = $DB->get_records('block_instances', $params);
        if ($instances) {
            foreach ($instances as $instance) {
                blocks_delete_instance($instance);
            }
        }
    }

    public function check(&$context, &$stack) {
        global $DB;

        $this->stack = &$stack;

        if (empty($context->blockname)) {
            $this->error('empty blockname');
            $block = $DB->get_record('blocks', array('name' => $context->blockname));
            if (empty($block)) {
                $this->error('block not installed');
            }
            if (!$block->visible) {
                $this->error('block not enabled');
            }
        }

        if (empty($context->blockcourseid) && $context->blockcourseid != 'current') {
            if (!$DB->get_record('course', array('id' => $context->blockcourseid))) {
                $this->error('Target course '.$context->blockcourseid.' does not exist');
            }
        }
    }

}