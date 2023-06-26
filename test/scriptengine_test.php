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
 * @package    local_moodlescript
 * @category   test
 * @copyright  2013 Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

global $CFG;
global $USER;

require_once($CFG->dirroot.'/local/moodlescript/lib.php');
require_once($CFG->dirroot.'/local/moodlescript/classes/engine/stack.class.php');

use local_moodlescript\engine\stack;

local_moodlescript_load_engine();

/**
 *  tests class for local_shop.
 */
class local_moodlescript_scriptengine_testcase extends advanced_testcase {

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

        $params = array('name' => 'Moodlescript test course 1', 'shortname' => 'PFTEST1', 'category' => $category1->id, 'idnumber' => 'PFTEST1');
        $course1 = $this->getDataGenerator()->create_course($params);
        $contextid1 = context_course::instance($course1->id)->id;

        $params = array('name' => 'Moodlescript test course 2', 'shortname' => 'PFTEST2', 'category' => $category1->id, 'idnumber' => 'PFTEST2');
        $course2 = $this->getDataGenerator()->create_course($params);
        $contextid2 = context_course::instance($course2->id)->id;

        $user1 = $this->getDataGenerator()->create_user(array('email'=>'user1@example.com', 'username'=>'user1'));
        $user2 = $this->getDataGenerator()->create_user(array('email'=>'user2@example.com', 'username'=>'user2'));

        $this->setAdminUser();

        $this->assertTrue(empty($enrolled));

        // Prepare instances of handlers.
        $handleaddblock = new \local_moodlescript\engine\handle_add_block();
        $handleremoveblock = new \local_moodlescript\engine\handle_remove_block();
        $handlehideblock = new \local_moodlescript\engine\handle_hide_block();
        $handleshowblock = new \local_moodlescript\engine\handle_show_block();
        $handleaddenrolmethod = new \local_moodlescript\engine\handle_add_enrol_method();
        $handleremoveenrolmethod = new \local_moodlescript\engine\handle_remove_enrol_method();
        $handlemovecourse = new \local_moodlescript\engine\handle_move_course();
        $handleenroluser = new \local_moodlescript\engine\handle_enrol();
        $handleunenroluser = new \local_moodlescript\engine\handle_unenrol();
        $handleassignuserrole = new \local_moodlescript\engine\handle_assign_role();
        $handleunassignuserrole = new \local_moodlescript\engine\handle_unassign_role();
        $handleaddgroup = new \local_moodlescript\engine\handle_add_group();
        $handleremovegroup = new \local_moodlescript\engine\handle_remove_group();
        $handleaddgrouping = new \local_moodlescript\engine\handle_add_grouping();
        $handleremovegrouping = new \local_moodlescript\engine\handle_remove_grouping();
        $handlegroupuser = new \local_moodlescript\engine\handle_group_user();
        $handleungroupuser = new \local_moodlescript\engine\handle_ungroup_user();
        $handlegroupgroup = new \local_moodlescript\engine\handle_group_group();
        $handleungroupgroup = new \local_moodlescript\engine\handle_ungroup_group();

        $results = array();

        $logger = new stack();

        // ADDING BLOCKS.
        // Test with identified course.
        $context = new StdClass;
        $context->blockname = 'html';
        $context->blockcourseid = $course1->id;
        $handleaddblock->execute($results, $context, $logger);

        $this->assertTrue(true == $DB->get_record('block_instances', array('blockname' => 'html', 'parentcontextid' => $contextid1)));

        // Test with "current" course.
        $context = new StdClass;
        $context->blockname = 'html';
        $context->blockcourseid = 'current';
        $context->courseid = $course2->id;
        $handleaddblock->execute($results, $context, $logger);

        $this->assertTrue(true == $DB->get_record('block_instances', array('blockname' => 'html', 'parentcontextid' => $contextid2)));

        // Test adding more blocks with identified course.
        $context = new StdClass;
        $context->blockname = 'html';
        $context->blockcourseid = $course1->id;
        $handleaddblock->execute($results, $context, $logger);
        $handleaddblock->execute($results, $context, $logger);
        $handleaddblock->execute($results, $context, $logger);

