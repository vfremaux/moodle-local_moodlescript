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

defined('MOODLE_INTERNAL') || die();

use \StdClass;

class command_unenrol extends tokenizer {

    /*
     * Add keyword needs find what to add in the remainder
     */
    public function parse() {
        global $USER;

        $this->trace('   Start parse '.$this->remainder);
        if (preg_match('/^([a-zA-Z0-9:_-]+)\s+FROM\s+([a-z\:A-Z0-9_-]+)\s*(?:AS)?\s*([a-zA-Z_]+)?\s*$/', $this->remainder, $matches)) {

            $handler = new handle_unenrol();

            $context = new StdClass;

            $targetuser = $matches[1];
            $identifier = new parse_identifier('user', $this->logger);
            if ($targetuser == 'current') {
                $context->userid = $USER->id;
            } else {
                $context->userid = $identifier->parse($targetuser);
            }

            $target = $matches[2];
            $identifier = new parse_identifier('course', $this->logger);
            if ($targetuser == 'current') {
                $context->unenrolcourseid = 'current';
            } else {
                $context->unenrolcourseid = $identifier->parse($target);
            }

            if ($role = @$matches[3]) {
                $context->role = $role;
            }

            $this->trace('   End parse ++');
            return [$handler, $context];
        } else if (preg_match('/^([a-zA-Z0-9:_-]+)\s+FROM\s+([a-z\:A-Z0-9_-]+)\s*(HAVING)\s*$/', $this->remainder, $matches)) {

            $handler = new handle_unenrol();

            $context = new StdClass;

            $targetuser = $matches[1];
            $identifier = new parse_identifier('user', $this->logger);
            if ($targetuser == 'current') {
                $context->userid = $USER->id;
            } else {
                $context->userid = $identifier->parse($targetuser);
            }

            $target = $matches[2];
            $identifier = new parse_identifier('course', $this->logger);
            if ($targetuser == 'current') {
                $context->unenrolcourseid = 'current';
            } else {
                $context->unenrolcourseid = $identifier->parse($target);
            }

            $this->parse_having($matches[3], $context);

            $this->trace('   End parse ++');
            return [$handler, $context];
        } else {
            $this->trace('   End parse --');
            return [null, null];
        }
    }

}