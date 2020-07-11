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

use \StdClass;

class parse_add_module extends tokenizer {

    public static $samples;

    public function __construct($remainder, &$parser) {
        parent::__construct($remainder, $parser);
        self::$samples = "ADD MODULE \"<modulename>\" TO \"<course name>\" IN SECTION \"section:<sectionnum>\" HAVING\n";
        self::$samples .= "name: Some module name\n";
        self::$samples .= "idnumber: <modidnumber>\n";
        self::$samples .= "<instanceattr1>: <attrvalue1>\n";
        self::$samples .= "<instanceattr2>: <attrvalue2>\n";
        self::$samples .= "...\n";
        self::$samples .= "visible: 0|1\n\n";

        self::$samples = "ADD MODULE \"<modulename>\" TO \"<course name>\" IN PAGE \"id:<pageid>\" HAVING\n";
        self::$samples .= "name: Some module name\n";
        self::$samples .= "idnumber: <modidnumber>\n";
        self::$samples .= "visible: 0|1\n\n";
    }

    /*
     * Add keyword needs find what to add in the remainder
     */
    public function parse() {
        $this->trace('...Start parse add module ');

        $pattern = '/^';
        $pattern .= tokenizer::TOKEN.tokenizer::SP;
        $pattern .= 'TO'.tokenizer::SP.tokenizer::QUOTED_EXT_IDENTIFIER.tokenizer::SP; // Course identifier
        $pattern .= '(IN SECTION|IN PAGE)'.tokenizer::OPT_SP.tokenizer::SP.tokenizer::QUOTED_EXT_IDENTIFIER.tokenizer::OPT_SP; // Section or page identidier
        $pattern .= '(HAVING)?'.tokenizer::OPT_SP;
        $pattern .= '$/';

        if (preg_match($pattern, $this->remainder, $matches)) {

            $handler = new \local_moodlescript\engine\handle_add_module();
            $context = new StdClass;
            $context->modtype = $matches[1];

            $targetcourse = $matches[2];
            $identifier = new \local_moodlescript\engine\parse_identifier('course', $this->logger);
            if ($targetcourse == 'current') {
                $context->modcourseid = $targetcourse;
            } else {
                $context->modcourseid = $identifier->parse($targetcourse, $this->logger);
            }

            $targettype = $matches[3];
            if ($targettype == 'IN SECTION') {
                $context->targettype = 'section';
            } else {
                $context->targettype = 'coursepage';
            }

            $target = $matches[4];
            if ($target != 'current') {
                if ($targettype == 'section') {
                    $identifier = new \local_moodlescript\engine\parse_identifier('course_sections', $this->logger);
                    $context->modsectionid = $identifier->parse($target, $this->logger);
                } else {
                    $identifier = new \local_moodlescript\engine\parse_identifier('format_page', $this->logger);
                    $context->modpageid = $identifier->parse($target, $this->logger);
                }
            } else {
                if ($targettype == 'section') {
                    $this->error("Add Module Parse Error : Cannot use 'current' keyword in section ");
                    $this->trace('...End parse --');
                    return [null, null];
                } else {
                    $context->modpageid = 'current';
                }
            }

            $this->parse_having(@$matches[5], $context);

            $this->trace('...End parse ++');
            return array($handler, $context);
        } else {
            $this->error("Add Module Parse Error : No syntax match ");
            $this->trace('...End parse --');
            return [null, null];
        }
    }

}