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

use StdClass;
use coding_exception;

/**
 * implements a posthandler stack to be executed after a deployment.
 *
 * the stack can register some handlers and execute the handler sequence
 */
class stack {

    /**
     * A unique id for each stack.
     */
    protected $stackid;

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
        static $stackid = 1;

        $this->stackid = $stackid;
        $this->stack = array();
        $this->log = array();
        $this->errors = array();
        $this->contexts = array();
        $this->warnings = array();
        $this->initialcontext = new StdClass();
        $this->currentcontext = new StdClass();

        $stackid++;
    }

    public function get_id() {
        return $this->stackid;
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
     * All handlers are fed with the currentcontext object. The currentcontext
     * object is appended with all the step's proper context values obtained
     * from parsing.
     * @param object $globalcontext the initial context for the stack execution.
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
        } else {
            if (!is_object($globalcontext)) {
                throw new coding_exception("globalcontext should be object at this point");
            }
        }

        // Initialisation before execute()
        $this->initialcontext = clone($globalcontext); // ensure object will not be altered by changes
        $this->currentcontext = $globalcontext;

        $results = array();
        if (!empty($this->stack)) {
            $context = reset($this->contexts);
            $i = 0;
            foreach ($this->stack as $handler) {
                // Update current context with handler's specific.
                // echo "Updating context stack ".$this->stackid." before execution <br/>";
                foreach ($context as $key => $value) {
                    if (is_object($value)) {
                        // echo "Adding to step context ; $key => ".print_r($value, true)." <br/>";
                    } else {
                        // echo "Adding to step context ; $key => $value <br/>";
                    }
                    $this->currentcontext->$key = $value;
                }

                if (function_exists('debug_trace')) {
                    if ($CFG->debug == DEBUG_DEVELOPER) {
                        debug_trace("Executing ".get_class($handler));
                    }
                }
                $handler->execute($results, $this);
                if ($this->has_errors()) {
                    // Stop here.
                    return $results;
                }
                // Fetch next handler context for next turn.
                $context = next($this->contexts);
                $i++;
            }
        }
        return $results;
    }

    /**
     * Simple public accessor.
     */
    public function get_current_context() {
        return $this->currentcontext;
    }

    /**
     * Processes all checks before exectution; Check step receives the global context with static input globals.
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

        // initialise pre check().
        $this->initialcontext = clone($globalcontext); // Ensure object will NOT be altered.
        $this->currentcontext = $globalcontext;

        $result = null;
        if (!empty($this->stack)) {
            $context = reset($this->contexts);

            foreach ($this->stack as $handler) {
                if (!empty($context)) {
                    // Add/override with step proper context issued from parsing.
                    foreach ($context as $key => $value) {
                        $this->currentcontext->$key = $value;
                    }
                }
                if (function_exists('debug_trace')) {
                    if ($CFG->debug == DEBUG_DEVELOPER) {
                        debug_trace("Checking ".get_class($handler)." With updated context:");
                        debug_trace($context);
                    }
                }
                $handler->check($this);
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

    /**
     * the stack's log
     */
    public function log($msg) {
        $this->log[] = $msg;
    }

    /**
     * the stack's warning log
     */
    public function warn($msg) {
        $this->warnings[] = $msg;
    }

    /**
     * the stack's error log
     */
    public function error($msg) {
        $this->errors[] = $msg;
    }

    /**
     * Get the error status of the stack.
     */
    public function has_errors() {
        return !empty($this->errors);
    }

    /**
     * Get the warning status of the stack.
     */
    public function has_warnings() {
        return !empty($this->warnings);
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
                return implode("<br/>\n", $this->log);
                break;
            }

            case 'errors': {
                return implode("<br/>\n", $this->errors);
                break;
            }

            case 'warnings': {
                return implode("<br/>\n", $this->warnings);
                break;
            }
        }
    }

    /**
     * Outputs a printable version describing the stack content.
     */
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
        return implode("<br/>\n", $this->errors);
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