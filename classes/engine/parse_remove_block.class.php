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

class parse_remove_block extends tokenizer {

    public static $samples;

    public function __construct($remainder, parser &$parser) {
        parent::__construct($remainder, $parser);
        self::$samples = "REMOVE BLOCK latest_news FROM idnumber:\"C2022_203\"\n\n";
        self::$samples = "REMOVE BLOCK idnumber:\"B203_34\"\n";
    }

    /*
     * Add keyword needs find what to add in the remainder
     */
    public function parse() {
        $this->trace('...Start parse '.$this->remainder);

        $pattern = '/^';
        $pattern .= tokenizer::TOKEN.tokenizer::SP;
        $pattern .= 'FROM'.tokenizer::SP.tokenizer::QUOTED_EXT_IDENTIFIER.tokenizer::OPT_SP;
        $pattern .= '(HAVING)?'.tokenizer::OPT_SP;
        $pattern .= '$/';

        if (preg_match($pattern, $this->remainder, $matches)) {

            $handler = new \local_moodlescript\engine\handle_remove_block();
            $context = new StdClass;
            $context->blockname = $matches[1];

            $target = $matches[2];
            $identifier = new \local_moodlescript\engine\parse_identifier('course', $this->logger);
            if ($target == 'current') {
                $context->blockcourseid = $target;
            } else {
                $context->blockcourseid = $identifier->parse($target);
            }

            $this->parse_having(@$matches[3], $context);

            $this->trace('...End parse ++');
            return array($handler, $context);
        } else {
            $this->trace('...End parse --');
            return [null, null];
        }
    }

}