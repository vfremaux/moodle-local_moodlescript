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

class handle_remove_group extends handler {

    public function execute(&$results, &$stack) {
        global $DB, $COURSE;

        $this->stack = $stack;
        $context = $this->stack->get_current_context();

        if ($context->groupcourseid == 'current') {
            if (isset($contex->courseid)) {
                $context->groupcourseid = $context->courseid;
            } else {
                $context->groupcourseid = $COURSE->id;
            }
        }

        try {
            $group = $DB->get_record('groups', array('id' => $context->groupgroupid));
            groups_delete_group($context->groupgroupid);
            $this->log("Group {$group->name} removed from course {$context->groupcourseid}");
            $result = $group->id;
            $results[] = $result;
            return $result;
        } catch (Exception $e) {
            $this->error("Group deletion error ".$e->getMessage());
        }

        return false;
    }

    /**
     * Pre-checks executability conditions (static).
     * Must NOT modify context.
     */
    public function check(&$stack) {
        global $DB;

        $this->stack = $stack;
        $context = $this->stack->get_current_context();

        if (!$this->is_runtime($context->groupcourseid)) {
            if ($context->groupcourseid == 'current') {
                $groupcourseid = $context->courseid;
            } else {
                $groupcourseid = $context->groupcourseid;
            }

            if (!$course = $DB->get_record('course', array('id' => $groupcourseid))) {
                $this->error('Missing target course for group addition');
            }
        } else {
            $this->warn('Remove group : Course id is runtime and thus unchecked. It may fail on execution.');
        }

    }
}