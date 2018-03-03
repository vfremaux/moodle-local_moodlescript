<?php


namespace local_moodlescript\engine;

defined('MOODLE_INTERNAL') || die;

class handle_show_block extends handler {

    public function execute($result, &$context, &$logger) {
        global $DB;

        $this->log = $logger;

        $parentcontext = context_course::instance($context->showcourseid);

        $params = array('blockname' => $context->blockname, 'parentcontextid' => $parentcontext->id);
        $blockinstances = $DB->get_records('block_instances', $params);
        foreach ($blockinstances as $bi) {
            $bi->visible = 1;
            $DB->update_record('block_instances', $bi);
        }
    }

    public function check(&$context, &$logger) {
        global $DB;

        $this->log = $logger;

        if (empty($context->blockname)) {
            $this->error('Empty block name');
        }

        $block = $DB->get_record('block', array('name' => $context->blockname));
        if (empty($block)) {
            $this->error('Block is not installed');
        } else {
            if ($block->visible) {
                $this->error('Block is not enabled');
            }
        }

        if (empty($context->hidecourseid)) {
            $this->error('Empty course id');
        }

        if ($context->hidecourseid != 'current') {
            if (!is_numeric($context->hidecourseid)) {
                $this->error('Target course id is not a number');
            }
        }

        if (!$course = $DB->get_record('course', array('id' => $context->hidecourseid))) {
            $this->error('Target course does not exist');
        }

        return (!empty($this->errorlog));
    }
}