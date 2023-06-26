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
require_once($CFG->dirroot.'/lib/filestorage/tgz_packer.php');

use StdClass;
use context_course;
use context_system;
use backup;
use restore_controller;
use restore_dbops;
use tgz_packer;

defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

class handle_restore_course extends handler {

    public function execute(&$results, &$stack) {
        global $DB, $CFG, $USER;

        $this->stack = $stack;
        $context = $this->stack->get_current_context();

        if (!empty($context->params->shortname)) {
            if ($oldcourse = $DB->get_record('course', ['shortname' => $context->params->shortname])) {
                if (!$this->stack->is_context_frozen()) {
                    $this->stack->update_current_context('courseid', $oldcourse->id);
                    $this->stack->update_current_context('coursecatid', $oldcourse->category);
                }
                $this->warn("Restore course : a course in the way with shortname {$context->params->shortname}. Resuming...");
                return $oldcourse->id;
            }
        }

        if (empty($context->filepath)) {
            // Restore from a backup file stored in a source area in a course.

            if ($context->restorecourseid == 'current') {
                // If current restore a new course from the current course backup.
                // Seems like it is a course copy if the backup is up to date.
                $courseid = $context->courseid;
            } else {
                $courseid = $context->restorecourseid;
            }
            $course = $DB->get_record('course', array('id' => $courseid));
        }

        if ($context->restorecategoryid == 'current') {
            $coursecatid = $context->coursecatid;
        } else {
            $coursecatid = $context->restorecategoryid;
        }

        $category = $DB->get_record('course_categories', array('id' => $coursecatid));

        if ($context->source == 'publishflow') {
            include_once($CFG->dirroot.'/blocks/publishflow/lib.php');
            $context->courseid = publishflow_local_deploy($category, $course);

            $this->log('Course restore completed by publishfow.');
            return;
        }

        $fs = get_file_storage();

        // Get or make an archivefile
        if (!empty($context->filepath)) {

            $contextid = \context_user::instance($USER->id)->id;

            $fs->delete_area_files($contextid, 'user', 'draft', 0);

            $filerec = new StdClass;
            $filerec->contextid = $contextid;
            $filerec->component = 'user';
            $filerec->filearea = 'draft';
            $filerec->itemid = 0;
            $filerec->filepath = '/';
            $filerec->filename = basename($context->filepath);
            $archivefile = $fs->create_file_from_pathname($filerec, $context->filepath);
        } else {
            // Find archive file in course scope. Take most recent.
            $coursecontext = context_course::instance($courseid);
            $files = $fs->get_area_files($coursecontext->id, 'backup', $context->source, 0, 'timecreated DESC', false);

            if (count($files) > 0) {
                $archivefile = array_shift($files); // "shift" ! not "pop" to get the newest.
            }
        }

        if (!empty($archivefile)) {
            // At the moment, course is the only alternative.
            $contextid = context_system::instance()->id;
            $component = 'local_moodlescript'; // Temporary area.
            $filearea = 'temp';
            $itemid = $uniq = 9999999 + rand(0, 100000);
            $tempdir = $CFG->tempdir."/backup/$uniq";

            if (!is_dir($tempdir)) {
                mkdir($tempdir, 0777, true);
            }

            if (!$archivefile->extract_to_pathname(new tgz_packer(), $tempdir)) {
                throw new Exception('Fatal error : Restore unpack error');
            }

            // Transaction.
            $transaction = $DB->start_delegated_transaction();

            // Create new course.
            $userdoingtherestore = $USER->id; // E.g. 2 == admin.
            $newcourseid = restore_dbops::create_new_course('', '', $category->id);
            if (empty($newcourseid)) {
                $this->error("Could not get a new course in category {$category->id}.");
            }

            // Restore backup into course.
            $controller = new restore_controller($uniq, $newcourseid,
                backup::INTERACTIVE_NO, backup::MODE_SAMESITE, $userdoingtherestore,
                backup::TARGET_NEW_COURSE );
            $controller->execute_precheck();
            $controller->execute_plan();

            // Commit.
            $transaction->allow_commit();

            // Update names.
            if ($newcourse = $DB->get_record('course', array('id' => $newcourseid))) {
                if (!empty($context->params->fullname)) {
                    $newcourse->fullname = $context->params->fullname;
                }
                if (!empty($context->params->shortname)) {
                    $newcourse->shortname = $context->params->shortname;
                }
                if (!empty($context->params->idnumber)) {
                    $newcourse->idnumber = $context->params->idnumber;
                }
                if (!empty($context->params->summary)) {
                    $newcourse->summary = $data->summary;
                }
                $DB->update_record('course', $newcourse);
                $this->log("Course [{$newcourse->shortname}] \"{$newcourse->fullname}\" restored with id $newcourseid.<br/>");
            } else {
                $this->error("Course restore failed at id $newcourseid.");
            }

            // Cleanup temp file area.
            $fs = get_file_storage();
            $fs->delete_area_files($contextid, 'local_moodlescript', 'temp');

            if (!$this->stack->is_context_frozen()) {
                $this->log("Updating context after restore courseid = $newcourseid in stack ".$this->stack->get_id()."<br/>");
                $this->stack->update_current_context('courseid', $newcourseid);
                $this->stack->update_current_context('coursecatid', $category->id);
            }
        } else {
            if (!empty($courseid)) {
                $this->error("No archive file found in context of course $courseid");
            }
        }
    }

