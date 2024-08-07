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

/*
 * The echo command just print some literal or variables in log.
 *
 */
namespace local_moodlescript\engine;

defined('MOODLE_INTERNAL') || die();

class command_echo extends tokenizer {

    /*
     * Add keyword needs find what to print in log
     */
    public function parse() {
        $this->trace('   Start parse');

        $handler = new handle_echo();
        $context = new \StdClass;
        $context->argument = $this->resolve_variables($this->remainder);

        $this->trace('   End parse ++');
        return array($handler, $context);
    }

}