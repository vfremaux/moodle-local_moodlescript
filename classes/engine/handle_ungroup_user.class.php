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

use \StdClass;
use \Exception;

class handle_ungroup_user extends handler {

    public function execute(&$results, &$stack) {
        global $USER;

        $this->stack = $stack;
        $context = $this->stack->get_current_context();

        if ($context->groupuserid == 'current') {
            if (isset($context->userid)) {
                $context->groupuserid = $context->userid;
            } else {
                $context->groupuserid = $USER->id;
            }
        }

        try {
            groups_remove_member($context->groupgroupid, $context->groupuserid);
            return true;
        } catch (Exception $e) {
            $this->error("Group membership error ".$e->getMessage());
            return;
        }

        return false;
    }

    /**
     * Remind that Check MUST NOT alter the context. Just execute any pre-execution tests that might 
     * be necessary.
     * @param $array &$stack the script stack.
     */
    public function check(&$stack) {
        global $USER;

        $this->stack = $stack;
        $context = $this->stack->get_current_context();

        if (empty($context->groupuserid)) {
            $this->error('Missing user id');
        }

        if (!$this->is_runtime($context->groupuserid)) {
            if ($context->groupuserid == 'current') {
                if (isset($context->userid)) {
                    $groupuserid = $context->userid;
                } else {
                    $groupuserid = $USER->id;
                }
            } else {
                $groupuserid = $context->groupuserid;
            }

            if (!$DB->record_exists('user', array('id' => $groupuserid))) {
                $this->error('Unkonwn user of ID '.$groupuserid);
            }
        } else {
            $this->warn('Check Ungroup User : User id is runtime and thus unchecked. It may fail on execution.');
        }
    }
}