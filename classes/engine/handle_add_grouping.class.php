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
require_once($CFG->dirroot.'/local/moodlescript/classes/exceptions/execution_exception.class.php');

use \StdClass;
use \Exception;

class handle_add_grouping extends handler {

    protected $acceptedkeys = array('description', 'enrolmentkey');

    public function execute(&$results, &$stack) {
        global $DB, $CFG;

        $this->stack = $stack;
        $context = $this->stack->get_current_context();

        if ($context->groupingcourseid == 'current') {
            $context->groupingcourseid = $context->courseid;
        }
        if (!$course = $DB->get_record('course', array('id' => $context->groupingcourseid))) {
            throw new execution_exception('Runtime: Missing target course for grouping addition');
        }

        $grouping = new StdClass;
        $grouping->name = $context->groupingname;
        if (!empty($context->groupingidnumber)) {
            $grouping->idnumber = $context->groupingidnumber;
        }
        $grouping->courseid = $context->groupingcourseid;

        // Transpose params.
        if (!empty($context->params)) {
            foreach ($context->params as $key => $value) {
                if (in_array($key, $this->acceptedkeys)) {
                    $grouping->$key = $value;
                }
            }
        }

        try {
            $grouping->id = groups_create_grouping($grouping);
            $this->log("Grouping {$grouping->name} added in course {$context->groupingcourseid}");
            $result = $grouping->id;
            $results[] = $result;

            if (!$this->stack->is_context_frozen()) {
                $this->stack->update_current_context('groupingid', $grouping->id);
            }

            return $result;
        } catch (Exception $e) {
            debug_trace("Grouping creation error ".$e->get_message(), TRACE_DEBUG);
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

        if (empty($context->groupingname)) {
            $this->error('Grouping name is empty');
        }

        if (!empty($context->groupingidnumber) && empty($context->ifnotexists)) {
            if (groups_get_grouping_by_idnumber($context->coursegroupingid, $context->groupingidnumber)) {
                $this->error('Id number is used');
            }
        }

        if ($context->groupingcourseid != 'current') {
            if (!$course = $DB->get_record('course', array('id' => $context->groupingcourseid))) {
                $this->error('Missing target course for grouping addition');
            }
        }
    }
}