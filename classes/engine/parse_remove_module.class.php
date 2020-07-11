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

class parse_remove_module extends tokenizer {

    public static $samples;

    public function __construct($remainder, &$parser) {
        parent::__construct($remainder, $parser);
        self::$samples = "REMOVE MODULE \"idnumber:<modidnumber>\" [IF EXISTS]\n\n";
        self::$samples = "REMOVE MODULE \"id:<coursemoduleid>\" [IF EXISTS]\n\n";
        self::$samples = "REMOVE MODULE \"instance:<modname>:<instanceid>\" [IF EXISTS]\n\n";
    }

    /*
     * Add keyword needs find what to add in the remainder
     */
    public function parse() {
        $this->trace('...Start parse remove module '.$this->remainder);

        $pattern = '/^';
        $pattern .= tokenizer::TOKEN.tokenizer::SP.tokenizer::QUOTED_EXT_IDENTIFIER.tokenizer::OPT_SP;
        $pattern .= '(IF EXISTS)?'.tokenizer::OPT_SP;
        $pattern .= '$/';

        if (preg_match($pattern, $this->remainder, $matches)) {

            $handler = new \local_moodlescript\engine\handle_remove_module();
            $context = new StdClass;

            $target = $matches[2];
            if ($target == 'current') {
                $context->moduleid = $target;
            } else {
                if (preg_match('/^(idnumber\\:|id\\:)/', $target)) {
                    $identifier = new \local_moodlescript\engine\parse_identifier('course_modules', $this->logger);
                    $context->moduleid = $identifier->parse($target);
                } else if (preg_match('/^instance\\:(.*?):(.*?)$/', $target, $matches1)) {
                    // No parser is needed here. This is an instance primary ID.
                    $context->modulename = $matches1[1];
                    $context->instanceid = $matches1[2];
                }
            }

            if (!empty($matches[3])) {
                $context->ifexists = true; // Will not return any error if does not exist.
            }

            $this->trace('...End parse ++');
            return array($handler, $context);
        } else {
            $this->trace('...End parse --');
            return [null, null];
        }
    }

}