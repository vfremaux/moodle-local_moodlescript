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

class handle_delete_user extends handler {

    public function execute($result, &$context, &$stack) {
        global $DB, $CFG;

        $this->stack = $stack;

        $user = $DB->get_record('user', array('id' => $context->userid));
        user_delete_user($user);

        $result[] = $user->id;
        return $result;
    }

    public function check(&$context, &$stack) {
        global $DB, $CFG;

        $this->stack = $stack;

        if (empty($context->userid)) {
            $this->error('No userid');
        }

        if (!$user = $DB->get_record('user', array('id' => $context->userid))) {
            $this->error('No such user id '.$context->userid);
        }

    }
}