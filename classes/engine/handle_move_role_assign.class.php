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

defined('MOODLE_INTERNAL') || die;

class handle_move_role_assign extends handler {

    public function execute(&$results, &$context, &$stack) {
        global $DB;

        $this->stack = $stack;

        if (!$this->is_runtime($context->movecourseid)) {
            if ($context->movecourseid == 'current') {
                $context->movecourseid = $context->courseid;
            }
        } else {
            $identifier = new parse_identifier('course', $this->logger);
            $context->movecourseid = $identifier->parse($context->movecourseid);
        }
        $course = $DB->get_record('course', array('id' => $context->movecourseid));
        if (!$course) {
            throw new execution_exception('Runtime: Target course does not exist');
        }
        $moodlecontext = context_course::instance($context->movecourseid);

        if ($context->scope == 'course') {

            $fromrole = $DB->get_record('role', array('id' => $context->fromrole));
            if ($targetusersassigns = get_users_from_role_on_context($fromrole, $moodlecontext)) {
                foreach ($targetusersassigns as $assign) {
                    // Make the unique.
                    $targetuserids[$assign->userid] = 1;
                }

                $targetuserids = array_keys($targetuserids);
                foreach ($targetuserids as $uid) {
                    role_unassign($fromrole->id, $uid, $moodlecontext->id);
                    role_assign($context->torole, $uid, $moodlecontext->id);
                }
            }

            $this->log('Move role assign : Moving assignments in '.$course->id.' from role '.$context->rolefrom.' to '.$context->roleto);
        } else {
            // Context is a single user.
            role_unassign($context->fromrole, $context->userid, $moodlecontext->id);
            role_assign($context->torole, $context->userid, $moodlecontext->id);
            $this->log('Move role assign : Moving assignment in '.$course->id.' from role '.$context->rolefrom.' to '.$context->roleto);
        }
        return true;
    }

    public function check(&$context, &$stack) {
        global $DB;

        $this->stack = $stack;

        if (!$this->is_runtime($context->movecourseid)) {
            if ($context->movecourseid != 'current') {
                if (!$course = $DB->get_record('course', array('id' => $context->movecourseid))) {
                    $this->error('Move role assign : Target course does not exist');
                }
            }
        } else {
            $this->warn('Move role assign : Course id is runtime and thus unchecked. It may fail on execution.');
        }

        if (empty($context->rolefrom)) {
            $this->error('Move role assign : Missing from role');
        }

        if (empty($context->roleto)) {
            $this->error('Move role assign : Missing dest role');
        }

    }
}