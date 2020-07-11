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

class parse_assign_role extends tokenizer {

    public static $samples;

    public function __construct($remainder, &$parser) {
        parent::__construct($remainder, $parser);
        self::$samples = "ASSIGN ROLE <roleshortname> TO idnumber:<idnumber> IN idnumber:<courseidnum>\n";
    }

    /*
     * Add keyword needs find what to add in the remainder
     */
    public function parse() {
        global $USER;

        $this->trace('...Start parse ');

        $pattern = '/^';
        $pattern .= tokenizer::TOKEN.tokenizer::SP;
        $pattern .= 'TO'.tokenizer::SP.tokenizer::IDENTIFIER.tokenizer::SP;
        $pattern .= 'IN'.tokenizer::SP.tokenizer::QUOTED_EXT_IDENTIFIER.tokenizer::OPT_SP;
        $pattern .= '$/';

        if (preg_match($pattern, $this->remainder, $matches)) {

            $handler = new \local_moodlescript\engine\handle_assign_role();
            $context = new StdClass;
            $context->rolename = $matches[1];
            $identifier = new \local_moodlescript\engine\parse_identifier('role', $this->logger);
            $context->roleid = $identifier->parse('shortname:'.$context->rolename);

            $targetuser = $matches[2];
            if ($targetuser != 'current') {
                $identifier = new \local_moodlescript\engine\parse_identifier('user', $this->logger);
                $context->assignuserid = $identifier->parse($targetuser);
            }

            $target = $matches[3];
            if ($target == 'current') {
                $context->rolecourseid = $target;
            } else {
                $identifier = new \local_moodlescript\engine\parse_identifier('course', $this->logger);
                $context->rolecourseid = $identifier->parse($target);
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