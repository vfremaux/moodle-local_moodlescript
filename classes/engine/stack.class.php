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

/**
 * implements a posthandler stack to be executed after a deployment.
 *
 * the stack can register some handlers and execute the handler sequence
 */
class stack {

    /**
     * A list of handlers to execute.
     */
    protected $stack;

    /**
     * A list of contexts associated to each handler in the stack.
     */
    public $contexts;

    /**
     * the context values snapshot loaded at start of the stack execution.
     * is modified by the handler returns, unless stack context is frozen..
     */
    protected $initialcontext;

    /**
     * the current context of the stack is initialized with the global context and
     * is modified by the handler returns, unless stack context is frozen..
     */
    protected $currentcontext;

    /**
     * If context is frozen, individual handlers cannot change values in the current context.
     */
    protected $contextfrozen;

    protected $log;
    protected $errors;
    protected $warnings;

    protected $haserrors;

    public function __construct() {
        $this->stack = array();
        $this->log = array();
        $this->errors = array();
        $this->contexts = array();
        $this->warnings = array();
        $this->initialcontext = new StdClass();
        $this->currentcontext = new StdClass();
    }

    /**
     * Register the handlers to be processed with an execution context.
     */
    public function register(handler $handler, $context = null) {

        if (is_null($handler)) {
            return;
        }

        $this->stack[] = $handler;
        $this->contexts[] = $context;
    }

    public function reset_context() {
        $this->currentcontext = $this->initialcontext;
    }

    public function update_current_context($key, $value) {
        $this->currentcontext->$key = $value;
    }

    /**
     * Processes all the stack in order propagating a result object;
     * @param object $globalcontext
     */
    public function execute(&$globalcontext) {
        global $CFG;

        if (function_exists('debug_trace')) {
            if ($CFG->debug == DEBUG_DEVELOPER) {
                debug_trace("Start stack.");
            }
        }

        if (is_null($globalcontext)) {
            // Initialize a blanck context object.
            $globalcontext = new StdClass();
        }

        $this->initialcontext = $globalcontext;
        $this->currentcontext = $globalcontext;

        $results = array();
        if (!empty($this->stack)) {
            $context = reset($this->contexts);
            foreach ($this->stack as $handler) {
                if (!empty($this->currentcontext)) {
                    // Add/override with global/current context.
                    foreach ($this->currentcontext as $key => $value) {
                        $context->$key = $value;
                    }
                }
                if (function_exists('debug_trace')) {
                    if ($CFG->debug == DEBUG_DEVELOPER) {
                        debug_trace("Executing ".get_class($handler));
                    }
                }
                $handler->execute($results, $context, $this);
                $context = next($this->contexts);
            }
        }
        return $results;
    }

    /**
     * Processes all the stack in order propagating a result object;
     * @param object $globalcontext
     */
    public function check($globalcontext = null) {
        global $CFG;

        if (function_exists('debug_trace')) {
            if ($CFG->debug == DEBUG_DEVELOPER) {
                debug_trace("Start stack check.");
            }
        }

        if (empty($globalcontext)) {
            // Initialize a blanck context object.
            $globalcontext = new StdClass;
        }

        $result = null;
        if (!empty($this->stack)) {
            $context = reset($this->contexts);
            foreach ($this->stack as $handler) {
                if (!empty($globalcontext)) {
                    // Add/override with global context.
                    foreach ($globalcontext as $key => $value) {
                        $context->$key = $value;
                    }
                }
                if (function_exists('debug_trace')) {
                    if ($CFG->debug == DEBUG_DEVELOPER) {
                        debug_trace("Checking ".get_class($handler)." With context:");
                        debug_trace($context);
                    }
                }
                $handler->check($context, $this);
                $context = next($this->contexts);
            }
        }

        if (function_exists('debug_trace')) {
            if ($CFG->debug == DEBUG_DEVELOPER) {
                debug_trace("Stack check finished.");
            }
        }

        return $this->has_errors();
    }

    public function log($msg) {
        $this->log[] = $msg;
    }

    public function warn($msg) {
        $this->warnings[] = $msg;
    }

    public function error($msg) {
        $this->errors[] = $msg;
    }

    /**
     * Get the error status of the stack.
     */
    public function has_errors() {
        return !empty($this->errors);
    }

    public function get_log() {
        return $this->log;
    }

    /**
     * Get the result of the log.
     */
    public function print_log($logtype = 'log') {
        switch ($logtype) {
            case 'log': {
                return implode("\n", $this->log);
                break;
            }

            case 'errors': {
                return implode("\n", $this->errors);
                break;
            }

            case 'warnings': {
                return implode("\n", $this->warnings);
                break;
            }
        }
    }

    public function print_stack() {

        $str = '';
        if (!empty($this->stack)) {
            $i = 0;
            foreach ($this->stack as $stacktask) {
                $str .= get_class($stacktask)." (";
                $context = $this->contexts[$i];
                foreach ($context as $key => $value) {
                    if (is_object($value) || is_array($value)) {
                        // This are extra context items added from outside.
                        continue;
                    }
                    if ($key != 'params' && $key != 'options') {
                        $str .= "$key: $value, ";
                    } else if ($key == 'params') {
                        $str .= 'params[ ';
                        foreach ($value as $paramkey => $paramvalue) {
                            $str .= "$paramkey: $paramvalue, ";
                        }
                        $str .= '], ';
                    } else {
                        // Options.
                        $str .= 'OPTS[ ';
                        foreach ($value as $paramkey => $paramvalue) {
                            $str .= "$paramkey: $paramvalue, ";
                        }
                        $str .= '], ';
                    }
                }
                $str .= ")\n";
                $i++;
            }
        } else {
            return 'Empty stack.';
        }

        return $str;
    }

    public function print_errors() {
        return implode("\n", $this->errors);
    }

    public function freeze() {
        $this->contextfrozen = true;
    }

    public function unfreeze() {
        $this->contextfrozen = false;
    }

    public function is_context_frozen() {
        return $this->contextfrozen;
    }
}