        $course1blocks = $DB->get_records('block_instances', array('blockname' => 'html', 'parentcontextid' => $contextid1));
        $this->assertTrue(4 == count(array_keys($course1blocks)));

        // HIDING BLOCKS. (all instances of in the course)
        // Test with "current" course on course2. All instances should disappear.
        $context = new StdClass;
        $context->blockname = 'html';
        $context->courseid = $course2->id;
        $context->hidecourseid = 'current';
        $handlehideblock->execute($results, $context, $logger);

        $params = array('blockname' => 'html', 'parentcontextid' => $contextid2);
        $sql = "
            SELECT
                *
            FROM
                {block_instances} bi,
                {block_positions} bp
            WHERE
                bi.id = bp.blockinstanceid AND
                bp.visible = 1 AND
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
                bp.visible = 0 AND
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
        $context->showcourseid = 'current';
        $handleshowblock->execute($results, $context, $logger);

        $params = array('blockname' => 'html', 'parentcontextid' => $contextid2);
        $sql = "
            SELECT
                *
            FROM
                {block_instances} bi,
                {block_positions} bp
            WHERE
                bi.id = bp.blockinstanceid AND
                bp.visible = 1 AND
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
                bp.visible = 0 AND
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
        $handleremoveblock->execute($results, $context, $logger);

        $this->assertTrue(false == $DB->get_record('block_instances', array('blockname' => 'html', 'parentcontextid' => $contextid1)));

        // Test with designated course on course2.
        $context = new StdClass;
        $context->blockname = 'html';
        $context->blockcourseid = $course2->id;
        $handleremoveblock->execute($results, $context, $logger);

        $this->assertTrue(false == $DB->get_record('block_instances', array('blockname' => 'html', 'parentcontextid' => $contextid2)));

        // MOVE COURSE. Move course 1 back and forth.

        // Test with "current" course on course1.
        $context = new StdClass;
        $context->courseid = $course1->id;
        $context->movecourseid = 'current';
        $context->coursecatid = $category2->id;
        $handlemovecourse->execute($results, $context, $logger);

        $this->assertTrue($category2->id == $DB->get_field('course', 'category', array('id' => $course1->id)));

        $context = new StdClass;
        $context->courseid = $course1->id;
        $context->movecourseid = 'current';
        $context->coursecatid = $category1->id;
        $handlemovecourse->execute($results, $context, $logger);

        $this->assertTrue($category1->id == $DB->get_field('course', 'category', array('id' => $course1->id)));

        // Test with designated course on course1.
        $context = new StdClass;
        $context->coursecatid = $category2->id;
        $context->movecourseid = $course2->id;
        $handlemovecourse->execute($results, $context, $logger);

        $this->assertTrue($category2->id == $DB->get_field('course', 'category', array('id' => $course2->id)));

        $context = new StdClass;
        $context->coursecatid = $category1->id;
        $context->movecourseid = $course2->id;
        $handlemovecourse->execute($results, $context, $logger);

        $this->assertTrue($category1->id == $DB->get_field('course', 'category', array('id' => $course2->id)));

        // ENROL USER.

        $rolestudent = $DB->get_record('role', array('shortname' => 'student'));

        $context = new StdClass;
        $context->enrolcourseid = $course1->id;
        $context->userid = $user2->id;
        $context->method = 'manual';
        $context->roleid = $rolestudent->id;
        $handleenroluser->execute($results, $context, $logger);

        $course1context = context_course::instance($course1->id);
        $rolecc = $DB->get_record('role', array('shortname' => 'student'));
        $params = array('contextid' => $course1context->id,
                        'userid' => $user2->id,
                        'roleid' => $rolestudent->id);
        $this->assertNotEmpty($DB->get_record('role_assignments', $params));

        $course1manualenrol = $DB->get_record('enrol', array('courseid' => $course1->id, 'enrol' => 'manual'));

        $course1context = context_course::instance($course1->id);
        $params = array('userid' => $user2->id,
                        'enrolid' => $course1manualenrol->id);
        $this->assertNotEmpty($DB->get_record('user_enrolments', $params));

        // UNENROL USER.

        $context = new StdClass;
        $context->unenrolcourseid = $course1->id;
        $context->unenroluserid = $user2->id;
        $context->params = new StdClass;
        $context->params->enrol = 'manual';
        $context->roleid = $rolestudent->id;
        $handleunenroluser->execute($results, $context, $logger);

