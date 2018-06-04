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

class parse_backup_course extends tokenizer {

    /*
     * Add keyword needs find what to add in the remainder
     */
    public function parse() {
        $this->trace('...Start parse '.$this->remainder);

        $pattern = '/^';
        $pattern .= tokenizer::QUOTED_EXT_IDENTIFIER.tokenizer::SP;
        $pattern .= 'FOR'.tokenizer::SP.tokenizer::TOKEN.tokenizer::OPT_SP;
        $pattern .= '$/';

        if (preg_match($pattern, trim($this->remainder), $matches)) {

            $handler = new handle_backup_course();
            $context = new StdClass;

            if ($matches[1] != 'current') {
                $identifier = new parse_identifier('course', $this->logger);
                $context->backupcourseid = $identifier->parse($matches[1]);
            } else {
                $context->backupcourseid = 'current';
            }
            $context->target = $matches[2];

            $this->trace('...End parse ++');
            return array($handler, $context);
        } else {
            $this->trace('...End parse --');
            return [null, null];
        }
    }

}