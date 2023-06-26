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

require_once($CFG->dirroot.'/user/lib.php');

use \StdClass;
use \Exception;

class handle_suspend_user extends handler {

    public function execute(&$results, &$stack) {
        global $DB, $USER;

        $this->stack = $stack;
        $context = $this->stack->get_current_context();

        if ($context->suspenduserid == 'current') {
            if (isset($context->userid)) {
                $context->suspenduserid = $context->userid;
            } else {
                $context->suspenduserid = $USER->id;
            }
        }

        if (!$user = $DB->get_record('user', array('id' => $context->suspenduserid))) {
            $this->error("Suspend user : No existing user $context->suspenduserid ");
            return;
        }
        $user->suspended = 1;
        user_update_user($user, false, false);

        if (!$this->stack->is_context_frozen()) {
            $this->stack->update_current_context('userid', $user->id);
        }

        $results[] = $user->id;
        return $user->id;
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

        if (empty($context->suspenduserid)) {
            $this->error('Check Suspend User : No userid');
        }

        if (!$this->is_runtime($context->suspenduserid)) {
            if (empty($context->suspenduserid != 'current')) {
                $suspenduserid = $context->suspenduserid;
            } else {
                $suspenduserid = $context->userid;
            }

            if (!$user = $DB->get_record('user', array('id' => $context->suspenduserid))) {
                $this->error('Check Suspend User : No such user id '.$context->suspenduserid);
            }
        } else {
            $this->warn('Check Suspend User : User id is runtime and thus unchecked. It may fail on execution.');
        }
    }
}