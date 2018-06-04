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

class parse_remove_capability extends tokenizer {

    /*
     * Add keyword needs find what to add in the remainder
     */
    public function parse() {
        global $CFG;

        $this->trace('   Start parse : '.$this->remainder);

        $pattern = '/^';
        $pattern .= tokenizer::IDENTIFIER.tokenizer::SP;
        $pattern .= 'FROM'.tokenizer::SP.tokenizer::IDENTIFIER.tokenizer::OPT_SP;
        $pattern .= '(HAVING)?'.tokenizer::OPT_SP;
        $pattern .= '$/';

        if (preg_match($pattern, $this->remainder, $matches)) {

            $handler = new \local_moodlescript\engine\handle_remove_capability();

            $context = new StdClass;

            $context->capability = $matches[1];
            $role = $matches[2];
            $having = @$matches[3];

            if (!empty($having)) {
                $having = new \local_moodlescript\engine\parse_having('', $this->parser);
                $params = $having->parse();
                $context->params = $params;

                if (!empty($context->params->context)) {
                    $contextparser = new \local_moodlescript\engine\parse_context($this->parser);
                    $context->roleid = $identifier->parse($context->params->context);
                }
            }

            $identifier = new \local_moodlescript\engine\parse_identifier('role', $this->parser);
            $context->roleid = $identifier->parse($role);

            $this->trace('   End parse ++');
            return array($handler, $context);
        } else {
            $this->trace('   End parse --');
            return [null, null];
        }
    }

}