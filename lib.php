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
    include_once($CFG->dirroot.'/local/moodlescript/classes/engine/evaluable_expression.class.php');
    include_once($CFG->dirroot.'/local/moodlescript/classes/engine/evaluable_functions.class.php');

    if ($CFG->debug == DEBUG_DEVELOPER) {
        if (function_exists('debug_trace')) {
            debug_trace($CFG->wwwroot." Loading handlers... ", TRACE_DEBUG_FINE);
        }
    }

    // Require all implemented handlers.
    $handlers = glob($CFG->dirroot.'/local/moodlescript/classes/engine/handle_*');
    foreach ($handlers as $hndfile) {
        if ($CFG->debug == DEBUG_DEVELOPER) {
            if (function_exists('debug_trace')) {
                debug_trace(" Loading handler $hndfile ", TRACE_DEBUG_FINE);
            }
        }
        include_once($hndfile);
    }

    // Require all implemented parsers.
    $parsers = glob($CFG->dirroot.'/local/moodlescript/classes/engine/parse*');
    foreach ($parsers as $prsfile) {
        if ($CFG->debug == DEBUG_DEVELOPER) {
            if (function_exists('debug_trace')) {
                debug_trace(" Loading handler $prsfile ", TRACE_DEBUG_FINE);
            }
        }
        include_once($prsfile);
    }
}

/**
 * Get a parser instance loaded with the script to parse and execute.
 * Ensures that all moodlescript libs and classes are loaded.
 * @param string $script
 * @return a parser ready to operate.
 */
function local_moodlescript_get_engine(string $script) {
    static $engineloaded = false;

    if (!$engineloaded) {
        local_moodlescript_load_engine();
        $engineloaded = true;
    }

    return new \local_moodlescript\engine\parser($script);
}

/**
 * Execute the full process, i.e. : parse, check and execute of the loaded parser.
 * @param \local_moodlescript\engine\parser $parser the parser to execute
 * @param object $globalcontext a data stub to provide parser with initial data context.
 * @param int &$reportlevel tells what level of report is given as returned output : 0 : success log, 1 : warnings, 2 : errors
 * @return the last error report, or nothing if succedded.
 */
function local_moodlescript_execute($parser, StdClass $globalcontext, &$reportlevel = null) {
    global $CFG;

    $stack = $parser->parse($globalcontext);
    $reportlevel = 0;

    if ($parser->has_errors()) {
        if (function_exists('debug_trace')) {
            if ($CFG->debug == DEBUG_DEVELOPER) {
                debug_trace($CFG->wwwroot." Parsed trace : ".$parser->print_trace());
            }
            debug_trace($CFG->wwwroot." Parsed stack errors : ".$parser->print_errors());
        }
        $report = $parser->print_errors();
        $report .= "\n".$parser->print_stack();
        $reportlevel = 2;
        return $report;
    }

    if (function_exists('debug_trace')) {
        debug_trace($CFG->wwwroot." Parsed stack :\n ".$parser->print_stack(), TRACE_DEBUG);
    }

    if (is_null($stack)) {
        throw new coding_exception("Null stack. Cannot execute.");
    }

    // echo "BEFORE CHECK<br/>";
    // print_object($stack);

    $result = $stack->check($globalcontext);
    if ($stack->has_errors()) {
        if (function_exists('debug_trace')) {
            if ($CFG->debug == DEBUG_DEVELOPER) {
                debug_trace($CFG->wwwroot." Check errors : ".$stack->print_log('errors'), TRACE_DEBUG);
                debug_trace($CFG->wwwroot." Check warnings : ".$stack->print_log('warnings'), TRACE_DEBUG);
            }
        }
        $reportlevel = 2;
        $report = $stack->print_log('errors');
        if ($stack->has_warnings()) {
            $report .= "<br/>\n";
            $report .= $stack->print_log('warnings');
        }
        return $report;
    }

    if ($stack->has_warnings()) {
        if (function_exists('debug_trace')) {
            if ($CFG->debug == DEBUG_DEVELOPER) {
                debug_trace($CFG->wwwroot." Check warnings : ".$stack->print_log('warnings'), TRACE_DEBUG);
            }
        }
        $reportlevel = 1;
        if ($stack->has_warnings()) {
            $report .= "<br/>\n";
            $report .= "Check warnings : ".$stack->print_log('warnings');
        }
    }

    // echo "AFTER CHECK<br/>";
    // print_object($stack);
    // die;

    // $globalarr = (array) $globalcontext;

    $stack->execute($globalcontext);

    if ($stack->has_errors()) {
        if (function_exists('debug_trace')) {
            // If the engine is robust enough. There should be not...
            debug_trace($CFG->wwwroot." Stack errors : ".$stack->print_log('errors'), TRACE_DEBUG);
            if ($stack->has_warnings()) {
                debug_trace($CFG->wwwroot." Stack errors : ".$stack->print_log('warnings'), TRACE_DEBUG);
            }
        }
        $reportlevel = 2;
        $report = $stack->print_log('log');
        $report .= "<br/>\n";
        $report .= $stack->print_log('errors');
        if ($stack->has_warnings()) {
            $report .= "<br/>\n";
            $report .= $stack->print_log('warnings');
        }
        return $report;
    }

    if ($stack->has_warnings()) {
        $report = $stack->print_log('log');
        $report .= "<br/>\n";
        $report .= $stack->print_log('warnings');
    }

    if (function_exists('debug_trace')) {
        debug_trace($CFG->wwwroot." Stack execution log : ".$stack->print_log('log'), TRACE_DEBUG);
    }

    return $stack->print_log('log')."<br/>\n";
}