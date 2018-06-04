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

class handle_unenrol extends handler {

    public function execute($result, &$context, &$stack) {
        global $DB;

        $this->stack = $stack;

        if ($context->unenrolcourseid == 'current') {
            $context->unenrolcourseid = $context->courseid;
        }

    }

    public function check(&$context, &$stack) {
        global $DB;

        $this->stack = $stack;

        if (empty($context->unenrolcourseid)) {
            $this->error('Empty course id');
        }

        if ($context->unenrolcourseid == 'current') {
            $context->unenrolcourseid = $context->courseid;
        }

        if (empty($context->method)) {
            $this->error('No enrol method');
        }

        if (empty($context->userid)) {
            $this->error('No user');
        }
    }
}