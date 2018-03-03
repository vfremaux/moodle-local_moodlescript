<?php


namespace local_moodlescript\engine;

defined('MOODLE_INTERNAL') || die;

class handle_backup_course extends handler {

    public function execute($result, &$context, &$logger) {
        global $DB, $CFG;

        $this->log = &$logger;

        if ($context->backupcourseid == 'current') {
            $courseid = $context->courseid;
        } else {
            $courseid = $context->backupcourseid;
        }

        $course = $DB->get_record('course', array('id' => $courseid));

        if ($context->target == 'publishflow') {
            include_once($CFG->dirroot.'/blocks/publishflow/backup/backup_automation.class.php');
            \backup_automation::run_publishflow_coursebackup($course->id);
            \backup_automation::remove_excess_publishflow_backups($course);
        }

    }

    public function check(&$context, &$logger) {
        global $DB;

        $this->log = &$logger;
        $this->errorlog = &$logger;

        if (empty($context->backupcourseid)) {
            $this->error('Empty backup course id');
        }

        if ($context->backupcourseid != 'current') {
            if (!is_numeric($context->backupcourseid)) {
                $this->error('Backup target id is not a number');
            }
        }

        if (!$course = $DB->get_record('course', array('id' => $context->backupcourseid))) {
            $this->error('Backup target course does not exist');
        }

        return (!empty($this->errorlog));
    }
}