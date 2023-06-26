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

class parse_add_group extends tokenizer {

    public static $samples;

    public function __construct($remainder, &$parser) {
        parent::__construct($remainder, $parser);
        self::$samples = "ADD GROUP <groupname> IF NOT EXISTS TO idnumber:<courseidnum> IDENTIFIED BY <groupidnumber>\n\n";
        self::$samples = "ADD GROUP <groupname> TO idnumber:<courseidnum> IDENTIFIED BY <groupidnumber> HAVING\n";
        self::$samples .= "enrolmentkey: <key>\n";
    }

    /*
     * Add keyword needs find what to add in the remainder
     */
    public function parse() {
        $this->trace('...Start parse add group '.$this->remainder);

        $pattern = '/^';
        $pattern .= tokenizer::QUOTED_EXT_IDENTIFIER.tokenizer::SP;
        $pattern .= '(IF NOT EXISTS)?'.tokenizer::OPT_SP;
        $pattern .= 'TO'.tokenizer::SP.tokenizer::QUOTED_EXT_IDENTIFIER.tokenizer::OPT_SP;
        $pattern .= '(IDENTIFIED BY)?'.tokenizer::OPT_SP.tokenizer::OPT_NON_SPACE.tokenizer::OPT_SP;
        $pattern .= '(HAVING)?'.tokenizer::OPT_SP;
        $pattern .= '$/';

        if (preg_match($pattern, $this->remainder, $matches)) {

            $handler = new \local_moodlescript\engine\handle_add_group();

            $context = new StdClass;
            $context->groupname = $matches[1];

            if (!empty($matches[2])) {
                $context->ifnotexists = true;
            }

            $target = $matches[3];
            $identifier = new \local_moodlescript\engine\parse_identifier('course', $this->logger);
            if ($target == 'current') {
                $context->groupcourseid = $target;
            } else {
                $context->groupcourseid = $identifier->parse($target);
            }

            if (!empty($matches[4])) {
                $context->groupidnumber = $matches[3];
            }

            $this->parse_having(@$matches[5], $context);

            $this->trace('...End parse ++');
            return array($handler, $context);
        } else {
            $this->trace('...End parse --');
            return [null, null];
        }
    }

}