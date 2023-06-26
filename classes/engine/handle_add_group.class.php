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

class handle_add_group extends handler {

    protected $acceptedkeys = array('description', 'enrolmentkey');

    public function execute(&$results, &$stack) {
        global $DB, $CFG;

        $this->stack = $stack;
        $context = $this->stack->get_current_context();

        if ($context->groupcourseid == 'current') {
            $context->groupcourseid = $context->courseid;
        }
        if (!$DB->record_exists('course', array('id' => $context->groupcourseid))) {
            throw new execution_exception('Runtime: Missing target course for group addition');
        }

        $group = new StdClass;
        $group->name = $context->groupname;
        if (!empty($context->groupidnumber)) {
            $group->idnumber = $context->groupidnumber;
        }
        $group->courseid = $context->groupcourseid;
        $group->timecreated = time();

        // Transpose params.
        if (!empty($context->params)) {
            foreach ($context->params as $key => $value) {
                if (in_array($key, $this->acceptedkeys)) {
                    $group->$key = $value;
                }
            }
        }

        try {
            $group->id = groups_create_group($group);
            $this->log("Group {$group->name} added in course {$context->groupcourseid}");
            $result = $group->id;
            $results[] = $result;

            if (!$this->stack->is_context_frozen()) {
                $this->stack->update_current_context('groupid', $group->id);
            }
            return $result;
        } catch (Exception $e) {
            $this->error("Group creation error ".$e->get_message());
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

        if (empty($context->groupname)) {
            $this->error('Group name is empty');
        }

        if (!empty($context->groupidnumber) && empty($context->ifnotexists)) {
            if (groups_get_group_by_idnumber($context->coursegroupid, $context->groupidnumber)) {
                $this->error('Id number is used');
            }
        }

        if ($context->groupcourseid != 'current') {
            if (!$course = $DB->get_record('course', array('id' => $context->groupcourseid))) {
                $this->error('Missing target course for group addition');
            }
        }
    }
}