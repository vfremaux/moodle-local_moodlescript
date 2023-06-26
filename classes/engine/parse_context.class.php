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

class parse_context {

    protected $logger;

    public function __construct(&$logger = null) {
        $this->logger = $logger;
    }

    /**
     * Parses an context expression given as context:<level>:<id>.
     * Some "runtime" expression as context:runtime:<level>:<id> will be resolved at runtime.
     * Some context:func:<pluginname>@<funcname> will call that func in plugin's lib.php or locallib.php.
     * @param string $fqcontext context token expression
     * @param string $step the execution step, as 'parse' or 'runtime'
     * @return string|int a numeric contextid or the original expression reprocessed for runtime.
     */
    public function parse($fqcontext, $step = 'parse') {
        global $DB, $CFG;

        if ($CFG->debug == DEBUG_DEVELOPER) {
            if (function_exists('debug_trace')) {
                debug_trace('Parse context : parsing context '.$fqcontext);
            }
        }

        if (strpos($fqcontext, ':') === false) {
            // Expression is a true Mysql context id.
            if (!is_numeric($fqcontext)) {
                $this->logger[] = 'Not numeric primary id on context table';
                return;
            }

            return $fqcontext;
        }

        $parts = explode(':', $fqcontext);

        if (count($parts) == 1) {
            $this->logger[] = 'Single token for context '.$fqcontext.'. 3 at least expected.';
            return;
        }

        if (count($parts) == 2) {
            $this->logger[] = 'Two tokens for context '.$fqcontext.'. 3 at least expected.';
            return;
        }

        if ($parts[1] == 'runtime') {
            if ($step == 'parse') {
                // Do not resolve yet the identifier at parse time.
                return $fqcontext;
            }

            // We are at runtime. Remove the runtime marker and continue.
            $fqcontext = str_replace('runtime:', '', $fqcontext);
            array_shift($parts);
        }

        if (count($parts) == 3) {
            list($contextkeyword, $contextlevel, $ctxid) = $parts;
        }

        if (count($parts) > 3) {
            $this->logger[] = 'Too many parts in '.$fqcontext.'. 2 or 3 least expected.';
            return;
        }

        if ($contextlevel == 'func') {
            // We point a plugin function to get the identifier value.
            /*
             * the syntax admits a function form as func:component_name@functionname
             */
            list($pluginname, $func) = explode ('@', $itemid);
            $pluginpath = get_component_path($pluginname);
            if (!file_exists($pluginpath.'/locallib.php')) {
                if (!file_exists($pluginpath.'/lib.php')) {
                    $this->logger[] = 'Library field not found in component '.$pluginname;
                    return null;
                } else {
                    include_once($pluginpath.'/lib.php');
                }
            } else {
                include_once($pluginpath.'/locallib.php');
            }

            $fqfunc = str_replace('@', '_', $itemid);
            if (!function_exists($fqfunc)) {
                $this->logger[] = 'Required function '.$qffunc.' not found';
                return null;
            }

            $ctxid = $qffunc();
        } else {
            switch ($contextlevel) {
                case 'block': {
                    $ctxid = context_block::instance($itemid)->id;
                    break;
                }

                case 'module': {
                    $ctxid = context_module::instance($itemid)->id;
                    break;
                }

                case 'course': {
                    $ctxid = context_course::instance($itemid)->id;
                    break;
                }

                case 'category': {
                    $ctxid = context_coursecat::instance($itemid)->id;
                    break;
                }

                case 'user': {
                    $ctxid = context_user::instance($itemid)->id;
                    break;
                }

                case 'system': {
                    $ctxid = context_system::instance()->id;
                    break;
                }

                default:
                    return null;
            }
        }

        return $ctxid;
    }
}