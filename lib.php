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

defined('MOODLE_INTERNAL') || die;

define('PROCESSING_INPUT', 0);
define('PROCESSING_PARSING', 1);
define('PROCESSING_RUNNING', 2);

function local_moodlescript_load_engine() {
    global $CFG;

    include_once($CFG->dirroot.'/local/moodlescript/classes/engine/parser.class.php');
    include_once($CFG->dirroot.'/local/moodlescript/classes/engine/handler_base.class.php');
    include_once($CFG->dirroot.'/local/moodlescript/classes/engine/tokenizer.class.php');

    // Require all implemented handlers.
    $handlers = glob($CFG->dirroot.'/local/moodlescript/classes/engine/handle_*');
    foreach ($handlers as $hndfile) {
        include_once($hndfile);
    }

    // Require all implemented parsers.
    $parsers = glob($CFG->dirroot.'/local/moodlescript/classes/engine/parse*');
    foreach ($parsers as $prsfile) {
        include_once($prsfile);
    }
}

function local_moodlescript_get_engine($script) {
    static $engineloaded = false;

    if (!$engineloaded) {
        local_moodlescript_load_engine();
        $engineloaded = true;
    }

    return new \local_moodlescript\engine\parser($script);
}

function local_moodlescript_execute($parser, $globalcontext) {
    global $CFG;
    $stack = $parser->parse((array)$globalcontext);

    if ($parser->has_errors()) {
        if (function_exists('debug_trace')) {
            if ($CFG->debug = DEBUG_DEVELOPER) {
                debug_trace($CFG->wwwroot." Parsed trace : ".$parser->print_trace());
            }
            debug_trace($CFG->wwwroot." Parsed stack errors : ".$parser->print_errors());
        }
        $report = $parser->print_errors();
        $report .= "\n".$parser->print_stack();
        return $report;
    }

    if (function_exists('debug_trace')) {
        debug_trace($CFG->wwwroot." Parsed stack :\n ".$parser->print_stack());
    }

    $result = $stack->check((array)$globalcontext);
    if ($stack->has_errors()) {
        if (function_exists('debug_trace')) {
            if ($CFG->debug = DEBUG_DEVELOPER) {
                debug_trace($CFG->wwwroot." Check warnings : ".$stack->print_log('warnings'));
                debug_trace($CFG->wwwroot." Check errors : ".$stack->print_log('errors'));
            }
        }
        return $stack->print_log('errors');
    }

    $result = $stack->execute((array)$globalcontext);

    if (function_exists('debug_trace')) {
        if ($stack->has_errors()) {
            // If the engine is robust enough. There should be not...
            debug_trace($CFG->wwwroot." Stack errors : ".$stack->print_log('warnings'));
            debug_trace($CFG->wwwroot." Stack errors : ".$stack->print_log('errors'));
        }
    }
    if (function_exists('debug_trace')) {
        if ($CFG->debug = DEBUG_DEVELOPER) {
            debug_trace($CFG->wwwroot." Stack execution log : ".$stack->print_log('log'));
        }
    }

}