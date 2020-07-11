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

use \stdClass;
use \moodle_exception;

class handle_add_question extends handler {

    public function execute(&$results, &$context, &$stack) {
        global $DB;

        $this->stack = $stack;

        if (empty($context->addcoursemoduleid)) {
            // course module id could not be resolved neither at parse or check step.
            if ($this->is_runtime($context->quizidentifier)) {
                if (preg_match('/^runtime\\:idnumber|runtime\\:id/', $context->addcoursemoduleid)) {
                    $identifier = new parse_identifier('course_modules', $this);
                    $context->addcoursemoduleid = $identifier->parse('course_modules', 'id');
                    $errorcpl(" by course module ");
                } else {
                    $identifier = new parse_identifier($context->quizmodulename, $this->logger);
                    $quizid = $identifier->parse($context->quizidentifier, 'idnumber', 'runtime');
                    $moduleid = $DB->get_field('modules', 'id', ['name' => $quizmodulename]);
                    $params = ['module' => $moduleid, 'instance' => $quizid];
                    $context->addcoursemoduleid = $DB->get_field('course_modules', 'id', $params);
                    $errorcpl(" by instance of {$context->quizmodulename}");
                }

                if (!$DB->record_exists('course_modules', ['id' => $context->addcoursemoduleid])) {
                    throw new moodle_exception("Add Question Runtime : Unkown course module ".$errorcpl);
                }
            }
        } else if ($this->is_runtime($context->addcoursemoduleid)) {
        } else if ($context->addcoursemoduleid == 'current') {
            $context->addcoursemoduleid = $context->cmid;
        }

        $params = ['id' => $context->addcoursemoduleid];
        $cm = $DB->get_record('course_modules', $params);

        if (!$cm) {
            throw new moodle_exception("");
        }

        $params = ['id' => $cm->module];
        $module = $DB->get_record('modules', $params);

        $params = ['id' => $cm->instance];
        $instance = $DB->get_record($module->name, $params);

        if (empty($context->params->onpage)) {
            $context->params->onpage = 0;
        }

        if (empty($context->params->includesubcategories)) {
            $context->params->includesubcategories = 0;
        }

        if (!empty($context->questionid)) {
            // We have an explicit question to add.
            
        } else {
            if (!empty($context->questiontype)) {
                $quiz = $cm;
                $quiz->modname = $module->name;
                if ($context->questiontype == 'randomconstained') {
                    include_once($CFG->dirroot.'/mod/quiz/accessrule/chooseconstraints/lib.php');
                    quiz_add_randomconstrained_questions($quiz, 0 + @$context->params->onpage, $context->iterate);
                } else if ($context->questiontype == 'random') {
                    include_once($CFG->dirroot.'/mod/quiz/locallib.php');
                    quiz_add_random_questions($quiz, $context->params->onpage, $context->randomtargetcategoryid,
                            $context->iterate, $context->params->includesubcategories, '');
                }
            }
        }

    }

    public function check(&$context, &$stack) {
        global $DB, $CFG;

        $this->stack = $stack;

        $errorcpl = '';

        if (empty($context->addcoursemoduleid)) {

            // Get target quiz course module by instance.
            if (empty($context->quizmodulename)) {
                $this->error("Check Add Question : Quiz type not defined");
            }

            if (!$this->is_runtime($context->quizidentifier)) {
                $identifier = new parse_identifier($context->quizmodulename, $this);
                $quizid = $identifier->parse($context->quizidentifier, 'id');
                $moduleid = $DB->get_field('modules', 'id', ['name' => $quizmodulename]);
                $params = ['module' => $moduleid, 'instance' => $quizid];
                $context->addcoursemoduleid = $DB->get_field('course_modules', 'id', $params);
                $errorcpl(" by instance of {$context->quizmodulename}");

                if (!$DB->record_exists('course_modules', ['id' => $context->addcoursemoduleid])) {
                    $this->error("Check add question : Unkown course module  (by instance)".$errorcpl);
                }
            }
        } else {
            if (!$this->is_runtime($context->addcoursemoduleid)) {
                if (preg_match('/^idnumber\\:|id\\:/', $context->addcoursemoduleid)) {
                    $identifier = new parse_identifier('course_modules', $this);
                    $context->addcoursemoduleid = $identifier->parse('course_modules', 'id');

                    if (!$DB->record_exists('course_modules', ['id' => $context->addcoursemoduleid])) {
                        $this->error("Check Add Question : Unkown course module (by course module)".$errorcpl);
                    }
                }
            }
        }

        if (!empty($context->questiontype)) {
            if ($context->questiontype == 'random') {
                if (empty($context->randomtargetcategoryid)) {
                    $this->error("Check Add Question : Adding a random question must point a target category");
                }
                if (!$this->is_runtime($context->randomtargetcategoryid)) {
                    if (!$DB->record_exists('question_categories', ['id' => $context->randomtargetcategoryid])) {
                        $this->error("Check Add Question : Random question target category {$context->randomtargetcategoryid} does not exists");
                    }
                }
            }
            if ($context->questiontype == 'randomconstrained') {
                if (!is_dir($CFG->dirroot.'/mod/quiz/accessrule/chooseconstraints')) {
                    $this->error("Check Add Question : Random constraints access rule is not installed");
                }
            }
        }

    }
}