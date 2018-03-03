<?php


namespace local_moodlescript\engine;

defined('MOODLE_INTERNAL') || die;

class handle_hide_block extends handler {

    /**
     * Hide all instances of blockname in a course.
     */
    public function execute($result, &$context, &$logger) {
        global $DB;

        $this->log = &$logger;

        if ($context->hidecourseid == 'current') {
            $parentcontext = context_course::instance($context->courseid);
            $course = $DB->get_record('course', array('id' => $context->courseid));
        } else {
            $parentcontext = context_course::instance($context->hidecourseid);
            $course = $DB->get_record('course', array('id' => $context->hidecourseid));
        }

        $params = array('blockname' => $context->blockname, 'parentcontextid' => $parentcontext->id);
        $blockinstances = $DB->get_records('block_instances', $params);
        foreach ($blockinstances as $bi) {
            $bps = $DB->get_records('block_positions', array('blockinstanceid' => $bi->id));
            if ($bps) {
                // For those positions we already have info on.
                foreach ($bps as $bp) {
                    $bp->visible = 0;
                    $DB->update_record('block_positions', $bp);
                }
            } else {
                // No info about any exmplicit position. We need to create some.
                $bp = new StdClass;
                $bp->blockinstanceid = $bi->id;
                $bp->contextid = $bi->parentcontextid;
                // We hide it in the course only. Not in subcontexts.
                $pagetype = 'course-view-' . $course->format;
                $bp->pagetype = $pagetype;
                if ($course->format != 'page') {
                    // This is the usual case.
                    $bp->subpage = '';
                } else {
                    if (!empty($context->pageid)) {
                        $bp->subpage = 'page-'.$context->pageid;
                    } else {
                        
                    }
                }
                $bp->region = $bi->defaultregion;
                $bp->weight = $bi->defaultweight;
                $bp->visible = 0; // The most important thing.
                $DB->insert_record('block_positions', $bp);
            }
        }
    }

    public function check(&$context, &$logger) {
        global $DB;

        $this->log = &$logger;

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
                $this->error('target course id is not a number');
            }
        }

        if (!$course = $DB->get_record('course', array('id' => $context->hidecourseid))) {
            $this->error('Target course does not exist');
        }

        if ($course->format == 'page') {
            if (!empty($context->pageid)) {
                if (!$page = $DB->get_record('format_page', array('id' => $context->pageid))) {
                    $this->error('Target course page (page format) does not exist');
                } else {
                    if ($page->courseid != $course->id) {
                        $this->error('Target course page (page format) exists, but not in the required course');
                    }
                }
            } else {
                // If pageid not given, then hide all blocks if this type in the course.
                // this might be done using the format_page_item more easily.
                assert(1);
            }
        }

        return (!empty($this->errorlog));
    }
}