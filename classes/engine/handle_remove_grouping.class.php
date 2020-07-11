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

    public function execute(&$results, &$context, &$stack) {
        global $DB, $CFG;

        $this->stack = $stack;

        if ($context->groupingcourseid == 'current') {
            $context->groupingcourseid = $context->courseid;
        }

        try {
            $grouping = $DB->get_record('groupings', array('id' => $context->groupinggroupid));
            groups_delete_grouping($context->groupinggroupid);
            $this->log("Grouping {$grouping->name} removed from course {$context->groupingcourseid}");
            $result = $grouping->id;
            $results[] = $result;
            return $result;
        } catch (Exception $e) {
            mtrace("Group deletion error ".$e->get_message());
        }

        return false;
    }

    public function check(&$context, &$stack) {
        global $DB, $CFG;

        $this->stack = $stack;

        if ($context->groupingcourseid == 'current') {
            $context->groupingcourseid = $context->courseid;
        }

        if (!$course = $DB->get_record('course', array('id' => $context->groupingcourseid))) {
            $this->error('Missing target course for group addition');
        }

    }
}