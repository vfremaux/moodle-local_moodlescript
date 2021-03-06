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

class parse_move_role_assign extends tokenizer {

    /*
     * Add keyword needs find what to add in the remainder
     */
    public function parse() {
        $this->trace('...Start parse '.$this->remainder);

        $pattern = '/^'.tokenizer::OPT_SP;
        $pattern .= 'FROM'.tokenizer::SP.tokenizer::IDENTIFIER.tokenizer::SP;
        $pattern .= 'TO'.tokenizer::SP.tokenizer::IDENTIFIER.tokenizer::SP;
        $pattern .= 'IN'.tokenizer::SP.tokenizer::QUOTED_EXT_IDENTIFIER.tokenizer::OPT_SP;
        $pattern .= '(IF EXISTS)?'.tokenizer::OPT_SP;
        $pattern .= '$/';

        $this->trace('using '.$pattern);
        if (preg_match($pattern, trim($this->remainder), $matches)) {

            $handler = new handle_move_role_assign();
            $context = new StdClass;

            $context->scope = 'course';

            if ($matches[1] != 'current') {
                $identifier = new parse_identifier('course', $this->logger);
                $context->movecourseid = $identifier->parse($matches[1]);
            } else {
                $context->movecourseid = 'current';
            }

            $identifier = new parse_identifier('role', $this->logger);
            $context->rolefrom = $identifier->parse('shortname:'.$matches[2]);

            $identifier = new parse_identifier('role', $this->logger);
            $context->roleto = $identifier->parse('shortname:'.$matches[3]);

            $this->trace('...End parse ++');
            return array($handler, $context);
        } else {
            $this->trace('...End parse --');
            return [null, null];
        }
    }

}