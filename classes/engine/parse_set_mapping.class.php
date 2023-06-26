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

class parse_set_mapping extends tokenizer {

    protected static $samples;

    public function __construct($remainder, &$parser) {
        parent::__construct($remainder, $parser);
        self::$samples = "SET MAPPING mapname AS\nkey1: value1\nkey2: value2\n";
    }

    /*
     * Add keyword needs find what to add in the remainder
     */
    public function parse() {
        $this->trace('...Start parse '.$this->remainder);

        $pattern = '/^';
        $pattern .= tokenizer::IDENTIFIER.tokenizer::SP;
        $pattern .= '(HAVING)?'.tokenizer::OPT_SP; // Course other options.
        $pattern .= '$/';

        if (preg_match($pattern, trim($this->remainder), $matches)) {

            $handler = new handle_set_mapping();

            $mapname = $matches[1];

            // Get mapped data from having clauses.
            $members = new StdClass;
            $this->parse_having(@$matches[2], $members, $asarray = true);

            // Give named map to output context.
            $context = new StdClass;
            $context->$mapname = $members->params;

            $this->trace('...End parse ++');
            return array($handler, $context);
        } else {
            $this->trace('...End parse --');
            return [null, null];
        }
    }

}