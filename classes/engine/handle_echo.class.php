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

class handle_echo extends handler {

    public function execute(&$results, &$stack) {

        $this->stack = $stack;
        $context = $this->stack->get_current_context();

        if ($context->argument == 'GLOBALS') {
            $str = "GLOBALS:\n";
            foreach ($context as $key => $value) {
                $str .= "    $key: ".$this->resolve_variables($value)."\n";
            }
            $str .= "\n";
            $this->log($str);
            return;
        }

        $this->log($this->resolve_variables($context->argument));
        return true;
    }

    /**
     * Remind that Check MUST NOT alter the context. Just execute any pre-execution tests that might 
     * be necessary.
     * @param $array &$stack the script stack.
     */
    public function check(&$stack) {

        $this->stack = $stack;
        $context = $this->stack->get_current_context();

        if (empty($context->argument)) {
            $this->error('Nothing to print');
            return;
        }
        // $this->log('Check : Echoed');
    }
}