        $course1context = context_course::instance($course1->id);
        $params = array('userid' => $user2->id,
                        'enrolid' => $course1manualenrol->id,
                        'status' => 0);
        $this->assertEmpty($DB->get_record('user_enrolments', $params));

        $params = array('contextid' => $course1context->id,
                        'userid' => $user2->id,
                        'roleid' => $rolestudent->id);
        $this->assertEmpty($DB->get_record('role_assignments', $params));

        // ASSIGN ROLE.
        $rolecc = $DB->get_record('role', array('shortname' => 'coursecreator'));

        $context = new StdClass;
        $context->rolecourseid = $course1->id;
        $context->userid = $user1->id;
        $context->roleid = $rolecc->id;
        $handleassignuserrole->execute($results, $context, $logger);

        $course1context = context_course::instance($course1->id);
        $params = array('contextid' => $course1context->id,
                        'userid' => $user1->id,
                        'roleid' => $rolecc->id);
        $this->assertNotEmpty($DB->get_record('role_assignments', $params));

        // UNASSIGN ROLE.

        $context = new StdClass;
        $context->rolecourseid = $course1->id;
        $context->userid = $user1->id;
        $context->roleid = $rolecc->id;
        $handleunassignuserrole->execute($results, $context, $logger);

        $course1context = context_course::instance($course1->id);
        $params = array('contextid' => $course1context->id,
                        'userid' => $user1->id,
                        'roleid' => $rolecc->id);
        $this->assertEmpty($DB->get_record('role_assignments', $params));

        // ADDING GROUPING.
        $context = new StdClass;
        $context->groupingcourseid = $course1->id;
        $context->groupingname = "Grouping Test 1";
        $context->groupingidnumber = "GRPNG_1";
        $grouping1id = $handleaddgrouping->execute($results, $context, $logger);

        $context = new StdClass;
        $context->groupingcourseid = $course1->id;
        $context->groupingname = "Grouping Test 2";
        $context->params = new StdClass;
        $context->params->description = "Some description for grouping 2";
        $grouping2id = $handleaddgrouping->execute($results, $context, $logger);

        // ADDING GROUP.
        $context = new StdClass;
        $context->groupcourseid = $course1->id;
        $context->groupname = "Group Test A";
        $context->groupidnumber = "GRP_A";
        $context->params = new StdClass;
        $context->params->description = "Some description for group A";
        $context->params->enrolmentkey = "AZERTYU";
        $groupaid = $handleaddgroup->execute($results, $context, $logger);

        $context = new StdClass;
        $context->groupcourseid = $course1->id;
        $context->groupname = "Group Test B";
        $context->groupidnumber = "GRP_B";
        $context->params = new StdClass;
        $groupbid = $handleaddgroup->execute($results, $context, $logger);

        // GROUPING GROUPS.
        $context = new StdClass;
        $context->groupcourseid = $course1->id;
        $context->groupgroupingid = $grouping1id;
        $context->groupgroupid = $groupaid;
        $handlegroupgroup->execute($results, $context, $logger);

        $context = new StdClass;
        $context->groupcourseid = $course1->id;
        $context->groupgroupingid = $grouping1id;
        $context->groupgroupid = $groupbid;
        $handlegroupgroup->execute($results, $context, $logger);

        // GROUPING USERS.
        $context = new StdClass;
        $context->groupcourseid = $course1->id;
        $context->groupuserid = $user1->id;
        $context->groupgroupid = $groupaid;
        $handlegroupuser->execute($results, $context, $logger);

        $context = new StdClass;
        $context->groupcourseid = $course1->id;
        $context->groupuserid = $user2->id;
        $context->groupgroupid = $groupaid;
        $handlegroupuser->execute($results, $context, $logger);

        // replay for idempotency
        $context = new StdClass;
        $context->groupcourseid = $course1->id;
        $context->groupuserid = $user2->id;
        $context->groupgroupid = $groupaid;
        $handlegroupuser->execute($results, $context, $logger);

