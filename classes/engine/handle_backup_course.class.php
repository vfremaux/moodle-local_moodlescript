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

class handle_backup_course extends handler {

    public function execute($result, &$context, &$stack) {
        global $DB, $CFG;

        $this->stack = &$stack;

        if ($context->backupcourseid == 'current') {
            $courseid = $context->courseid;
        } else {
            $courseid = $context->backupcourseid;
        }

        $course = $DB->get_record('course', array('id' => $courseid));

        if ($context->target == 'publishflow') {
            // At the moment, try to avoid any output here.
            ob_start();
            include_once($CFG->dirroot.'/blocks/publishflow/backup/backup_automation.class.php');
            \backup_automation::run_publishflow_coursebackup($course->id);
            \backup_automation::remove_excess_publishflow_backups($course);
            ob_end_clean();
            $this->log('Course backup completed.');
        }
    }

    public function check(&$context, &$stack) {
        global $DB;

        $this->stack = &$stack;

        if (empty($context->backupcourseid)) {
            $this->error('Empty backup course id');
        }

        if ($context->backupcourseid != 'current') {
            if (!is_numeric($context->backupcourseid)) {
                $this->error('Backup target id is not a number');
            }
        } else {
            $context->backupcourseid = $context->courseid;
        }

        if (!$course = $DB->get_record('course', array('id' => $context->backupcourseid))) {
            $this->error('Backup target course does not exist');
        }
    }
}