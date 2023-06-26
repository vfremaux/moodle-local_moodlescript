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

        $this->trace('   Start parse UNENROL '.$this->remainder);

        $pattern1 = '/^';
        $pattern1 .= tokenizer::IDENTIFIER.tokenizer::SP;
        $pattern1 .= 'FROM'.tokenizer::SP.tokenizer::IDENTIFIER.tokenizer::OPT_SP;
        $pattern1 .= '(?:AS)?'.tokenizer::OPT_SP.tokenizer::OPT_TOKEN.tokenizer::OPT_SP;
        $pattern1 .= '$/';

        $pattern2 = '/^';
        $pattern2 .= tokenizer::IDENTIFIER.tokenizer::SP;
        $pattern2 .= 'FROM'.tokenizer::SP.tokenizer::IDENTIFIER.tokenizer::SP;
        $pattern2 .= '(HAVING)'.tokenizer::OPT_SP;
        $pattern2 .= '$/';

        if (preg_match($pattern1, $this->remainder, $matches)) {

            $handler = new handle_unenrol();

            $context = new StdClass;

            $targetuser = $matches[1];
            $identifier = new parse_identifier('user', $this->logger);
            if ($targetuser == 'current') {
                $context->unenroluserid = $targetuser;
            } else {
                $context->unenroluserid = $identifier->parse($targetuser, 'username');
            }

            $target = $matches[2];
            $identifier = new parse_identifier('course', $this->logger);
            if ($targetuser == 'current') {
                $context->unenrolcourseid = 'current';
            } else {
                $context->unenrolcourseid = $identifier->parse($target, 'shortname');
            }

            if ($role = @$matches[3]) {
                $context->role = $role;
            }

            $this->trace('   End parse ++');
            return [$handler, $context];
        } else if (preg_match($pattern2, $this->remainder, $matches)) {

            $handler = new handle_unenrol();

            $context = new StdClass;

            $targetuser = $matches[1];
            $identifier = new parse_identifier('user', $this->logger);
            if ($targetuser == 'current') {
                $context->unenroluserid = $targetuser;
            } else {
                $context->unenroluserid = $identifier->parse($targetuser, 'username');
            }

            $target = $matches[2];
            $identifier = new parse_identifier('course', $this->logger);
            if ($targetuser == 'current') {
                $context->unenrolcourseid = 'current';
            } else {
                $context->unenrolcourseid = $identifier->parse($target, 'shortname');
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