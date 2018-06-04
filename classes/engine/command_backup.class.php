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

class command_backup extends tokenizer {

    /*
     * Add keyword needs find what to add in the remainder
     */
    public function parse() {
        $this->trace('   Start parse backup '.$this->remainder);
        if (preg_match('/^([a-zA-Z0-9_]+?)\s+(.*)$/', $this->remainder, $matches)) {
            $class = '\\local_moodlescript\\engine\\parse_backup_'.\core_text::strtolower(trim($matches[1]));
            $classfile = 'parse_backup_'.\core_text::strtolower(trim($matches[1]));

            return $this->standard_sub_parse($matches, $class, $classfile);
        } else {
            $this->trace('   End parse --');
            return [null, null];
        }
    }

}