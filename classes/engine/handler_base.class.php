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
        $this->dynamiccheckstatus = -1;
    }

    /**
     * Executes the handler action.
     * @param object $result provides the previous result object of the handler chain.
     * @param objectref &$context the context for this handler execution instance
     * @param objectref &$logger a string array where to log any output
     */
    abstract function execute(&$results, &$context, &$stack);

    /**
     * Checks the validity of input context. Will fill the internal errorlog for the caller.
     * This might be used in a "check chain" when updating a script.
     * @param objectref &$context the context for this handler execution instance
     * @param objectref &$stack the surrounding stack.
     */
    abstract function check(&$context, &$stack);

    /*
     * The following functions are proxies for givong logging to the upper stack engine.
     */

    /**
     * the error log should report blocking errors.
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

        if (empty($attrmap)) {
            return;
        }

        foreach ($attrmap as $key => $desc) {

            if (!is_array($desc)) {
                throw new \moodle_exception('check_context_attributes expects a description, not a scalar mapping');
            }

            if ($desc['required']) {
                if (!array_key_exists($key, $this->context->params)) {
                    $this->error("Required attribute $key not provided in input");
                }
            }
        }

        if (!empty($this->context->params)) {
            foreach ($this->context->params as $key => $value) {
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
}