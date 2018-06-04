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

class parse_identifier {

    protected $table;

    protected $logger;

    public function __construct($table, &$logger = null) {

        if (empty($table)) {
            throw new coding_exception('Table cannot be empty for an object identifier');
        }

        $this->table = $table;
        $this->logger = $logger;
    }

    /**
     * Parses an identifier
     * @param string $fqidentifier Full qualified identifier
     */
    public function parse($fqidentifier, $step = 'parse') {
        global $DB, $CFG;

        if ($CFG->debug == DEBUG_DEVELOPER) {
            if (function_exists('debug_trace')) {
                debug_trace('Parse Identifier : parsing identifier '.$fqidentifier);
            }
        }

        if (strpos($fqidentifier, ':') === false) {

            if (!is_numeric($fqidentifier)) {
                $this->logger[] = 'Not numeric primary id on '.$this->table;
                return;
            }

            return $fqidentifier;
        }

        $parts = explode(':', $fqidentifier);

        if (count($parts) == 1) {
            $this->logger[] = 'Single token for identifier '.$fqidentifier.'. 2 at least expected.';
            return;
        }

        if ($parts[0] == 'runtime') {
            if ($step == 'parse') {
                // Do not resolve yet the identifier at parse time.
                return $fqidentifier;
            }

            // We are at runtime. Remove the runtime marker and continue.
            $fqidentifier = str_replace('runtime:', '', $fqidentifier);
            array_shift($parts);
        }

        if (count($parts) == 2) {
            list($field, $identifier) = $parts;
        }
        if (count($parts) == 3) {
            list($field, $mode, $identifier) = $parts;
        }
        if (count($parts) > 3) {
            $this->logger[] = 'Too many parts in '.$fqidentifier.'. 2 or 3 least expected.';
            return;
        }

        $identifier = preg_replace('/^"|"$/', '', $identifier); // Remove quotes.

        if (!empty($mode) && $mode == 'func') {
            // We point a plugin function to get the identifier value.
            /*
             * the syntax admits a function form as func:component_name@functionname
             */
            list($pluginname, $func) = explode ('@', $identifier);
            $pluginpath = \core_component::get_component_directory($pluginname);
            if (!file_exists($pluginpath.'/locallib.php')) {
                if (!file_exists($pluginpath.'/lib.php')) {
                    $this->logger[] = 'Library field not found in component '.$pluginname;
                    return null;
                } else {
                    include_once($pluginpath.'/lib.php');
                }
            } else {
                include_once($pluginpath.'/locallib.php');
                if (file_exists($pluginpath.'/lib.php')) {
                    include_once($pluginpath.'/lib.php');
                }
            }

            $fqfunc = str_replace('@', '_', $identifier);
            if (!function_exists($fqfunc)) {
                $this->logger[] = 'Required function '.$fqfunc.' not found';
                return null;
            }

            $identifier = $fqfunc();
            if (function_exists('debug_trace')) {
                debug_trace("Got $identifier by func for $field ");
            }
        }

        if (empty($field)) {
            $this->errorlog[] = 'Identifier input error on field for '.$this->table.' in '.$fqidentifier;
            return;
        }

        if (empty($identifier)) {
            $this->errorlog[] = 'Identifier input error in value for '.$this->table.' in '.$fqidentifier;
            return;
        }

        $dbman = $DB->get_manager();
        if (!$dbman->field_exists($this->table, $field)) {
            $this->errorlog[] = 'Missing or unkown field '.$field.' in '.$this->table;
            return;
        }

        try {
            $id = $DB->get_field($this->table, 'id', array($field => $identifier));
        } catch (\Exception $e) {
            $this->errorlog[] = 'Identifier query error in '.$this->table.' by '.$field;
            return;
        }

        return $id;
    }
}