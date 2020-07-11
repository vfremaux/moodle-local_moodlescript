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

class handle_ungroup_group extends handler {

    public function execute(&$results, &$context, &$stack) {

        $this->stack = $stack;

        try {
            groups_unassign_grouping($context->groupgroupingid, $context->groupgroupid);
        } catch (Exception $e) {
            mtrace("Grouping membership error ".$e->get_message());
        }

        return true;
    }

    public function check(&$context, &$stack) {

        $this->stack = $stack;

    }
}