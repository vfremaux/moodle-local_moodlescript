<?php


namespace local_moodlescript\engine;

defined('MOODLE_INTERNAL') || die;

class handle_echo extends handler {

    public function execute($result, &$context, &$logger) {
        global $DB, $CFG;

        $this->log = &$logger;

        $this->log($context->argument);
    }

    public function check(&$context, &$logger) {
        global $DB;

        $this->log = &$logger;
        $this->errorlog = &$logger;

        if (empty($context->argument)) {
            $this->error('Nothing to print');
        }

        return (!empty($this->errorlog));
    }
}