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

class parse_add_course extends tokenizer {

    public static $samples;

    public function __construct($remainder, &$parser) {
        parent::__construct($remainder, $parser);
        self::$samples = "ADD COURSE \"<course name>\" AS \"<shortname>\" TO idnumber:<coursecatidnum> HAVING\n";
        self::$samples .= "idnumber: <courseidnum>\n";
        self::$samples .= "visible: 0|1\n\n";
    }

    /*
     * Add keyword needs find what to add in the remainder
     */
    public function parse() {

        $this->trace('Start parse add course '.$this->remainder);

        $pattern = '/^';
        $pattern .= tokenizer::QUOTED_EXT_LITTERAL.tokenizer::SP; // Course fullname.
        $pattern .= 'AS'.tokenizer::SP.tokenizer::IDENTIFIER.tokenizer::SP; // course shortname.
        $pattern .= 'TO'.tokenizer::SP.tokenizer::QUOTED_EXT_IDENTIFIER.tokenizer::OPT_SP; // Category identifier
        $pattern .= '(HAVING)?'.tokenizer::OPT_SP; // Course other options.
        $pattern .= '$/';

        if (preg_match($pattern, $this->remainder, $matches)) {

            $handler = new \local_moodlescript\engine\handle_add_course();
            $context = new StdClass;

            $context->fullname = $matches[1];
            $context->fullname = preg_replace('/^\'"|\'"$/', '', $context->fullname); // Unquote.

            $context->shortname = $matches[2];

            $target = $matches[3];
            $identifier = new \local_moodlescript\engine\parse_identifier('course_categories', $this->logger);
            if ($target == 'current') {
                $context->addcoursecatid = $target;
            } else {
                $context->addcoursecatid = $identifier->parse($target);
            }

            $this->parse_having(@$matches[4], $context);

            $this->trace('...End parse ++');
            return array($handler, $context);
        } else {
            $this->error("Parse Error : No syntax match ");
            $this->trace('...End parse --');
            return [null, null];
        }
    }

}