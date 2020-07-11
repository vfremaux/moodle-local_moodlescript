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

class handle_group_user extends handler {

    public function execute(&$results, &$context, &$stack) {
        global $DB, $USER;

        $this->stack = $stack;

        if ($context->groupuserid == 'current') {
            $context->groupuserid = $USER->id;
        }

        try {
            groups_add_member($context->groupgroupid, $context->groupuserid);
            $this->log("User {$context->groupuserid} added in group {$context->groupgroupid}");
            $params = array('userid' => $context->groupuserid, 'groupid' => $context->groupgroupid);
            $membershipid = $DB->get_field('groups_members', 'id', $params);

            $result = $membershipid;
            $results[] = $result;
            return $result;
        } catch (Exception $e) {
            mtrace("Group membership error ".$e->getMessage());
        }

        return false;
    }

    public function check(&$context, &$stack) {
        global $USER, $DB;

        $this->stack = $stack;

        if (empty($context->groupuserid)) {
            $this->error('Missing user id');
        }

        if ($context->groupuserid == 'current') {
            $context->groupuserid = $USER->id;
        }

        if (!$DB->record_exists('user', array('id' => $context->groupuserid))) {
            $this->error('Unknown user of ID '.$context->groupuserid);
        }

        if (empty($context->groupgroupid)) {
            $this->error('Missing group id');
        }

        if (!$DB->record_exists('groups', array('id' => $context->groupgroupid))) {
            $this->error('Unknown group of ID '.$context->groupgroupid);
        }
    }
}