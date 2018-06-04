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

class parse_add_enrol_method extends tokenizer {

    /*
     * Add keyword needs find what to add in the remainder
     */
    public function parse() {
        global $CFG;

        $this->trace('   Start parse : '.$this->remainder);

        $pattern = '/^';
        $pattern .= tokenizer::TOKEN.tokenizer::SP;
        $pattern .= 'TO'.tokenizer::OPT_SP.tokenizer::IDENTIFIER.tokenizer::OPT_SP;
        $pattern .= '(HAVING)?'.tokenizer::OPT_SP;
        $pattern .= '$/';

        if (preg_match($pattern, $this->remainder, $matches)) {

            $handler = new \local_moodlescript\engine\handle_add_enrol_method();
            $context = new StdClass;
            $context->method = $matches[1];

            $target = $matches[2];
            $having = @$matches[3];

            if (!empty($having)) {
                $having = new \local_moodlescript\engine\parse_having('', $this->parser);
                $params = $having->parse();
                $context->params = $params;
            }

            if (empty($target)) {
                $this->errorlog[] = 'Empty course target for enrol method '.$context->method;
                $this->trace('   End parse -e');
                return [null, null];
            }

            $identifier = new \local_moodlescript\engine\parse_identifier('course', $this->parser);

            if ($target == 'current') {
                $context->enrolcourseid = $target;
            } else {
                $context->enrolcourseid = $identifier->parse($target);
            }

            $this->trace('   End parse ++');
            return array($handler, $context);
        } else {
            $this->trace('   End parse --');
            return [null, null];
        }
    }

}