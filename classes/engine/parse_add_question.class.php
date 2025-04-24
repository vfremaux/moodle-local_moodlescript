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
 * Add an existing question to a quiz, or add some addable questiontypes (randoms)
 * @package local_moodlescript
 * @author Valery Fremaux (valery.fremaux@gmail.com)
 * @copyright (c) 2017 onwards Valery Fremaux (http://www.mylearningfactory.com)
 */
namespace local_moodlescript\engine;

defined('MOODLE_INTERNAL') || die;

use \StdClass;

class parse_add_question extends tokenizer {

    public static $samples;

    public function __construct($remainder, &$parser) {
        parent::__construct($remainder, $parser);
        self::$samples = "ADD QUESTION idnumber:\"<questionidnumber>\" TO idnumber:<coursemoduleidnumber> [AT START|AT END|[AT <slotnum>]] [ITERATE <n>] HAVING\n";
        self::$samples .= "hidden: false\n\n";
        self::$samples = "ADD QUESTION idnumber:\"<questionidnumber>\" TO id:<coursemoduleid> [AT START|AT END|[AT <slotnum>]] [ITERATE <n>]\n\n";
        self::$samples = "ADD QUESTION idnumber:\"<questionidnumber>\" TO id:<courseoduleid> [AT START|AT END|[AT <slotnum>]] [ITERATE <n>]\n\n";
        self::$samples = "ADD QUESTION type:randomconstrained TO quiz:id:<quizid> [AT START|AT END|[AT <slotnum>]] [ITERATE <n>]\n\n";
        self::$samples = "ADD QUESTION type:random ON idnumber:<questioncategoryidnumber> TO id:<quizid> [AT START|AT END|[AT <slotnum>]] [ITERATE <n>]\n\n";
    }

    /*
     * Add keyword needs find what to add in the remainder
     */
    public function parse() {

        $this->trace('Start parse add question '.$this->remainder);

        $pattern = '/^';
        $pattern .= tokenizer::QUOTED_EXT_IDENTIFIER.tokenizer::SP; // Question identifier or type selector (for random).
        $pattern .= '(ON)?'.tokenizer::OPT_SP.tokenizer::OPT_QUOTED_EXT_IDENTIFIER.tokenizer::OPT_SP; // Target question category for random types
        $pattern .= 'TO'.tokenizer::SP.tokenizer::QUOTED_EXT_IDENTIFIER.tokenizer::OPT_SP; // Course or coursepage identifier
        $pattern .= '(AT START|AT END|AFTER|BEFORE)?'.tokenizer::OPT_SP.tokenizer::OPT_QUOTED_EXT_IDENTIFIER.tokenizer::OPT_SP; // rel location identifier
        $pattern .= '(ITERATE)?'.tokenizer::OPT_SP.tokenizer::OPT_INTNUMBER.tokenizer::OPT_SP; // Iteration round count
        $pattern .= '(HAVING)?'.tokenizer::OPT_SP; // Course other options.
        $pattern .= '$/';

        if (preg_match($pattern, $this->remainder, $matches)) {

            $handler = new \local_moodlescript\engine\handle_add_question();
            $context = new StdClass;

            if (strpos($matches[1], 'type:') === false) {
                // this is a question identifier.
                $identifier = new \local_moodlescript\engine\parse_identifier('question', $this->logger);
                $context->questionid = $identifier->parse($matches[1], 'idnumber');
            } else {
                // We have a type call. This is for random questions that only need a new random question instance to be generated.
                $context->questiontype = str_replace('type:', '', $matches[1]);
            }

            if (!empty($context->questiontype) && $context->questiontype == 'random') {
                $on = $matches[2];
                if (empty($on)) {
                    $this->error("Random type questions need focussing a target category using ON keyword");
                    $this->trace('...End parse --');
                    return array(null, null);
                }
                $randomtargetcategory = $matches[3]; // On target category
                if (empty($randomtargetcategory)) {
                    $this->error("Random type questions need having a target category for ON clause");
                    $this->trace('...End parse --');
                    return array(null, null);
                }
                $identifier = new \local_moodlescript\engine\parse_identifier('question_categories', $this->logger);
                $context->randomtargetcategoryid = $identifier->parse($randomtargetcategory, 'idnumber');
            }

            // Some other randomtype (Randomconstrained) will not need any further attribute.

            $coursemodule = $matches[4];
            if ($coursemodule == 'current') {
                $context->addcoursemoduleid = $coursemodule;
            } else if (preg_match('/^(runtime\\:)?idnumber\\:|(runtime\\:)?id\\:/', $coursemodule)) {
                // In this case this is a standard instance identifier on a course module.
                $identifier = new \local_moodlescript\engine\parse_identifier('course_modules', $this->logger);
                if ($coursemodule == 'current') {
                    $context->addcoursemoduleid = $coursemodule;
                } else {
                    $context->addcoursemoduleid = $identifier->parse($coursemodule, 'idnumber');
                }
            } else if (preg_match('/^([^:]+?):(.*)/', $coursemodule, $matches1)) {
                $context->quizmodulename = $matches1[1];
                $context->quizidentifier = $matches1[2];
            }

            if (!empty($matches[5])) {
                $context->location = $matches[5];
                $context->slotid = $matches[6];
                if (in_array($context->location, ['AFTER', 'BEFORE']) && empty($context->slotid)) {
                    $this->error("AFTER/BEFORE need a target slotid");
                    $this->trace('...End parse --');
                    return array(null, null);
                }
            } else {
                $context->location = "AT END";
            }

            if (!empty($matches[7])) {
                $context->iterate = $matches[8];
                if (empty($context->iterate)) {
                    $this->error("Iterate needs a loop count");
                    $this->trace('...End parse --');
                    return array(null, null);
                }
            } else {
                $context->iterate = 1;
            }

            $this->parse_having(@$matches[9], $context);

            $this->trace('...End parse ++');
            return array($handler, $context);
        } else {
            $this->error("Add Question Parse Error : No syntax match ");
            $this->trace('...End parse --');
            return [null, null];
        }
    }

}