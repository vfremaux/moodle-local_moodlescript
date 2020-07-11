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

class handle_suspend_user extends handler {

    public function execute(&$results, &$context, &$stack) {
        global $DB, $CFG;

        $this->stack = $stack;

        if ($context->suspenduserid == 'current') {
            $context->suspenduserid = $context->userid;
        }

        if ($this->is_runtime($context->suspenduserid)) {
            $identifier = new parse_identifier('user', $this);
            $context->suspenduserid = $identifier->parse($context->suspenduserid, 'idnumber', 'runtime');
        }

        $user = $DB->get_record('user', array('id' => $context->suspenduserid));
        $user->suspended = 1;
        user_update_user($user, false, false);

        $results[] = $user->id;
        return $user->id;
    }

    public function check(&$context, &$stack) {
        global $DB, $CFG;

        $this->stack = $stack;

        if (empty($context->suspenduserid)) {
            $this->error('No userid');
        }

        if (empty($context->suspenduserid != 'current')) {
            if (!$this->is_runtime($context->suspenduserid)) {
                if (!$user = $DB->get_record('user', array('id' => $context->suspenduserid))) {
                    $this->error('No such user id '.$context->suspenduserid);
                }
            }
        }
    }
}