    /**
     * Remind that Check MUST NOT alter the context. Just execute any pre-execution tests that might 
     * be necessary.
     * @param $array &$stack the script stack.
     */
    public function check(&$stack) {
        global $DB;

        $this->stack = $stack;
        $context = $this->stack->get_current_context();

        if (!empty($context->filepath)) {
            if (!file_exists($context->filepath)) {
                $this->error('Check Restore Course : backup file not found');
            }

            if (!is_readable($context->filepath)) {
                $this->error('Check Restore Course : backup file not readable. this might be a permission issue.');
            }

            if (empty($context->restorecategoryid)) {
                $this->error('Check Restore Course : category id must be provided');
            }

            if (!is_numeric($context->restorecategoryid)) {
                $this->error('Check Restore Course : category id is not a number');
            }
        } else {

            if (empty($context->restorecourseid)) {
                $this->error('Check Restore Course : Empty restore course id');
            }

            if (empty($context->source)) {
                // Should not happen. Controlled in parser.
                $this->error('Check Restore Course : Empty restore source');
            }

            if ($context->source == 'publishflow')  {
                if (!is_dir($CFG->dirroot.'/blocks/publishflow')) {
                    $this->error('Check Restore Course : Publishflow not installed');
                }
            }

            if ($context->restorecourseid != 'current') {
                if (!is_numeric($context->restorecourseid)) {
                    $this->error('Check Restore Course : source id is not a number');
                }
            } else {
                $context->restorecourseid = $context->courseid;
            }

            if (!$course = $DB->get_record('course', array('id' => $context->restorecourseid))) {
                $this->error('Check Restore Course : source course does not exist');
            }

            if ($context->restorecategoryid != 'current') {
                if (!is_numeric($context->restorecategoryid)) {
                    $this->error('Check Restore Course : category id is not a number');
                }
                $execcategory = $context->restorecategoryid;
            } else {
                if (empty($context->coursecatid)) {
                    $this->error('Check Restore Course : No category id origin for resolving "current"');
                }
                $execcategory = $context->coursecatid;
            }

            // Other checks : check filearea has a valid backup file.
            $fs = get_file_storage();
        }

        if (!$category = $DB->get_record('course_categories', array('id' => $execcategory))) {
            $this->error('Check Restore Course : target category does not exist');
        }
    }
}