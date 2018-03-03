<?php


namespace local_moodlescript\engine;

defined('MOODLE_INTERNAL') || die;

class handle_remove_block extends handler {

    public function execute($result, &$context, &$logger) {
        global $DB;

        $this->log = &$logger;

        if ($context->blockcourseid == 'current') {
            $parentcontext = context_course::instance($context->courseid);
        } else {
            $parentcontext = context_course::instance($context->blockcourseid);
        }

        $params = array('blockname' => $context->blockname, 'parentcontextid' => $parentcontext->id);
        $instances = $DB->get_records('block_instances', $params);
        if ($instances) {
            foreach ($instances as $instance) {
                blocks_delete_instance($instance);
            }
        }
    }

    public function check(&$context, &$logger) {
        global $DB;

        $this->log = &$logger;

        if (empty($context->blockname)) {
            $this->error('empty blockname');
            $block = $DB->get_record('blocks', array('name' => $context->blockname));
            if (empty($block)) {
                $this->error('block not installed');
            }
            if (!$block->visible) {
                $this->error('block not enabled');
            }
        }

        if (empty($context->blockcourseid) && $context->blockcourseid != 'current') {
            if (!$DB->get_record('course', array('id' => $context->blockcourseid))) {
                $this->error('Target course '.$context->blockcourseid.' does not exist');
            }
        }

        return (!empty($this->errorlog));
    }

}