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

defined('MOODLE_INTERNAL') || die;

abstract class handler {

    /**
     * A set of all the variables required by the execution handler, and global state of the stack
     * context variables.
     */
    protected $context;

    /**
     * The surrounding exectution stack.
     */
    protected $stack;

    protected $statement;

    /**
     * Dynamic check status tracks if the dynamic part of the checks have been 
     * already executed (at check time) or not. If dynamic_check() is implemented and
     * is called at check time, checks completion should result in 0 in the status, and
     * the error stack will be empty.
     */
    public $dynamiccheckstatus;

    public function __construct($statement = '') {
        $this->statement = $statement;
        $this->dynamiccheckstatus = -1; // experimental
    }

    /**
     * Executes the handler action.
     * @param object &$results provides the previous result object of the handler chain by ref.
     * @param objectref &$stack the surrounding stack.
     */
    abstract function execute(&$results, &$stack);

    /**
     * Checks the validity of input context. Will fill the internal errorlog for the caller.
     * This might be used in a "check chain" when updating a script. Context will only be checked to ensure
     * execution is possible. It MUST NOT be altered by the check function.
     * @param objectref &$stack the stack to execute.
     */
    abstract function check(&$stack);

    /**
     * All the checks that in some situation can only be done at execution time, usually
     * because they are depending on dynamic setup of the execution context. Dynammic checks
     * may though be called at check time, if the dynamic condition can be resolved and the
     * whole check stack can be performed.
     * Dynamic checks will mark a dynamiccheck status in the instance so execution phase can
     * know it has been completed.
     * @param objectref &$stack the current stack object
     * @param object $resolved some objects issued from static checking that are needed by
     * the dynamic resolution to complete checks. Content of this object is an agreement between
     * check() and dynamic_check()
     */
    public function dynamic_check(&$stack, $resolved, $isruntime = false) {
        return;
    }

    /*
     * The following functions are proxies for giving logging to the upper stack engine.
     */

    /**
     * The error log should report blocking errors. Execution stops on first error.
     */
    public function error($errormsg) {
        $this->stack->error($errormsg);
    }

    /**
     * the error log should report non blocking warnings.
     */
    public function warn($warningmsg) {
        $this->stack->warn($warningmsg);
    }

    /**
     * the log should report positive succeeded actions.
     */
    public function log($msg) {

        if (empty($this->stack)) {
            // Handler is executed directly. E.g. unit tests.
            $this->stack = new stack();
            $this->stack->register($this, $this->context);
        }

        $this->stack->log($msg);
    }

    /**
     * Given an attribute mapping description, checks that there are no unsupported
     * attributes. Attribute inputs is usually given by a HAVING syntax. Attribute mapping
     * usually comes from a plugin API or other descriptor locations if available.
     * @param array $attrmap the attribute descriptor.
     * @return void (but feeds $this->error if errors).
     */
    public function check_context_attributes($attrmap) {

        $context = $this->stack->get_current_context();

        if (empty($attrmap)) {
            return;
        }

        foreach ($attrmap as $key => $desc) {

            if (!is_array($desc)) {
                // Malformed attribute map.
                throw new \moodle_exception('check_context_attributes expects a description, not a scalar mapping');
            }

            if (!empty($desc['required'])) {
                if (!array_key_exists($key, $context->params)) {
                    $this->error("Required attribute $key not provided in input (additional params)");
                }
            }
        }

        // Checks there are no unepexted params in input.
        if (!empty($context->params)) {
            foreach ($context->params as $key => $value) {
                if (!in_array($key, array_keys($attrmap))) {
                    $this->error("Attribute $key not supported in input");
                }
            }
        }
    }

    /**
     * Checks if a value is a runtime expression.
     * @param string $identifier
     * @return boolean
     */
    protected function is_runtime($identifier) {
        return \core_text::strpos($identifier, 'runtime:') === 0;
    }

    /**
     * Searches and replace variable expressions in a string, taking values from context.
     * Searches for remaining "runtime" variables that could not be eliminated at parsetime.
     * @param string $str The input string.
     * @param object $context The context to get vars from.
     */
    protected function resolve_variables($str) {

        $config = get_config('local_moodlescript');
        $context = $this->stack->get_current_context();

        // First search for maps.
        if (preg_match_all('/'.tokenizer::RUNTIMECTXMAPVAR.'/', $str, $matches)) {
            for ($i = 0; $i < count($matches[0]); $i++) {
                $full = $matches[0][$i];
                $mapname = $matches[1][$i];
                $keyname = $matches[2][$i];

                $replaced = false;
                if (isset($context->$mapname)) {
                    if (array_key_exists($keyname, $context->$mapname)) {
                        $str = str_replace($full, $context->$mapname[$keyname], $str);
                        $replaced = true;
                    }
                }

                if (!$replaced) {
                    switch ($config->missingvariableoutput) {
                        case 'blank' : 
                            $str = str_replace($full, '', $str);
                            break;
                        case 'signalled' : 
                            $str = str_replace($full, '{runtimemissing[]}', $str);
                            break;
                        case 'ignored' : 
                            break;
                    }
                }
            }
        }

        // Next search for simple vars.
        if (preg_match_all('/'.tokenizer::RUNTIMECTXVAR.'/', $str, $matches)) {
            for ($i = 0; $i < count($matches[0]); $i++) {
                $full = $matches[0][$i];
                $varname = $matches[1][$i];

                $replaced = false;
                if (isset($context->$varname)) {
                    $str = str_replace($full, $context->$varname, $str);
                    $replaced = true;
                }

                if (!$replaced) {
                    switch ($config->missingvariableoutput) {
                        case 'blank' : 
                            $str = str_replace($full, '', $str);
                            break;
                        case 'signalled' : 
                            $str = str_replace($full, '{runtimemissing}', $str);
                            break;
                        case 'ignored' : 
                            break;
                    }
                }
            }
        }
        return $str;
    }
}