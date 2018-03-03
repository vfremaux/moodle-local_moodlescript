<?php


namespace local_moodlescript\engine;

defined('MOODLE_INTERNAL') || die;

class handle_list_globals extends handler {

    public function execute($result, &$context, &$logger) {
        global $DB;

        $this->log = &$logger;

        $this->log("GLOBAL CONTEXT");
        foreach ($context as $key => $value) {
            $this->log("$key: $value");
        }
        $this->log("\n");
    }

    public function check(&$context, &$logger) {
        global $DB;

        $this->log = &$logger;
        $this->errorlog = &$logger;

        return false;
    }
}