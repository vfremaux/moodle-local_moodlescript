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

class parse_remove_grouping extends tokenizer {

    public static $samples;

    public function __construct($remainder, &$parser) {
        parent::__construct($remainder, $parser);
        self::$samples = "REMOVE GROUPING name:<groupingname> FROM idnumber:<courseidnum>\n\n";
        self::$samples = "REMOVE GROUPING idnumber:<groupingidnumber> FROM idnumber:<courseidnum>\n\n";
        self::$samples = "REMOVE GROUPING id:<groupingid> FROM idnumber:<courseidnum>\n\n";
        self::$samples = "REMOVE GROUPING name:<groupingname> FROM shortname:<courseshortname>\n\n";
        self::$samples = "REMOVE GROUPING name:<groupingname> FROM id:<courseid>\n\n";
    }

    /*
     * Add keyword needs find what to add in the remainder
     */
    public function parse() {
        $this->trace('...Start parse remove grouping '.$this->remainder);

        $pattern = '/^';
        $pattern .= tokenizer::QUOTED_EXT_IDENTIFIER.tokenizer::SP;
        $pattern .= 'FROM'.tokenizer::SP.tokenizer::QUOTED_EXT_IDENTIFIER.tokenizer::OPT_SP;
        $pattern .= '$/';

        if (preg_match($pattern, $this->remainder, $matches)) {

            $handler = new \local_moodlescript\engine\handle_remove_grouping();

            $context = new StdClass;

            $groupid = $matches[1];
            $identifier = new \local_moodlescript\engine\parse_identifier('groupings', $this->logger);
            if ($target == 'current') {
                $context->groupingid = $target;
            } else {
                $context->groupingid = $identifier->parse($target);
            }

            $target = $matches[2];
            $identifier = new \local_moodlescript\engine\parse_identifier('course', $this->logger);
            if ($target == 'current') {
                $context->groupingcourseid = $target;
            } else {
                $context->groupingcourseid = $identifier->parse($target);
            }

            $this->trace('...End parse ++');
            return array($handler, $context);
        } else {
            $this->trace('...End parse --');
            return [null, null];
        }
    }

}