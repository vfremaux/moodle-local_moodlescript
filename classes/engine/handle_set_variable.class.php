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
 * A command to build a context value named with a token.
 * Named variables can be used as :varname in expressions.
 *
 * @package local_moodlescript
 * @category local
 * @author Valery Fremaux (valery.fremaux@gmail.com)
 * @copyright (c) 2017 onwards Valery Fremaux (http://www.mylearningfactory.com)
 */
namespace local_moodlescript\engine;

defined('MOODLE_INTERNAL') || die;

class handle_set_variable extends handler {

    public function execute(&$results, &$stack) {

        // This is essentially a runtime operation.

        $this->stack = $stack;
        $context = $this->stack->get_current_context();

        $tovariable = $context->tovariable;
        if (preg_match('/^\\:(\w+)$/', $context->fromvariable, $matches)) {
            // Variable copy.
            $fromvariable = $matches[1];
            $context->$tovariable = @$context->$fromvariable;
        } else {
            // Litteral after replacement of all subvars after stripping eventual starting and ending quotes.
            $context->fromvariable = preg_replace('/^"/', '', $context->fromvariable);
            $context->fromvariable = preg_replace('/"$/', '', $context->fromvariable);
            $result = $this->resolve_variables($context->fromvariable);
            $context->$tovariable = $result;
        }
    }

    /**
     * Remind that Check MUST NOT alter the context. Just execute any pre-execution tests that might 
     * be necessary.
     * @param $array &$stack the script stack.
     */
    public function check(&$stack) {

        $this->stack = $stack;

        // This is essentially a runtime operation. It always results.
        return true;
    }
}