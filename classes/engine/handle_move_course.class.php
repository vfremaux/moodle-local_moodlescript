<?php


namespace local_moodlescript\engine;

defined('MOODLE_INTERNAL') || die;

class handle_move_course extends handler {

    public function execute($result, &$context, &$logger) {
        global $DB;

        $this->log = &$logger;

        if ($context->movecourseid == 'current') {
            $course = $DB->get_record('course', array('id' => $context->courseid));
        } else {
            $course = $DB->get_record('course', array('id' => $context->movecourseid));
        }

        // Update course cleanly, purging caches and making all required fixes everywhere
        $updatedcourse = new \StdClass;
        $updatedcourse->id = $course->id;
        $updatedcourse->category = $context->coursecatid;
        $this->log('Moving course '.$course->id.' to category '.$context->coursecatid);
        update_course($updatedcourse);
    }

    public function check(&$context, &$logger) {
        global $DB;

        $this->log = &$logger;

        if ($context->movecourseid != 'current') {
            if (!$course = $DB->get_record('course', array('id' => $context->movecourseid))) {
                $this->error('Target course does not exist');
            }
        }

        if (empty($context->coursecatid)) {
            $this->error('Missing or empty coursecat id');
        }

    }
}