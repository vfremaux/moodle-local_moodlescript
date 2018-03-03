<?php


namespace local_moodlescript\engine;

defined('MOODLE_INTERNAL') || die;

class handle_remove_enrol_method extends handler {

    public function execute($result, &$context, &$logger) {

        $this->log = &$logger;

        if ($context->enrolcourseid == 'current') {
            $courseid = $context->courseid;
        } else {
            $courseid = $context->enrolcourseid;
        }

        $plugin = enrol_get_plugin($context->method);
        $enabledinstances = enrol_get_instances($courseid, true);
        $todelete = array();
        if ($enabledinstances) {
            foreach ($enabledinstances as $enrolinstance) {
                if ($enrolinstance->enrol == $context->method) {
                    $todelete[] = $enrolinstanc;
                }
            }
        }
        if (!empty($todelete)) {
            $i = 0;
            foreach ($todelete as $td) {
                $plugin->delete_instance($td);
                $i++;
            }
            $this->log("Deleted $i instances of {$context->method} enrol instances");
        } else {
            $this->log('Nothing done');
        }
    }

    public function check(&$context, &$logger) {

        $this->log = &$logger;
        $this->errorlog = &$logger;

        if (empty($context->method)) {
            $this->error('Missing enrol method for deletion');
        }

        if ($context->enrolcourseid != 'current') {
            if (!$DB->record_exists('course', array('id' => $context->enrolcourseid))) {
                $this->error('Target course does not exist');
            }
        }

        return (!empty($this->errorlog));
    }
}