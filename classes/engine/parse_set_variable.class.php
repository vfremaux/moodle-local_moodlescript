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

class parse_set_variable extends tokenizer {

    protected static $samples;

    public function __construct($remainder, parser &$parser) {
        parent::__construct($remainder, $parser);
        self::$samples = "SET VARIABLE savedcourseid FROM :courseid\n";
        self::$samples .= "SET VARIABLE variable1 FROM litteral\n";
        self::$samples .= "SET VARIABLE variable2 FROM litteral:{coursename}\n";
    }

    /*
     * Add keyword needs find what to add in the remainder
     */
    public function parse() {
        $this->trace('...Start parse '.$this->remainder);

        $pattern = '/^';
        $pattern .= tokenizer::IDENTIFIER.tokenizer::SP.'FROM'.tokenizer::SP.tokenizer::IDENTIFIER.tokenizer::OPT_SP;
        $pattern .= '/';

        $pattern2 = '/^';
        $pattern2 .= tokenizer::IDENTIFIER.tokenizer::SP.'FROM'.tokenizer::SP.tokenizer::QUOTED_EXT_LITTERAL.tokenizer::OPT_SP;
        $pattern2 .= '/';

        if (preg_match($pattern, trim($this->remainder), $matches)) {
            // Expected a pure identifier.
            $handler = new handle_set_variable();
            $context = new StdClass;

            $context->tovariable = $matches[1];

            $context->fromvariable = $matches[2];

            $this->trace('...End parse ++');
            return array($handler, $context);
        } else if (preg_match($pattern2, trim($this->remainder), $matches)) {
            // Expected a litteral.
            $handler = new handle_set_variable();
            $context = new StdClass;

            $context->tovariable = $matches[1];

            $context->fromvariable = $this->resolve_variables($matches[2]);

            $this->trace('...End parse ++');
            return array($handler, $context);
        } else {
            $this->trace('...End parse --');
            return [null, null];
        }
    }

}