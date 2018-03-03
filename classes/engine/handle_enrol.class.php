<?php


namespace local_moodlescript\engine;

defined('MOODLE_INTERNAL') || die;

class handle_enrol extends handler {

    public function execute($result, &$context, &$logger) {
        global $DB;

        $this->log = &$logger;

        if ($context->enrolcourseid == 'current') {
            $course = $DB->get_record('course', array('id' => $context->courseid));
        } else {
            $course = $DB->get_record('course', array('id' => $context->enrolcourseid));
        }

        $enrolplugin = enrol_get_plugin($context->method);
        $params = array('enrol' => $context->method, 'courseid' => $course->id, 'status' => ENROL_INSTANCE_ENABLED);
        $enrols = $DB->get_records('enrol', $params);
        $enrol = reset($enrols);

        $starttime = time();
        if (!empty($context->params->starttime)) {
            $starttime = $context->params->starttime;
        }

        $endtime = 0;
        if (!empty($context->params->endtime)) {
            $starttime = $context->params->endtime;
        }

        if ($context->method == 'sync') {
            \enrol_sync_plugin::static_enrol_user($course, $context->userid, $context->roleid, $starttime, $endtime);
            $this->log("User {$context->userid} enrolled in course {$course->id} using sync plugin");
        } else {
            $enrolplugin->enrol_user($enrol, $context->userid, $context->roleid, $starttime, $endtime, ENROL_USER_ACTIVE);
            $this->log("User {$context->userid} enrolled in course {$course->id} using {$enrol->enrol} plugin");
        }
    }

    public function check(&$context, &$logger) {
        global $DB;

        $this->log = &$logger;
        $this->errorlog = &$logger;

        if (empty($context->enrolcourseid)) {
            $this->error('Empty course id');
        }

        if ($context->enrolcourseid != 'current') {
            if (!$course = $DB->record_exists('course', array('id' => $context->enrolcourseid))) {
                $this->error('Target course does not exist');
            }
        } else {
            if (!$course = $DB->record_exists('course', array('id' => $context->courseid))) {
                $this->error('Curren course is missing or broken');
            }
        }

        if (empty($context->method)) {
            $this->error('No enrol method');
        }

        if (empty($context->userid)) {
            $this->error('No user');
        }

        if (!empty($course) && !empty($context->method)) {
            $course = $DB->get_record('course', array('id' => $context->enrolcourseid));
            $enrolplugin = enrol_get_plugin($context->method);
            $params = array('enrol' => $context->method, 'courseid' => $course->id, 'status' => ENROL_INSTANCE_ENABLED);
            if (!$enrols = $DB->get_records('enrol', $params)) {
                $this->error('No available {$context->method} enrol instances in course '.$course->id);
            }
        }

        return (!empty($this->errorlog));
    }
}