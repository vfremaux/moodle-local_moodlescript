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
 * A class that catalogs evaluator functions to evaluate expressions.
 * Evaluation functions can be polymorphic, and have all boolean ouptut, true or false.
 * Depending on input objecttypes, the suitable underlying algorithm will be applied to
 * comply the evaluation semantics.
 *
 * @package local_moodlescript
 * @category local
 * @author Valery Fremaux (valery.fremaux@gmail.com)
 * @copyright (c) 2017 onwards Valery Fremaux (http://www.mylearningfactory.com)
 */
namespace local_moodlescript\engine;

require_once($CFG->dirroot.'/grade/querylib.php');

use category;
use context;
use context_course;
use moodle_exception;

defined('MOODLE_INTERNAL') || die();

class evaluable_functions {

    /**
     * Checks a high level semantic "is empty" on objectref
     */
    public static function isempty(objectref $ref) {
        global $DB;

        if ($ref->objectype == 'category') {
            $cat = category::get($ref->id);
            $count = $cat->get_course_count(['recursive' => true]);
            return ($count == 0);
        } else if ($ref->objectype == 'cohort') {
            return $DB->record_exists('cohort_memebers', ['cohortid' => $ref->id]);
        } else {
            $return = !$DB->record_exists($ref->objecttype, ['id' => $ref->id]);
            return $return;
        }
    }

    /**
     * Checks if given user has logged in course or site.
     */
    public static function hasloggedin(objectref $user, objectref $course = null) {
        global $DB;

        if (!is_null($course)) {
            return $DB->record_exists('user_lastaccess', ['userid' => $user->id, 'courseid' => $course->id]);
        } else {
            $useraccess = $DB->get_field('user', 'lastaccess', ['id' => $user->id]);
            return $useraccess;
        }
    }

    /**
     * Checks if given user is actually loggedin.
     */
    public static function isloggedin(objectref $user) {
        global $USER, $DB;

        if ($USER->id == $user->id) {
            return isloggedin();
        } else {
            // Find a way to know (last log in a short time period ?)
            $horizon = time() - 5 * MINSECS;
            $params = [$USER->id, $horizon];
            $lastlog = $DB->get_records('logstore_standard_log', " userid = ? AND timecreated > ? ", $params, 'timecreated DESC, id DESC', 'id, timecreated,action');
            if (!$lastlog || $lastlog == 'loggedout') {
                return false;
            }
            else return true;
        }
    }

    /**
     * Checks if given user has running enrolment in course.
     */
    public static function isenrolledin(objectref $user, objectref $course) {
        $context = context_course::instance($course->id);
        return is_enrolled($context, $user, '', true);
    }

    /**
     * Checks user has some roles marked in course context or inherited down to that context.
     * Inspired from @see lib/accesslib.php§user_has_role_assignment
     */
    public static function hasrolein(objectref $user, objectref $course) {
        global $DB;

        if (!$context = context::course($course->id)) {
            return false;
        }
        $parents = $context->get_parent_context_ids(true);
        list($contexts, $params) = $DB->get_in_or_equal($parents, SQL_PARAMS_NAMED, 'r');
        $params['userid'] = $userid;
        $params['roleid'] = $roleid;

        $sql = "SELECT COUNT(ra.id)
                  FROM {role_assignments} ra
                 WHERE ra.userid = :userid AND ra.contextid $contexts";

        $count = $DB->get_field_sql($sql, $params);
        return ($count > 0);
    }

    /**
     * Checks user has some grades registered in course context.
     * @see grade/querylib.php
     */
    public static function hasgradesin(objectref $user, objectref $course) {
        $grades = grade_get_course_grade($user->id, $course->id);
        return !($grades === false);
    }

    /**
     * Checks user has started completion work for this course.
     */
    public static function hasstarted(objectref $user, objectref $course) {
        global $DB;

        $started = $DB->get_field('completion_course', 'timestaryed', ['userid' => $user->id, 'course' => $course->id]);
        return $started > 0;
    }

    /**
     * Checks user has completed the course.
     */
    public static function hascompleted(objectref $user, objectref $course) {
        global $DB;

        $completed = $DB->get_field('completion_course', 'timecompleted', ['userid' => $user->id, 'course' => $course->id]);
        return (!empty($completed));
    }

    /**
     * Checks user is in this cohort.
     */
    public static function isincohort(objectref $user, objectref $cohort) {
        global $DB;

        return $DB->record_exists('cohort_members', ['userid' => $user->id, 'cohortid' => $cohort->id]);
    }

    /**
     * Checks user is in this group (usable in course context).
     */
    public static function isingroup(objectref $user, objectref $group) {
        global $DB;

        return $DB->record_exists_sql('groups_members', ['userid' => $user->id, 'groupid' => $group->id]);
    }

    /**
     * Checks the course is in the category.
     * this seems a bit trivial and is given essentially for unification of the query
     * syntax.
     */
    public static function isincategory(objectref $course, objectref $category) {
        return $course->category == $category->id;
    }

    /**
     * Checks the course is in the category or the category subtree.
     */
    public static function isinsubs(objectref $courseorcat, objectref $category) {
        global $DB;

        if ($courseorcat->objecttype == 'course') {
            $catid = $courseorcat->category;
        } else if ($courseorcat->objecttype == 'category') {
            $catid = $courseorcat->id;
        } else {
            throw new moodle_exception("Bad object type {$courseorcat->objecttype} in \"isinsubs\"");
        }

        if ($catid == $category->id) {
            return true;
        }

        while ($catid = $DB->get_field('course_categories', 'parent', ['id' => $catid])) {
            if ($catid == $category->id) {
                return true;
            }
        }

        return false;
    }
}
