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

use \core_text;
use \StdClass;

require_once($CFG->dirroot.'/local/moodlescript/classes/engine/tokenizer.class.php');

/**
 * Having keyword parses and scans forward the script buffer until the next empty line. It
 * will collect name: value declarations to forge a $params structure in the context
 *
 */
class parse_having extends tokenizer {

    protected $table;

    public function parse() {
        global $DB;

        $params = new StdClass;

        while (!empty($this->parser->script)) {
            $line = array_shift($this->parser->script);
            if ($line == '' ||
                preg_match('/^[\/#;]/', $line) ||
                    preg_match('/^\s+$/', $line)) {
                // Stop scanning on first empty line.
                return $params;
            }

            $parts = explode(':', $line);
            $key = trim(array_shift($parts));

            // If line starts with an expected token, then put it back in buffer and terminate.
            if (in_array(core_text::strtoupper($key), $this->parser->keywords)) {
                array_push($this->parser->script, $line);
                return $params;
            }

            $value = trim(implode(':', $parts)); // Get all value parts and remove outside whitespaces.
            $value = preg_replace('/^[\'"]|["\']$/', '', $value); // Unquote value.
            $value = $this->parser->global_replace($value); // Make global context replacements on value.
            $params->$key = $value;
        }
        return $params;
    }
}