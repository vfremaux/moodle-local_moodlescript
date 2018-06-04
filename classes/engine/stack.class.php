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

use \stdclass;

/**
 * implements a posthandler stack to be executed after a deployment.
 *
 * the stack can register some handlers and execute the handler sequence
 */
class stack {

    protected $stack;

    protected $contexts;

    protected $log;
    protected $errors;
    protected $warnings;

    protected $haserrors;

    public function __construct() {
        $this->stack = array();
        $this->log = array();
        $this->errors = array();
        $this->warnings = array();
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
        $this->logger = [];
    }

    /**
     * Processes all the stack in order propagating a result object;
     */
    public function execute($globalcontext = null) {
        global $CFG;

        if (function_exists('debug_trace')) {
            if ($CFG->debug == DEBUG_DEVELOPER) {
                debug_trace("Start stack.");
            }
        }

        $result = null;
        if (!empty($this->stack)) {
            foreach ($this->stack as $handler) {
                $context = array_shift($this->contexts);
                if (!empty($globalcontext)) {
                    // Add/override with global context.
                    foreach ($globalcontext as $key => $value) {
                        $context->$key = $value;
                    }
                }
                if (function_exists('debug_trace')) {
                    if ($CFG->debug == DEBUG_DEVELOPER) {
                        debug_trace("Executing ".get_class($handler));
                    }
                }
                $result = $handler->execute($result, $context, $this);
            }
        }
        return $result;
    }

    /**
     * Processes all the stack in order propagating a result object;
     */
    public function check($globalcontext = null) {
        global $CFG;

        if (function_exists('debug_trace')) {
            if ($CFG->debug == DEBUG_DEVELOPER) {
                debug_trace("Start stack check.");
            }
        }

        if (!empty($this->stack)) {
            $i = 0;

            foreach ($this->stack as $handler) {
                $context = $this->contexts[$i];
                if (!empty($globalcontext)) {
                    // Add/override with global context.
                    foreach ($globalcontext as $key => $value) {
                        $context->$key = $value;
                    }
                }
                // Collects all possible check results in errorlog.
                if (function_exists('debug_trace')) {
                    if ($CFG->debug == DEBUG_DEVELOPER) {
                        debug_trace("Checking ".get_class($handler));
                    }
                }
                $handler->check($context, $this);
                $i++;
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
                $i ++;
            }
        } else {
            return 'Empty stack.';
        }

        return $str;
    }

    public function print_errors() {
        return implode("\n", $this->errors);
    }
}