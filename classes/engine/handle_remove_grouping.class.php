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

require_once($CFG->dirroot.'/group/lib.php');

use \StdClass;
use \Exception;

class handle_remove_grouping extends handler {

    public function execute(&$results, &$stack) {
        global $DB, $COURSE;

        $this->stack = $stack;

        if ($context->groupingcourseid == 'current') {
            if (isset($context->courseid)) {
                $context->groupingcourseid = $context->courseid;
            } else {
                $context->groupingcourseid = $COURSE->id;
            }
        }

        try {
            $grouping = $DB->get_record('groupings', array('id' => $context->groupinggroupid));
            groups_delete_grouping($context->groupinggroupid);
            $this->log("Grouping {$grouping->name} removed from course {$context->groupingcourseid}");
            $result = $grouping->id;
            $results[] = $result;
            return $result;
        } catch (Exception $e) {
            $this->error("Group deletion error ".$e->get_message());
        }

        return false;
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

        if (!$this->is_runtime($context->groupingcourseid)) {
            if ($context->groupingcourseid == 'current') {
                $groupingcourseid = $context->courseid;
            } else {
                $groupingcourseid = $context->groupingcourseid;
            }

            if (!$course = $DB->get_record('course', array('id' => $groupingcourseid))) {
                $this->error('Missing target course for group addition');
            }
        } else {
            $this->warn('Move role assign : Course id is runtime and thus unchecked. It may fail on execution.');
        }

    }
}