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
 * A class that parses and evaluates a test condition syntax in a buffer input.
 *
 * A test condition is build with the algorithm :
 *
 * @see defines in tokenizer.class.php
 *
 * TESTCOND_EXPR :== OPT_ULOGICAL_OPRATOR EVAL_EXPR OPT_LOGICAL_OPERATOR OPT_EVAL_EXPR
 * OPT_LOGICAL_OPERATOR :== (AND|OR|XOR)?
 * OPT_ULOGICAL_OPERATOR :== (NOT)?
 * OPT_EVAL_EXPR :== EVAL_EXPR?
 * EVAL_EXPR :== VALUE_IDENTIFIER EVAL_OPERATOR LITTERAL|QUOTED_LITTERAL|VALUE_IDENFIFIER|OBJECT_IDENTIFIER
 * EVAL_OPERATOR :== '='|'!='|'>'|'>='|'<'|'<='|'~'|'!~|hasrolein|isenrolledin|isloggedin|hasloggedin|hascompleted|hasstarted|isincategory|isincattree|isinsubs|isempty|incohort'
 * OBJECT_IDENTIFIER :== (user|user_profile_field|course|cohort|category):(<identifierid>:<id>|current)
 * VALUE_IDENTIFIER :== OBJECT_IDENTIFIER:<attributename>
 *
 * @package local_moodlescript
 * @category local
 * @author Valery Fremaux (valery.fremaux@gmail.com)
 * @copyright (c) 2017 onwards Valery Fremaux (http://www.mylearningfactory.com)
 */
namespace local_moodlescript\engine;

use StdClass;
use core_course_category;
use context_site;
use context_course;
use context_coursecat;
use moodle_exception;

defined('MOODLE_INTERNAL') || die();

class evaluable_expression extends tokenizer {

    /**
     * Some operator descriptors that are used more than once.
     */
    protected static $evaloperatormodeltpl = [
        'arithmetic' => [
            'optype' => 'arithmetic',
            'type1' => 'valueref',
            'type2' => 'valueref|litteral'
        ],
        'usercoursecat' => [
            'optype' => 'func',
            'type1' => 'objectref<user>',
            'type2' => 'objectref<course>|objectref<category>'
        ],
    ];

    /**
     * Expression descriptions that can help parsing and checking
     * scriptlet consistency. Tells which kind of object is expected each
     * side of an operator.
     */
    protected static $evaloperatormodels;

    /**
     * A head object for evaluation stacks. Evaluation staks serve the 
     * evaluation / conversion algorithms for resolving an expression value.
     * Use : $this->stack->operands // stacks operand terms
     * Use : $this->stack->operators // stack operators
     */
    protected $stack;

    /**
     * An internal state status
     */
    protected $parsed;

    /**
     * Subparser constructor.
     */
    public function __construct($remainder, &$parser) {

        if (is_null(self::$evaloperatormodels)) {
            self::$evaloperatormodels = [
                '=' => self::$evaloperatormodeltpl['arithmetic'],
                '!=' => self::$evaloperatormodeltpl['arithmetic'],
                '~=' => self::$evaloperatormodeltpl['arithmetic'],
                '!~=' => self::$evaloperatormodeltpl['arithmetic'],
                '<' => self::$evaloperatormodeltpl['arithmetic'],
                '>' => self::$evaloperatormodeltpl['arithmetic'],
                '<=' => self::$evaloperatormodeltpl['arithmetic'],
                '>=' => self::$evaloperatormodeltpl['arithmetic'],
                'hasloggedin' => 'objectref<user> op objectref<course>|void', 
                'isenrolledin' => self::$evaloperatormodeltpl['usercoursecat'],
                'isloggedin' => self::$evaloperatormodeltpl['usercoursecat'],
                'hasrolein' => self::$evaloperatormodeltpl['usercoursecat'],
                'hasgradesin' => self::$evaloperatormodeltpl['usercoursecat'],
                'isincohort' => [
                    'optype' => 'func',
                    'type1' => 'objectref<user>',
                    'type2' => 'objectref<cohort>'
                ],
                'hasstarted' => [
                    'optype' => 'func',
                    'type1' => 'objectref<user>',
                    'type2' => 'objectref<course>'
                ],
                'hascompleted' => [
                    'optype' => 'func',
                    'type1' => 'objectref<user>',
                    'type2' => 'objectref<course>'
                ],
                'isincategory' => [
                    'optype' => 'func',
                    'type1' => 'objectref<course>',
                    'type2' => 'objectref<category>'
                ],
                'isempty' => [
                    'optype' => 'func',
                    'type1' => 'objectref<category>',
                    'type2' => 'void'
                 ],
                'isinsubs' => [
                    'optype' => 'func',
                    'type1' => 'objectref<course|category>',
                    'type2' => 'objectref<category>'
                ],
                'isingroup' => [
                    'optype' => 'func',
                    'type1' => 'objectref<user>',
                    'type2' => 'objectref<group>'
                ]
            ];
        }

        parent::__construct($remainder, $parser);
        $this->stack = new StdClass;
        $this->stack->operands = [];
        $this->stack->operators = [];
    }

    /**
     * Parses an evaluable expression. Expression Parser just checks syntax is correct and
     * well formed at 'parse' time. At runtime, resolves any resolvable
     * expression to prepare evaluation. It will progress recursively.
     * @param string $expression an evaluable expression.
     * @param string $step the execution step, as 'parse' or 'runtime'
     * @return string|int a numeric contextid or the original expression reprocessed for runtime.
     */
    public function parse() {
        global $DB, $CFG;
        static $level = 1;

        $this->trace('   Start parse remainder : '.$this->remainder);
        // Start patterns. Patterns are checked at start, remained will be recursively proposed to
        // subparser until nothing remains.
        if ($level == 1) {
            // When the second term is a pure litteral.
            $pattern1 = '/^'.tokenizer::OPT_ULOGICAL_OPERATOR.tokenizer::OPT_SP.tokenizer::QUOTED_EXT_IDENTIFIER.tokenizer::SP.tokenizer::EVAL_OPERATOR;
            $pattern1 .= tokenizer::SP.tokenizer::QUOTED_EXT_LITTERAL.tokenizer::OPT_SP.'/';

            $pattern2 = '/^'.tokenizer::OPT_ULOGICAL_OPERATOR.tokenizer::OPT_SP.tokenizer::QUOTED_EXT_IDENTIFIER.tokenizer::SP.tokenizer::EVAL_OPERATOR;
            $pattern2 .= tokenizer::SP.tokenizer::QUOTED_EXT_IDENTIFIER.tokenizer::OPT_SP.'/';
        } else {
            // When the second term is a pure litteral.
            $pattern1 = '/^'.tokenizer::LOGICAL_OPERATOR.tokenizer::SP.tokenizer::OPT_ULOGICAL_OPERATOR.tokenizer::OPT_SP.tokenizer::QUOTED_EXT_IDENTIFIER;
            $pattern1 .= tokenizer::SP.tokenizer::EVAL_OPERATOR.tokenizer::SP.tokenizer::QUOTED_EXT_LITTERAL.tokenizer::OPT_SP.'/';

            // We add a logical op at start.
            $pattern2 = '/^'.tokenizer::LOGICAL_OPERATOR.tokenizer::SP.tokenizer::OPT_ULOGICAL_OPERATOR.tokenizer::OPT_SP.tokenizer::QUOTED_EXT_IDENTIFIER;
            $pattern2 .= tokenizer::SP.tokenizer::EVAL_OPERATOR.tokenizer::SP.tokenizer::QUOTED_EXT_IDENTIFIER.tokenizer::OPT_SP.'/';
        }

        if (preg_match($pattern1, $this->remainder, $matches)) {
            // Case where we expect : value identifier to a litteral value.
            // Check terms and validity conditions.
            $this->parser->trace("Match P1/$level");

            // Store elements in stacks.
            $this->store_elements($matches, $level);

            // Check terms and validity conditions.
            // some operators DO NOT accept litteral values as argument
            $opix = 3;
            if ($level > 1) {
                $opix++;
            }
            if (!in_array($matches[$opix], array_keys(self::$evaloperatormodels))) {
                $this->trace('Expression error : Operation "'.$matches[$opix].'" cannot accept a litteral as argument. It should be an object reference.');
                return [null, null];
            }

            // Compute right part and triml it from any whitespaces.
            $this->remainder = trim(preg_replace($pattern1, '', $this->remainder));
            // If remainder not empty, stack a new expression parser
            if (!empty($this->remainder)) {
                $level++;
                // Use $this to continue parsing. 
                // $next = new evaluable_expression($this->remainder, $this->parser); // Dig further.
                // $return = $next->parse();
                $return = $this->parse();
                $level--;
                return $return;
            }
            // End of expression chain. Everything on the path is fine.
            return [true, true];
        } else if (preg_match($pattern2, $this->remainder, $matches)) {
            // Case where we expect : value identifier to another value identifier comparison/op
            $this->parser->trace("Match P2/$level");

            // Store elements in stacks.
            $this->store_elements($matches, $level);

            // Compute right part and triml it from any whitespaces.
            $this->remainder = trim(preg_replace($pattern2, '', $this->remainder));
            // If remainder not empty, stack a new expression parser
            if (!empty($remainder)) {
                $level++;
                // $next = new evaluable_expression($remainder, $this->parser); // Dig further.
                $return = $this->parse();
                $level--;
                // return $next->parse();
                return $return;
            }
            // End of expression chain. Everything on the path is fine.
            return [true, true];
        }

        // None of the admitted patterns match.
        $this->trace('Expression Evaluator: syntax error at level '.$level);
        $this->trace('Expression pattern1: '.$pattern1);
        $this->trace('Expression pattern2: '.$pattern2);
        $this->trace('Both patterns failed.');
        return [null, null];
    }

    /**
     * Actually evaluates the expression to get its final value. Expression needs to have been
     * parsed before so that parsing stack result is created.
     */
    public function evaluate() {
        global $CFG;
        if (!$this->parsed && ($CFG->debug == DEBUG_DEVELOPER)) {
            throw new moodle_exception("Attempt to evaluate an unparsed expression");
        }

        if (empty($this->stack->operands)) {
            // Stack is parsed but empty.
            return 0;
        }

        /*
         * Evaluation algorithm
         *
         * pull operand, evaluate and push in result
         * while (operand)
         *    pull operand
         *    pull op
         *    if (AND)
         *        evaluate and combine in results
         *    else (OR or XOR)
         *        evaluate and push in results
         *        push op
         *    fi
         * elihw
         *
         * final = false
         * while (results)
         *     pull result
         *     pull op
         *     final = final op result
         * elihw
         */
        $this->stack->results = [];

        $operand = array_shift($this->stack->operands);
        $elementres = $this->evaluate_element($operand);
        $this->trace("   Element result : $elementres");
        $this->stack->results[] = $elementres;

        while (!empty($this->stack->operands)) {
            $operand = array_shift($this->stack->operands);
            $op = array_shift($this->stack->operators);
            if ($op == 'AND') {
                $currentresult = array_pop($this->stack->results);
                $elementres = $this->evaluate_element($operand);
                $currentresult = $currentresult && $elementres;
                $this->trace("   Element result (AND) : $elementres");
                array_push($this->stack->results, $currentresult);
            } else {
                $this->trace("   Element result (OR) : storing current $currentresult ");
                array_push($this->stack->results, $currentresult);
                array_push($this->stack->operators, $op);
            }
        }

        $final = false;
        while (!empty($this->stack->results)) {
            $res = array_shift($this->stack->results);
            $op = array_shift($this->stack->operators);
            if ($op == 'OR') {
                $final = $final || $res;
            } else if ($op == 'XOR') {
                $final = $final xor $res;
            } else {
                // Single result
                $final =  $res;
            }
        }

        return $final;
    }

    /**
     * Evaluates an expression element. I.e. with NO logical operator inside.
     * Operand has been parsed and decomposed to a [op1, ev, op2, not) structure.
     * @param $operand an elementary expression.
     * @param $matches value captures.
     */
    protected function evaluate_element($operand) {

        if (self::$evaloperatormodels[$operand->ev]['optype'] == 'arithmetic') {
            $operandref1 = $operand->op1;
            $evalop = $operand->ev;
            $parser = new parse_valueref($this->parser);
            $operand1 = $parser->evaluate($operandref1);
            $litteral = $this->unquote($operand->op2); // can be empty.

            switch($operand->ev) {
                case '=':
                    $this->trace("Evaluating $operand1 == $litteral ");
                    return $operand1 == $litteral;
                case '<=':
                    return $operand1 <= $litteral;
                case '>=':
                    return $operand1 >= $litteral;
                case '!=':
                    return $operand1 != $litteral;
                case '=~':
                    return preg_match('/'.$litteral.'/', $operand1);
            }

        } else {
            // We have function calls with objectrefs.
            $operandref1 = $operand->op1;
            $evalop = $operand->ev;
            $operandref2 = $operand->op2; // can be empty.
            $parser = new parse_objectref($this->parser);
            $operand1 = $parser->evaluate($operandref1);
            $operand2 = $parser->evaluate($operandref2);
            if (method_exists($this, $evalop)) {
                return $this->$evalop($operand1, $operand2);
            }
            $this->parser->trace("Bad operator $evalop. Not implemented.");
            return false;
        }
    }

    /**
     * Stores an elementary element with its internal parts.
     * The expression matches the following pattern : 
     * [NOT] <op1> <ev> <op2>
     *
     * <op1> cannot be empty.
     * <ev> cannot be empty and is an evaluation operation or a keyword
     * <op2> might be empty for some keywords.
     *
     * Note : Storage known nothing of what is in op1 op2 and ev. Juste stores it.
     * We just should rely that all those terms are legitimated by the parsing and
     * consistant with the expression building rules.
     *
     * @param array the result of the element parsing.
     * @param int $level the parsing level (iteration). 
     */
    protected function store_elements($matches, $level) {

        if ($level == 1) {
            // Parse optional NOT
            $operand = new StdClass;
            $operand->op1 = $matches[2];
            $operand->ev = $matches[3];
            $operand->op2 = $matches[4];
            if (!empty($matches[1])) {
                $operand->not = true;
            } else {
                $operand->not = false;
            }
        } else {
            // Parse optional NOT
            $operand = new StdClass;
            $operand->op1 = $matches[3];
            $operand->ev = $matches[4];
            $operand->op2 = $matches[5];
            if (!empty($matches[2])) {
                $operand->not = true;
            } else {
                $operand->not = false;
            }
            $this->stack->operators[] = $matches[1];
        }

        if (!in_array($operand->ev, array_keys(self::$evaloperatormodels))) {
            $this->parser->trace("Parse error : operator {$operand->ev} not accepted.");
            $this->parsed = false;
            return;
        }

        $this->stack->operands[] = $operand;
        $this->parsed = true;
    }

    /* Utilities */

    /**
     * Get list of courseids where user is enrolled in the category scope.
     */
    protected function get_concerned_courses($userid, $categoryid) {
        
    }

    /* Function calls (element assertions) */

    /**
     * Depending on operand optype. 
     * - category : true if has no courses and no subcategories
     * - cohort : true if has members
     * @param objectref evaluator operand.
     */
    public function isempty($what) {
        global $DB;

        switch ($what->objecttype) {
            case 'category' : {
                $hascourses = $DB->count_records('course', ['category' => $what->id]);
                $hassubs = $DB->count_records('course_categories', ['parent' => $what->id]);

                return !$hascourses && !$hassubs;
            }

            case 'cohort' : {
                return $DB->record_exists('cohort_members', ['cohortid' => $what->id]);
            }

            return false;
        }
    }

    /**
     * Tests if $who as (any) role in $where 
     * @param objectref $who evaluator operand optype user.
     * @param objectref $where evaluator operand optype course or category.
     */
    public function hasrolein($who, $where) {
        global $DB;

        if (empty($where)) {
            $context = context_system::instance();
        } else if ($where->objecttype == 'course') {
            $context = context_course::instance($where->id);
        } else if ($where->objecttype == 'category') {
            $context = context_coursecat::instance($where->id);
        } else {
            throw new moodle_exception("hasrolein : Invalid objecttype $where->objecttype for second parameter");
        }

        return $DB->record_exists('role_assignments', ['contextid' => $context->id, 'userid' => $who->id]);
    }

    /**
     * Tests if $who as an active explicit enrollemnt in $where 
     * @param objectref $who evaluator operand optype user.
     * @param objectref $where evaluator operand optype course.
     *
     *
     */
    public function isenrolledin($who, $where) {

        if ($where->objecttype == 'course') {
            $context = context_course::instance($where->id);
            return is_enrolled($context, $who);
        } else if ($where->objecttype == 'category') {
            $context = context_coursecat::instance($where->id);
            return is_enrolled($context, $who);
        }

        throw new moodle_exception("hasloggedin : Invalid objecttype $where->objecttype for second parameter");
    }

    /**
     * Tests if $who as logged in once
     * @param objectref $who evaluator operand optype user.
     * @param objectref $where evaluator operand optype course. If null, checks in whole site.
     */
    public function hasloggedin($who, $where) {
        global $DB;

        if (!empty($where))  {
            if ($where->objecttype == 'course') {
                return $DB->get_field('user_lastaccess', ['userid' => $who->id, 'courseid' => $where->id]);
            }

            throw new moodle_exception("hasloggedin : Invalid objecttype $where->objecttype for second parameter");
        } else {
            return $who->lastlogin;
        }
    }

    /**
     * Tests if $who has completed a course or a set of courses in a category
     * @param objectref $who evaluator operand optype user.
     * @param objectref $what evaluator operand optype course or category
     *
     * If $what is a category, will only check all courses $who is enrolled in within the category.
     */
    public function hascompleted($who, $what) {
        global $DB;

        if ($what->objecttype == 'course') {
            return $DB->record_exists_select('course_completion', ' userid = ? AND course = ? AND timecompleted > 0 ', [$who->id, $what->id]);
        }

        if ($what->objecttype == 'category') {
        }

        throw new moodle_exception("hascompleted : Invalid objecttype $what->objecttype for second parameter");
    }

    /**
     * Tests if $who has started a course, i.e. has a first completion mark in the course.
     * @param objectref $who evaluator operand optype user.
     * @param objectref $what evaluator operand optype course or category
     *
     * If $what is a category, will only check all courses $who is enrolled in within the category.
     */
    public function hasstarted($who, $what) {
        global $DB;

        if ($what->objecttype == 'course') {

            $sql = "
                COUNT
                    *
                FROM
                    course_modules_completion cmc,
                    course_modules cm
                WHERE
                    cmc.coursemoduleid = cm.id AND
                    cmc.userid = ? AND
                    cm.course = ? AND
                    completionstate = 1
            ";

            return $DB->count_records_sql($sql, [$who->id, $what->id]);

        } else if ($what->objecttype == 'category') {

        }

        throw new moodle_exception("hasstarted : Invalid objecttype $what->objecttype for second parameter");

    }

    /**
     * Tests if $what is inside $category
     * @param objectref $what evaluator operand optype course or category
     * @param objectref $category evaluator operand optype category
     *
     * If what is a category, will test if $category is a child of $what.
     * If what is a course, will test if $category is in $what..
     */
    public function isincategory($what, $category) {

        if ($what->objecttype == 'course') {
            return $what->category == $category->id;
        } else if ($what->objecttype == 'category') {
            return $what->parent == $category->id;
        }

        throw new moodle_exception("isincategory : Invalid objecttype $what->objecttype for first parameter");
    }

    /**
     * Tests if $what is inside $category or in any subcategory
     * @param objectref $what evaluator operand optype course or category
     * @param objectref $category evaluator operand optype category
     *
     * If what is a category, will test if $category is a child of $what.
     * If what is a course, will test if $category is in $what..
     */
    public function isincattree($what, $category) {
        return $this->isincategory($what, $category) || $this->isinsubs($what, $category);
    }

    /**
     * Tests if $what is inside $category'ds childs (but not in the category)
     * @param objectref $what evaluator operand optype course or category
     * @param objectref $category evaluator operand optype category
     *
     * If $what is a category, will test if $category is an ancestor of $what.
     * If $what is a course, will test if $category is an ancestor of $what.
     *
     * @throws moodle_exception when $what optype is unsupported.
     */
    public function isinsubs($what, $category) {

        $cat = core_course_category::get($category->id);
        $childrenids = $cat->get_all_children_ids();

        if ($what->objecttype == 'course') {
            return in_array($what->category, $childrenids);
        } else if ($what->objecttype == 'category') {
            return in_array($what->id, $childrenids);
        }

        throw new moodle_exception("insubs : Invalid objecttype $what->objecttype for first parameter");

    }

    /**
     * Tests if $what is user is in the cohort
     * @param objectref $what evaluator operand optype user
     * @param objectref $cohort evaluator operand optype cohort
     *
     * @throws moodle_exception when $what optype is unsupported.
     */
    public function isincohort($what, $cohort) {
        global $DB;

        if ($what->objecttype != 'user') {
            throw new moodle_exception("isincohort : Invalid objecttype $what->objecttype for first parameter");
        }

        if ($cohort->objecttype != 'cohort') {
            throw new moodle_exception("isincohort : Invalid objecttype $cohort->objecttype for second parameter");
        }

        return $DB->record_exists('cohort_members', ['userid' => $what->id, 'cohortid' => $cohort->id]);
    }

    /**
     * Tests if $what is user is in the group. this is active only in course context, or always returns false.
     * @param objectref $what evaluator operand optype user
     * @param objectref $group evaluator operand optype group
     *
     * @throws moodle_exception when $what optype is unsupported.
     */
    public function isingroup($what, $group) {
        global $DB;

        if ($what->objecttype != 'user') {
            throw new moodle_exception("isingroup : Invalid objecttype $what->objecttype for first parameter");
        }

        if ($group->objecttype != 'groups') {
            throw new moodle_exception("isingroup : Invalid objecttype $group->objecttype for second parameter");
        }

        $result = $DB->record_exists('groups_members', ['userid' => $what->id, 'groupid' => $group->id]);
        return $result;
    }
}