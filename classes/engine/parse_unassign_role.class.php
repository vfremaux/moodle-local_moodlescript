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

class parse_unassign_role extends tokenizer {

    public static $samples;

    public function __construct($remainder, &$parser) {
        parent::__construct($remainder, $parser);
        self::$samples = "UNASSIGN ROLE <roleshortname> IN idnumber:<courseidnumber> FOR idnumber:<useridnumber>\n";
    }

    /*
     * Add keyword needs find what to add in the remainder
     */
    public function parse() {
        $this->trace('...Start parse ');

        $pattern = '/^';
        $pattern .= tokenizer::TOKEN.tokenizer::SP;
        $pattern .= 'IN'.tokenizer::SP.tokenizer::QUOTED_EXT_IDENTIFIER.tokenizer::SP;
        $pattern .= 'FOR'.tokenizer::SP.tokenizer::QUOTED_EXT_IDENTIFIER.tokenizer::OPT_SP;
        $pattern .= '$/';

        if (preg_match($pattern, $this->remainder, $matches)) {

            $handler = new \local_moodlescript\engine\handle_unassign_role();
            $context = new StdClass;
            $context->rolename = $matches[1];
            $identifier = new \local_moodlescript\engine\parse_identifier('role', $this->logger);
            $context->roleid = $identifier->parse('shortname:'.$context->rolename);

            $target = $matches[2];
            $identifier = new \local_moodlescript\engine\parse_identifier('course', $this->logger);
            if ($target == 'current') {
                $context->rolecourseid = $target;
            } else {
                $context->rolecourseid = $identifier->parse($target);
            }

            $user = $matches[3];
            $identifier = new \local_moodlescript\engine\parse_identifier('user', $this->logger);
            if ($user == 'current') {
                $context->userid = $user;
            } else {
                $context->userid = $identifier->parse($user);
            }

            $this->trace('...End parse ++');
            return array($handler, $context);
        } else {
            $this->error("Parse Error : No syntax match ");
            $this->trace('...End parse --');
            return [null, null];
        }
    }

}