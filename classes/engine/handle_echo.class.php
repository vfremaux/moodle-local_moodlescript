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

    public function execute(&$results, &$context, &$stack) {

        $this->stack = $stack;

        if ($context->argument == 'GLOBALS') {
            $str = "GLOBALS:\n";
            foreach ($context as $key => $value) {
                $str .= "    $key: $value\n";
            }
            $str .= "\n";
            $this->log($str);
            return;
        }

        $this->log($context->argument);
        return true;
    }

    public function check(&$context, &$stack) {

        $this->stack = $stack;

        if (empty($context->argument)) {
            $this->error('Nothing to print');
            return;
        }
        $this->log('Echoed');
    }
}