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

class handle_group_group extends handler {

    public function execute(&$results, &$stack) {

        $this->stack = $stack;
        $context = $this->stack->get_current_context();

        try {
            groups_assign_grouping($context->groupgroupingid, $context->groupgroupid);
            $this->log("Group {$context->groupgroupid} added in grouping {$context->groupgroupingid}");
            return true;
        } catch (Exception $e) {
            $this->error("Grouping membership error ".$e->getMessage());
        }

        if (!$this->stack->is_context_frozen()) {
            $this->stack->update_current_context('groupid', $context->groupgroupid);
            $this->stack->update_current_context('groupingid', $context->groupgroupingid);
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

        if (empty($context->groupgroupid)) {
            $this->error('Missing group id');
        }

        if (!$DB->record_exists('groups', array('id' => $context->groupgroupid))) {
            $this->error('Unknown group of ID '.$context->groupgroupid);
        }

        if (empty($context->groupgroupingid)) {
            $this->error('Missing grouping id');
        }

        if (!$DB->record_exists('groupings', array('id' => $context->groupgroupingid))) {
            $this->error('Unknown grouping of ID '.$context->groupgroupingid);
        }
    }
}