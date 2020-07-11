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

class handle_add_user extends handler {

    protected $acceptedkeys = array('firstname', 'lastname', 'email', 'confirmed', 'policyagreed', 'suspended', 'country', 'city', 'auth', 'emailstop',
                                'icq', 'skype', 'yahoo', 'msn', 'aim', 'phone2', 'phone2', 'institution', 'department', 'lang');

    public function execute(&$results, &$context, &$stack) {
        global $DB, $CFG;

        $this->stack = $stack;

        $user = new Stdclass;
        $user->username = $context->username;
        $user->password = @$context->password;

        // Transpose params.
        foreach ($context->params as $key => $value) {
            if (in_array($key, $this->acceptedkeys)) {
                $user->$key = $value;
            }
        }

        try {
            $user->id = user_create_user($user, true, false);
        } catch (Exception $e) {
            mtrace("User creation error ".$e->get_message());
            return null;
        }

        if (!$this->stack->is_context_frozen()) {
            $this->stack->update_current_context('userid', $user->id);
        }

        $results[] = $user->id;
        return $user->id;
    }

    public function check(&$context, &$stack) {
        global $DB, $CFG;

        $this->stack = $stack;

        if (empty($context->username)) {
            $this->error('Username is empty');
        }

        if (empty($context->params->firstname)) {
            $this->error('Firstname is empty');
        }

        if (empty($context->params->lastname)) {
            $this->error('Lastname is empty');
        }

        if (empty($context->params->email)) {
            $this->error('Email is empty');
        }
    }
}