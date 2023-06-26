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

class command_enrol extends tokenizer {

    /*
     * Add keyword needs find what to add in the remainder
     */
    public function parse() {
        global $USER, $CFG;

        $this->trace('   Start parse '.$this->remainder);
        $pattern1 = '/^'.tokenizer::IDENTIFIER.tokenizer::SP.'IN'.tokenizer::SP.tokenizer::QUOTED_EXT_IDENTIFIER.tokenizer::SP.'(HAVING)?'.tokenizer::OPT_SP.'$/';
        $pattern2 = '/^'.tokenizer::IDENTIFIER.tokenizer::SP.'IN'.tokenizer::SP.tokenizer::QUOTED_EXT_IDENTIFIER.tokenizer::SP;
        $pattern2 .= 'AS'.tokenizer::SP.tokenizer::IDENTIFIER.'(?:\s+)?(USING)?(?:\s+)?'.tokenizer::OPT_TOKEN.tokenizer::OPT_SP.'$/';

        if (preg_match($pattern2, $this->remainder, $matches)) {

            if ($CFG->debug == DEBUG_DEVELOPER) {
                if (function_exists('debug_trace')) {
                    debug_trace('command_Enrol : pattern 2 matched');
                }
            }

            $handler = new handle_enrol();

            $context = new StdClass;
            $haserrors = false;

            // Parsing user to enrol.
            $targetuser = $matches[1];
            if ($targetuser == 'current') {
                $context->enroluserid = 'current';
            } else {
                $identifier = new parse_identifier('user', $this->logger);
                $context->enroluserid = $identifier->parse($targetuser);
            }

            if (empty($context->enroluserid)) {
                $this->error('Command_enrol: Unresolved target user (null)');
                $haserrors = true;
            }

            // Parsing course to enrol.
            $targetcourse = $matches[2];
            $identifier = new parse_identifier('course', $this->logger);
            if ($targetcourse == 'current') {
                $context->enrolcourseid = 'current';
            } else {
                $context->enrolcourseid = $identifier->parse($target);
            }

            if (empty($context->enrolcourseid)) {
                $this->error('Command_enrol: Unresolved target course (null)');
                $haserrors = true;
            }

            // Parsing role to use.
            $identifier = new parse_identifier('role', $this->logger);
            $context->roleid = $identifier->parse($matches[3], 'shortname');

            if (empty($context->roleid)) {
                $this->error('Command_enrol: Unresolved role (null)');
                $haserrors = true;
            }

            // Parsing method to use.
            if (!empty($matches[5])) {
                $context->method = $matches[5];
            } else {
                $context->method = 'manual';
            }

            if ($haserrors) {
                $this->trace('   End parse +e');
                return [null, null];
            }

            $this->trace('   End parse ++');
            return [$handler, $context];
        } else if (preg_match($pattern1, $this->remainder, $matches)) {

            if ($CFG->debug == DEBUG_DEVELOPER) {
                if (function_exists('debug_trace')) {
                    debug_trace('command_Enrol : pattern 1 matched');
                }
            }

            $handler = new handle_enrol('ENROL '.$this->remainder);

            $context = new StdClass;
            $haserrors = false;

            // Parsing user to enrol.
            $targetuser = $matches[1];
            if ($targetuser == 'current') {
                $context->enroluserid = 'current';
            } else {
                $identifier = new parse_identifier('user', $this->logger);
                $context->enroluserid = $identifier->parse($targetuser, 'username');
            }

            if (empty($context->enroluserid)) {
                $this->error('Command_enrol: Unresolved target user (null)');
                $haserrors = true;
            }

            // Parsing course to enrol.
            $targetcourse = $matches[2];
            $identifier = new parse_identifier('course', $this->logger);
            if ($targetcourse == 'current') {
                $context->enrolcourseid = 'current';
            } else {
                $context->enrolcourseid = $identifier->parse($target, 'shortname');
            }

            if (empty($context->enrolcourseid)) {
                $this->error('Command_enrol: Unresolved target course (null)');
                $haserrors = true;
            }

            $this->parse_having($context);

            // Parsing role to use in having params.
            if (!empty($context->params->role)) {
                $identifier = new parse_identifier('role', $this->logger);
                $context->roleid = $identifier->parse($context->params->role, 'shortname');
                if (empty($context->roleid)) {
                    $this->error('Command_enrol: Unresolved target role');
                    $haserrors = true;
                }
            }

            // Parsing method to use in having params.
            if (!empty($context->params->method)) {
                $context->method = $context->params->method;
            } else {
                $context->method = 'manual';
            }

            if ($haserrors) {
                $this->trace('   End parse +e');
                return [null, null];
            }

            $this->trace('   End parse ++');
            return [$handler, $context];

        } else {
            $this->trace('   End parse --');
            return [null, null];
        }
    }

}