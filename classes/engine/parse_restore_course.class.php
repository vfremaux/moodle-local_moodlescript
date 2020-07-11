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

class parse_restore_course extends tokenizer {

    public static $samples;

    public function __construct($remainder, &$parser) {
        parent::__construct($remainder, $parser);
        self::$samples = "RESTORE COURSE <courseidentifier> IN <categoryidentifier> FROM <backupsource> HAVING\n";
        self::$samples = "shortname: <shortname>\n";
        self::$samples = "fullname: <fullname>\n";
        self::$samples = "idnumber: <idnumber>\n";
        self::$samples = "summary: <summary>\n";
        self::$samples = "visible: <visible>\n";
        self::$samples = "order: last|first|after <courseidentifier>\n";
        self::$samples = "\nRESTORE COURSE FROM FILE <filepath> IN <categoryidentifier> HAVING\n";
        self::$samples = "shortname: <shortname>\n";
        self::$samples = "fullname: <fullname>\n";
        self::$samples = "idnumber: <idnumber>\n";
        self::$samples = "summary: <summary>\n";
        self::$samples = "visible: <visible>\n";
        self::$samples = "order: last|first|after <courseidentifier>\n";
    }

    /*
     * Add keyword needs find what to add in the remainder
     */
    public function parse() {
        $this->trace('...Start parse '.$this->remainder);

        $pattern1 = '/^';
        $pattern1 .= tokenizer::QUOTED_EXT_IDENTIFIER.tokenizer::SP;
        $pattern1 .= 'IN'.tokenizer::SP.tokenizer::QUOTED_EXT_IDENTIFIER.tokenizer::OPT_SP;
        $pattern1 .= '(FROM)?'.tokenizer::OPT_SP.tokenizer::OPT_TOKEN.tokenizer::OPT_SP;
        $pattern1 .= '(HAVING)?'.tokenizer::OPT_SP;
        $pattern1 .= '$/';

        $pattern2 = '/^FROM FILE';
        $pattern2 .= tokenizer::SP.tokenizer::QUOTED_EXT_IDENTIFIER.tokenizer::SP;
        $pattern2 .= 'IN'.tokenizer::SP.tokenizer::QUOTED_EXT_IDENTIFIER.tokenizer::OPT_SP;
        $pattern2 .= '(HAVING)?'.tokenizer::OPT_SP;
        $pattern2 .= '$/';

        if (preg_match($pattern1, trim($this->remainder), $matches)) {

            $handler = new handle_restore_course();
            $context = new StdClass;

            // Template course.
            if ($matches[1] != 'current') {
                $identifier = new parse_identifier('course', $this->logger);
                $context->restorecourseid = $identifier->parse($matches[1]);
            } else {
                $context->restorecourseid = 'current';
            }

            // Target category.
            if ($matches[2] != 'current') {
                $identifier = new parse_identifier('course_categories', $this->logger);
                $context->restorecategoryid = $identifier->parse($matches[2]);
            } else {
                $context->restorecategoryid = 'current';
            }

            if (!empty($matches[3])) {
                $context->source = $matches[3];
            } else {
                $context->source = 'course';
            }

            $this->parse_having(@$matches[4], $context);

            $this->trace('...End parse ++');
            return array($handler, $context);
        } else if (preg_match($pattern2, trim($this->remainder), $matches)) {

            $handler = new handle_restore_course();
            $context = new StdClass;

            $context->filepath = $matches[1];

            // Target category.
            if ($matches[2] != 'current') {
                $identifier = new parse_identifier('course_categories', $this->logger);
                $context->restorecategoryid = $identifier->parse($matches[2]);
            } else {
                $context->restorecategoryid = 'current';
            }

            $this->parse_having(@$matches[3], $context);

        } else {
            $this->trace('...End parse --');
            return [null, null];
        }
    }

}