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
 * @see course format page extension.
 * @author Valery Fremaux (valery.fremaux@gmail.com)
 * @copyright (c) 2017 onwards Valery Fremaux (http://www.mylearningfactory.com)
 */
namespace local_moodlescript\engine;

defined('MOODLE_INTERNAL') || die;

use \StdClass;

class parse_add_coursepage extends tokenizer {

    public static $samples;

    public function __construct($remainder, &$parser) {
        parent::__construct($remainder, $parser);
        self::$samples = "ADD COURSEPAGE \"<page name>\" TO idnumber:<courseidnum> [AT START|AT END|[AFTER|BEFORE idnumber:<coursepageidnum>]] HAVING\n";
        self::$samples .= "idnumber: <coursepageidnum>\n";
        self::$samples .= "nametwo: <coursepageidnum>\n";
        self::$samples .= "displaymenu: 1\n";
        self::$samples .= "display: PUBLIC|ENROLLED|HIDDEN|DEEPHIDDEN|PROTECTED\n";
        self::$samples .= "...\n\n";

        self::$samples = "ADD COURSEPAGE \"<page name>\" TO PAGE idnumber:<coursepageidnum> [AT START|AT END|[AFTER|BEFORE idnumber:<coursepageidnum>]] HAVING\n";
        self::$samples .= "idnumber: <coursepageidnum>\n";
        self::$samples .= "nametwo: <coursepageidnum>\n";
        self::$samples .= "display: PUBLIC|PUBLISHED|HIDDEN|PROTECTED|DEEPHIDDEN\n\n";
    }

    /*
     * Add keyword needs find what to add in the remainder
     */
    public function parse() {

        $this->trace('Start parse add course '.$this->remainder);

        $pattern = '/^';
        $pattern .= tokenizer::QUOTED_EXT_LITTERAL.tokenizer::SP; // Page fullname (nameone).
        $pattern .= 'TO ?(PAGE)?'.tokenizer::SP.tokenizer::QUOTED_EXT_IDENTIFIER.tokenizer::OPT_SP; // Course or coursepage identifier
        $pattern .= '(AT START|AT END|AFTER|BEFORE)?'.tokenizer::OPT_SP.tokenizer::OPT_QUOTED_EXT_IDENTIFIER.tokenizer::OPT_SP; // rel location identifier
        $pattern .= '(HAVING)?'.tokenizer::OPT_SP; // Course other options.
        $pattern .= '$/';

        if (preg_match($pattern, $this->remainder, $matches)) {

            $handler = new \local_moodlescript\engine\handle_add_coursepage();
            $context = new StdClass;

            $context->fullname = $matches[1];
            $context->fullname = preg_replace('/^[\\\'"]|[\\\'"]$/', '', $context->fullname); // Unquote.

            $parenttype = $matches[2]; // course if empty or PAGE.

            $parent = $matches[3];
            if (empty($parenttype)) {
                $identifier = new \local_moodlescript\engine\parse_identifier('course', $this->logger);
                if ($parent == 'current') {
                    $context->addcourseid = $parent;
                } else {
                    $context->addcourseid = $identifier->parse($parent, 'sortname');
                }
            } else {
                // parent type is a format page coursepage. (adding a child page).
                $identifier = new \local_moodlescript\engine\parse_identifier('course', $this);
                if ($parent == 'current') {
                    $context->addparentid = $parent;
                } else {
                    $context->addparentid = $identifier->parse($parent, 'shortname');
                }
            }

            if (!empty($matches[4])) {
                $context->location = $matches[4];
                $context->locationid = $matches[5];
                if (in_array($context->location, ['AFTER', 'BEFORE']) && empty($context->locationid)) {
                    $this->trace('...End parse --');
                    return array($handler, $context);
                }
            } else {
                $context->location = "AT END";
            }

            $this->parse_having(@$matches[6], $context);

            $this->trace('...End parse ++');
            return array($handler, $context);
        } else {
            $this->error("Add Coursepage Parse Error : No syntax match ");
            $this->trace('...End parse --');
            return [null, null];
        }
    }

}