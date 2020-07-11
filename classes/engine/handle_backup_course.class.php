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

require_once($CFG->dirroot.'/local/moodlescript/classes/exceptions/execution_exception.class.php');

use \backup;
use \backup_controller;
use \backup_plan_dbops;

defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

class handle_backup_course extends handler {

    public function execute(&$results, &$context, &$stack) {
        global $DB, $CFG;

        $this->stack = $stack;

        if ($context->backupcourseid == 'current') {
            $context->backupcourseid = $context->courseid;
        }

        if (!$course = $DB->get_record('course', array('id' => $context->backupcourseid))) {
            throw new execution_exception('Backup course Runtime : Backup target course does not exist');
        }

        if ($context->target == 'publishflow') {
            // At the moment, try to avoid any output here.
            ob_start();
            include_once($CFG->dirroot.'/blocks/publishflow/backup/backup_automation.class.php');
            \backup_automation::run_publishflow_coursebackup($course->id);
            \backup_automation::remove_excess_publishflow_backups($course);
            ob_end_clean();
            $this->log('Course backup completed.');
        // } else if ($context->target == 'course') {
    } else {
            // At the moment, course is the only alternative.
            // Make a backup with potentiential options.
            // Uses the default backup options in moodle course admin.
            $admin = get_admin();

            $bc = new backup_controller(backup::TYPE_1COURSE, $course->id, backup::FORMAT_MOODLE,
                                        backup::INTERACTIVE_NO, backup::MODE_GENERAL, $admin->id);
            // Set the default filename.
            $format = $bc->get_format();
            $type = $bc->get_type();
            $id = $bc->get_id();
            $users = $bc->get_plan()->get_setting('users')->get_value();
            $anonymised = $bc->get_plan()->get_setting('anonymize')->get_value();
            $filename = backup_plan_dbops::get_default_backup_filename($format, $type, $id, $users, $anonymised);
            $bc->get_plan()->get_setting('filename')->set_value($filename);

            // Execution.
            $bc->execute_plan();
            $bc->destroy();
        }

        return true;
    }

    public function check(&$context, &$stack) {
        global $DB;

        $this->stack = $stack;

        if (empty($context->backupcourseid)) {
            $this->error('Backup course : Empty backup course id');
        }

        if ($context->backupcourseid != 'current') {
            if (!is_numeric($context->backupcourseid)) {
                $this->error('Backup course : Backup target id is not a number');
            }
            if (!$course = $DB->get_record('course', array('id' => $context->backupcourseid))) {
                $this->error('Backup course : Target course does not exist');
            }
        }
    }
}