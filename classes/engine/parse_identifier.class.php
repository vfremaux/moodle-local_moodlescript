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
     * @param string $defaultkey tells which field is used as default for an unqualified identifier in input.
     * @param string $step parse|run When parsing, dynamic resolution identifiers will not be resolved and will pass thru
     * @param int $courseid if given and not 0, will complete the identifier to match a unique record (f.e. groups by name)
     */
    public function parse($fqidentifier, $defaultkey, $step = 'parse', $courseid = 0) {
        global $DB, $CFG;

        if ($CFG->debug == DEBUG_DEVELOPER) {
            if (function_exists('debug_trace')) {
                debug_trace('Parse Identifier : parsing identifier '.$fqidentifier);
            }
        }

        if (strpos($fqidentifier, ':') === false) {

            if (empty($defaultkey)) {
                if (!is_numeric($fqidentifier)) {
                    $this->logger->log('Not numeric primary id on '.$this->table);
                    return;
                }

                return $fqidentifier;
            } else {
                // Add default key to unqualified identifier.
                $fqidentifier = $defaultkey.':'.$fqidentifier;
            }
        }

        $parts = explode(':', $fqidentifier);

        if (count($parts) == 1) {
            $this->error('Single token for identifier '.$fqidentifier.'. 2 at least expected.', $step);
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
            $this->error('Too many parts in '.$fqidentifier.'. 2 or 3 least expected.', $step);
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
                    $this->error('Library field not found in component '.$pluginname, $step);
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
                $this->error('Required function '.$fqfunc.' not found', $step);
                return null;
            }

            $identifier = $fqfunc();
            if (function_exists('debug_trace')) {
                debug_trace("Got $identifier by func for $field ");
            }
        }

        if (empty($field)) {
            $this->error('Identifier input error on field for '.$this->table.' in '.$fqidentifier, $step);
            return;
        }

        if (empty($identifier)) {
            $this->error('Identifier input error in value for '.$this->table.' in '.$fqidentifier, $step);
            return;
        }

        $dbman = $DB->get_manager();
        if (!$dbman->field_exists($this->table, $field)) {
            $this->error('Missing or unkown field '.$field.' in '.$this->table, $step);
            return;
        }

        try {
            $params = array($field => $identifier);
            if ($courseid) {
                // Add course specific filter.
                /*
                 * NOTE : We assume the course field is "courseid". this may be a bit restrictive.
                 * We are focussing the group and grouping management at the moment.
                 */
                $params['courseid'] = $courseid;
            }
            $id = $DB->get_field($this->table, 'id', $params);
        } catch (\Exception $e) {
            $this->error('Identifier query error in '.$this->table.' by '.$field, $step);
            return;
        }

        return $id;
    }

    /*
     * Local error dispatcher.
     * @param string $msg
     * @param string $step 'parse' or 'runtime' (occasionally 'check').
     */
    protected function error($msg, $step = 'parse') {
        if ($step == 'runtime') {
            // At run time, errors are usualy fatal.
            throw new moodle_exception($msg);
        }

        $this->logger->error($msg);
    }
}