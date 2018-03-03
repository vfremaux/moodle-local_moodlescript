<?php


namespace local_moodlescript\engine;

defined('MOODLE_INTERNAL') || die;

class handle_unenrol extends handler {

    public function execute($result, &$context, &$logger) {
        global $DB;

        $this->log = $logger;

    }

    public function check(&$context, &$logger) {
        global $DB;

        $this->log = $logger;

        if (empty($context->unenrolcourseid)) {
            $this->error('Empty course id');
        }

        if (empty($context->method)) {
            $this->error('No enrol method');
        }

        if (empty($context->userid)) {
            $this->error('No user');
        }

        return (!empty($this->errorlog));
    }
}