        // UNGROUP USER.
        $context = new StdClass;
        $context->groupcourseid = $course1->id;
        $context->groupuserid = $user2->id;
        $context->groupgroupid = $groupaid;
        $handleungroupuser->execute($results, $context, $logger);

        // REMOVING GROUP.
        $context = new StdClass;
        $context->groupcourseid = $course1->id;
        $context->groupgroupid = $groupaid;
        $handleremovegroup->execute($results, $context, $logger);

        // REMOVING GROUPING.
        $context = new StdClass;
        $context->groupingcourseid = $course1->id;
        $context->groupinggroupid = $grouping1id;
        $handleremovegrouping->execute($results, $context, $logger);

        $context = new StdClass;
        $context->groupingcourseid = $course1->id;
        $context->groupinggroupid = $grouping2id;
        $handleremovegrouping->execute($results, $context, $logger);

        // Check everything is gone.
        $params = array('groupid' => $groupaid, 'userid' => $user1->id);
        $this->assertEmpty($DB->get_record('groups_members', $params));
        $params = array('groupid' => $groupaid, 'userid' => $user2->id);
        $this->assertEmpty($DB->get_record('groups_members', $params));
        $params = array('groupid' => $groupbid, 'userid' => $user1->id);
        $this->assertEmpty($DB->get_record('groups_members', $params));
        $params = array('groupid' => $groupbid, 'userid' => $user2->id);
        $this->assertEmpty($DB->get_record('groups_members', $params));
    }

    public function test_parsers() {
        global $DB, $CFG, $SITE;

        $testscript = implode("\n", file($CFG->dirroot.'/local/moodlescript/test/test_script.mdl'));

        $this->resetAfterTest();

        $parser = new \local_moodlescript\engine\parser($testscript);
        $globalcontext = array('site' => $SITE->fullname);
        $stack = $parser->parse($globalcontext);

        $this->assertNotNull($stack);
    }

    /**
     * A test scenario that tests the evaluation of expressions
     */
    public function test_expressions() {
        global $CFG, $DB, $USER;

        require_once($CFG->dirroot.'/local/moodlescript/classes/engine/evaluable_expression.class.php');

        $this->resetAfterTest();

        // Setup moodle content environment.

        $params= ['name' => 'courses 1', 'idnumber' => 'Y1'];
        $category1 = $this->getDataGenerator()->create_category($params);
        $params= ['name' => 'courses 2', 'idnumber' => 'Y2'];
        $category2 = $this->getDataGenerator()->create_category();

        $params = array('name' => 'Hogwarts Elemental Magics  1', 'shortname' => 'HEM1', 'category' => $category1->id, 'idnumber' => 'HEM1');
        $course1 = $this->getDataGenerator()->create_course($params);
        $contextid1 = context_course::instance($course1->id)->id;

        $params = array('name' => 'Hogwarts Evil Curses 2', 'shortname' => 'HEC1', 'category' => $category1->id, 'idnumber' => 'HEC1');
        $course2 = $this->getDataGenerator()->create_course($params);
        $contextid2 = context_course::instance($course2->id)->id;

        $u = ['email' => 'hermione.granger@hogwarts.edu', 'username' => 'hermione', 'idnumber' => 'HG'];
        $hermione = $this->getDataGenerator()->create_user($u);
        $u = ['email' => 'harry.potter@hogwarts.edu', 'username' => 'harry', 'idnumber' => 'HP'];
        $harry = $this->getDataGenerator()->create_user($u);
        $u = ['email' => 'draco.malfoy@hogwarts.edu', 'username' => 'draco', 'idnumber' => 'DM'];
        $draco = $this->getDataGenerator()->create_user($u);
        $u = ['email' => 'minerva.mcgonagall@hogwarts.edu', 'username' => 'minerva', 'idnumber' => 'MMG', 'department' => 'Transformations'];
        $minerva = $this->getDataGenerator()->create_user($u);

        // Create custom fields.

        // Add an optional, visible, unlocked custom field.
        $DB->insert_record('user_info_field', ['shortname' => 'status', 'name' => 'Status', 'required' => 0,
            'visible' => 1, 'locked' => 0, 'categoryid' => 1, 'datatype' => 'text']);

        $DB->insert_record('user_info_field', ['shortname' => 'pet', 'name' => 'Pet', 'required' => 0,
            'visible' => 1, 'locked' => 0, 'categoryid' => 1, 'datatype' => 'text']);

        $DB->insert_record('user_info_field', ['shortname' => 'house', 'name' => 'House', 'required' => 0,
            'visible' => 1, 'locked' => 0, 'categoryid' => 1, 'datatype' => 'text']);

        profile_save_data((object)['id' => $hermione->id, 'profile_field_pet' => 'Crookshanks']);
        profile_save_data((object)['id' => $hermione->id, 'profile_field_house' => 'Griffindor']);
        profile_save_data((object)['id' => $hermione->id, 'profile_field_status' => 'student']);
        profile_save_data((object)['id' => $harry->id, 'profile_field_pet' => 'Hedwig']);
        profile_save_data((object)['id' => $harry->id, 'profile_field_house' => 'Griffindor']);
        profile_save_data((object)['id' => $harry->id, 'profile_field_status' => 'student']);
        profile_save_data((object)['id' => $draco->id, 'profile_field_pet' => 'Niffler']);
        profile_save_data((object)['id' => $draco->id, 'profile_field_house' => 'Slytherin']);
        profile_save_data((object)['id' => $draco->id, 'profile_field_status' => 'student']);
        profile_save_data((object)['id' => $minerva->id, 'profile_field_status' => 'teacher']);

        // Create some cohorts.
        $cohort1 = $this->getDataGenerator()->create_cohort(['name' => 'Poudlar Griffindor 1', 'idnumber' => 'G1']);
        $cohort2 = $this->getDataGenerator()->create_cohort(['name' => 'Poudlar Slytherin 1', 'idnumber' => 'S1']);

        // cohort_add_member($cohort1->id, $hermione->id); // Voluntary miss for tests.
        cohort_add_member($cohort1->id, $harry->id);
        cohort_add_member($cohort1->id, $hermione->id);
        cohort_add_member($cohort2->id, $draco->id);

        $this->getDataGenerator()->enrol_user($hermione->id, $course1->id, 'student');
        $this->getDataGenerator()->enrol_user($harry->id, $course1->id, 'student');
        $this->getDataGenerator()->enrol_user($draco->id, $course2->id, 'student');
        $this->getDataGenerator()->enrol_user($minerva->id, $course1->id, 'editingteacher');

        $this->setAdminUser();

        $script = '';
        $parser = new \local_moodlescript\engine\parser($script);

        $testexpressions = file($CFG->dirroot.'/local/moodlescript/test/test_expressions.mdl');
        array_shift($testexpressions);

        $parser->trace("Testing Evaluating expressions");

        foreach ($testexpressions as $exp) {

            trim($exp, "\n");
            $parser->trace("\nUnit test : Evaluating expression : $exp");
            // Decode : <expected value> <username> <expression>
            $parts = preg_split('/[\s]+/', $exp);
            $expectedvalue = (array_shift($parts)) ? true : false;
            $currentuser = array_shift($parts);
            if ($currentuser == '*') {
                $this->setUser($USER);
            } else {
                $runninguser = $DB->get_record('user', ['username' => $currentuser]);
                $this->setUser($runninguser);
            }
            $expression = implode(' ', $parts);

            if (!empty(trim($expression))) {

                $evaluable = new \local_moodlescript\engine\evaluable_expression($expression, $parser);
                $result = $evaluable->parse();

                if (is_null($result[0])) {
                    $parser->trace("Bad parsing of $expression");
                }
                // Could the expression be parsed ? 
                $this->assertNotNull($result[0]);
                $this->assertNotNull($result[1]);

                $eval = $evaluable->evaluate();
                $this->assertEquals($expectedvalue, $eval);
            }
        }
    }

    /*
     * Testing with using variable
     */
    public function test_variables() {
        global $DB, $CFG, $SITE;

        $testscript = implode("\n", file($CFG->dirroot.'/local/moodlescript/test/variable_processing.mdl'));

        $this->resetAfterTest();

        $parser = new \local_moodlescript\engine\parser($testscript);
        $globalcontext = array('site' => $SITE->fullname);
        $stack = $parser->parse($globalcontext);

        $this->assertNotNull($stack);
    }
}