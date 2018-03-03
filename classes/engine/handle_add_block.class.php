<?php


namespace local_moodlescript\engine;

defined('MOODLE_INTERNAL') || die;

class handle_add_block extends handler {

    public function execute($result, &$context, &$logger) {
        global $DB;

        // Get a block instance of the block class.
        $blockinstance = block_instance($context->blockname);

        $blockrecord = new StdClass;
        $blockrecord->blockname = $context->blockname;

        if ($context->blockcourseid == 'current') {
            $parentcontext = context_course::instance($context->courseid);
        } else {
            $parentcontext = context_course::instance($context->blockcourseid);
        }

        // Check for unicity.
        if (!$blockinstance->allow_multiple()) {
            $params = array('blockname' => $context->blockname, 'parentcontextid' => $parentcontextid);
            if ($DB->get_record('block_instances', $params)) {
                $logger[] = 'Could not instanciate block '.$context->blockname.' because already one in course';
                return $result;
            }
        }

        $blockrecord->parentcontextid = $parentcontext->id;
        $blockrecord->showinsubcontexts = 0;
        $blockrecord->pagetypepattern = 'course-view-*';
        $blockrecord->defaultregion = 'side-post';
        $blockrecord->defaultweight = 0;

        if (!empty($context->params->configdata)) {
            $blockrecord->configdata = $context->params->configdata;
        }

        $blockid = $DB->insert_record('block_instances', $blockrecord);

        $result[] = $blockid;
        return $result;
    }

    public function check(&$context, &$logger) {
        global $DB;

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

        return (!empty($this->errorlog));
    }

}