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
 * Tests the script engine postprocessor
 *
 * @package    block_publishflow
 * @category   test
 * @copyright  2013 Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

global $CFG;

// Require all impplemented handlers.
$handlers = glob($CFG->dirroot.'/blocks/publishflow/classes/postprocessing/*');
foreach ($hanlders as $hndfile) {
    include_once($hndfile);
}

/**
 *  tests class for local_shop.
 */
class block_publishflow_scriptengine_testcase extends advanced_testcase {

    /**
     * Script engine handler classes are instanciated and functionnally tested.
     * Test scenario : We setup some courses in a test category for operating the handlers :
     * - Test course
     * - Test category 1
     * - Test category 2
     * 
     * Test to perform :
     * - Adding blocks to the course
     * - Hiding blocks in a course
     * - Showing blocks in a course
     * - Removing blocks to the course
     *
     * - Moving a course from category 1 to category 2 and backwards.
     * - Adding a self enrol method to course
     * - Removing a self enrol method to course
     * - Hiding the course
     * - Showing back the course
     * - Backuping the course for publishflow.
     * - Hiding the category 2
     * - Showing the category 2
     * - Enrolling current user in course (user not inside)
     * - Unenrolling current user from course (user inside)
     * - Enrolling current user in course (user inside)
     * - Unenrolling current user from course (user not inside)
     *
     * Error situations testing
     * - Moving a course but course does not exist.
     */
    public function test_handlers() {
        global $DB;

        $this->resetAfterTest();

        // Setup moodle content environment.

        $category1 = $this->getDataGenerator()->create_category();
        $category2 = $this->getDataGenerator()->create_category();

        $params = array('name' => 'Publishflow test course 1', 'shortname' => 'PFTEST1', 'category' => $category1->id, 'idnumber' => 'PFTEST1');
        $course1 = $this->getDataGenerator()->create_course($params);
        $contextid1 = context_course::instance($course1->id);

        $params = array('name' => 'Publishflow test course 2', 'shortname' => 'PFTEST2', 'category' => $category1->id, 'idnumber' => 'PFTEST2');
        $course2 = $this->getDataGenerator()->create_course($params);
        $contextid2 = context_course::instance($course2->id);

        $this->setAdminUser();

        $this->assertTrue(empty($enrolled));

        // Prepare instances of handlers.
        $handleaddblock = new \block_publishflow\postprocessing\handle_add_block();
        $handleremoveblock = new \block_publishflow\postprocessing\handle_remove_block();
        $handlehideblock = new \block_publishflow\postprocessing\handle_hide_block();
        $handleshowblock = new \block_publishflow\postprocessing\handle_show_block();
        $handleaddenrolmethod = new \block_publishflow\postprocessing\handle_add_enrol_method_block();
        $handleremoveenrolmethod = new \block_publishflow\postprocessing\handle_remove_enrol_method_block();
        $handlemovecourse = new \block_publishflow\postprocessing\handle_move_course();

        $result = array();

        // ADDING BLOCKS.
        // Test with identified course.
        $context = new StdClass;
        $context->blockname = 'html';
        $context->blockcourseid = $course1->id;
        $result = $handleaddblock->execute($result, $context, $logger);

        $this->assertTrue(true == $DB->get_record('block_instances', array('blockname' => 'html', 'parentcontextid' => $contextid1)));

        // Test with "current" course.
        $context = new StdClass;
        $context->blockname = 'html';
        $context->blockcourseid = 'current';
        $context->courseid = $course2->id;
        $result = $handleaddblock->execute($result, $context, $logger);

        $this->assertTrue(true == $DB->get_record('block_instances', array('blockname' => 'html', 'parentcontextid' => $contextid2)));

        // Test adding more blocks with identified course.
        $context = new StdClass;
        $context->blockname = 'html';
        $context->blockcourseid = $course1->id;
        $result = $handleaddblock->execute($result, $context, $logger);
        $result = $handleaddblock->execute($result, $context, $logger);
        $result = $handleaddblock->execute($result, $context, $logger);

        $course1blocks = $DB->get_records('block_instances', array('blockname' => 'html', 'parentcontextid' => $contextid1));
        $this->assertTrue(4 == count(array_keys($course1blocks)));

        // HIDING BLOCKS. (all instances of in the course)
        // Test with "current" course on course2. All instances should disappear.
        $context = new StdClass;
        $context->blockname = 'html';
        $context->courseid = $course2->id;
        $context->blockcourseid = 'current';
        $result = $handlehideblock->execute($result, $context, $logger);

        $params = array('blockname' => 'html', 'parentcontextid' => $contextid2);
        $sql = "
            SELECT
                *
            FROM
                {block_instances} bi,
                {block_positions} bp
            WHERE
                bi.id = bp.blockinstanceid AND
                pb.visible = 1 AND
                bi.parentcontextid = :parentcontextid AND
                bi.blockname = :blockname
        ";
        // We should NOT find any record visible in this range.
        $this->assertTrue(false == $DB->get_record_sql($sql, $params));

        $sql = "
            SELECT
                *
            FROM
                {block_instances} bi,
                {block_positions} bp
            WHERE
                bi.id = bp.blockinstanceid AND
                pb.visible = 0 AND
                bi.parentcontextid = :parentcontextid AND
                bi.blockname = :blockname
        ";
        // We SHOULD find records not visible in this range.
        $this->assertTrue(false != $DB->get_record_sql($sql, $params));

        // SHOWING BLOCKS. (all instances of in the course)
        // Test with "current" course on course2. All instances should disappear.
        $context = new StdClass;
        $context->blockname = 'html';
        $context->courseid = $course2->id;
        $context->blockcourseid = 'current';
        $result = $handleshowblock->execute($result, $context, $logger);

        $params = array('blockname' => 'html', 'parentcontextid' => $contextid2);
        $sql = "
            SELECT
                *
            FROM
                {block_instances} bi,
                {block_positions} bp
            WHERE
                bi.id = bp.blockinstanceid AND
                pb.visible = 1 AND
                bi.parentcontextid = :parentcontextid AND
                bi.blockname = :blockname
        ";
        // We should find records visible (not hidden) in this range.
        $this->assertTrue(false != $DB->get_record_sql($sql, $params));

        $sql = "
            SELECT
                *
            FROM
                {block_instances} bi,
                {block_positions} bp
            WHERE
                bi.id = bp.blockinstanceid AND
                pb.visible = 0 AND
                bi.parentcontextid = :parentcontextid AND
                bi.blockname = :blockname
        ";
        // We SHOULD NOT find records not visible in this range.
        $this->assertTrue(false == $DB->get_record_sql($sql, $params));

        // REMOVING BLOCKS. (all instances of in the course)

        // Test with "current" course on course1. All instances should disapear.
        $context = new StdClass;
        $context->blockname = 'html';
        $context->courseid = $course1->id;
        $context->blockcourseid = 'current';
        $result = $handleremoveblock->execute($results, $context, $logger);

        $this->assertTrue(false == $DB->get_record('block_instances', array('blockname' => 'html', 'parentcontextid' => $contextid1)));

        // Test with designated course on course2.
        $context = new StdClass;
        $context->blockname = 'html';
        $context->blockcourseid = $course2->id;
        $result = $handleremoveblock->execute($results, $context, $logger);

        $this->assertTrue(false == $DB->get_record('block_instances', array('blockname' => 'html', 'parentcontextid' => $contextid2)));

        // MOVE COURSE. Move course 1 back and forth.

        // Test with "current" course on course1.
        $context = new StdClass;
        $context->courseid = $course1->id;
        $context->blockcourseid = 'current';
        $context->coursecatid = $category2->id;
        $result = $handlemovecourse->execute($results, $context, $logger);

        $this->assertTrue($category2->id == $DB->get_field('course', 'category', array('id' => $course1->id)));

        $context = new StdClass;
        $context->courseid = $course1->id;
        $context->blockcourseid = 'current';
        $context->coursecatid = $category1->id;
        $result = $handlemovecourse->execute($results, $context, $logger);

        $this->assertTrue($category1->id == $DB->get_field('course', 'category', array('id' => $course1->id)));

        // Test with designated course on course1.
        $context = new StdClass;
        $context->coursecatid = $category2->id;
        $context->movecourseid = $course2->id;
        $result = $handleremoveblock->execute($results, $context, $logger);

        $this->assertTrue($category2->id == $DB->get_field('course', 'category', array('id' => $course2->id)));

        $context = new StdClass;
        $context->coursecatid = $category1->id;
        $context->movecourseid = $course2->id;
        $result = $handleremoveblock->execute($results, $context, $logger);

        $this->assertTrue($category1->id == $DB->get_field('course', 'category', array('id' => $course2->id)));

    }
}