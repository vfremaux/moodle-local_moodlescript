<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package local_moodlescript
 * @category local
 * @author Valery Fremaux (valery.fremaux@gmail.com)
 * @copyright (c) 2017 onwards Valery Fremaux (http://www.mylearningfactory.com)
 */
namespace local_moodlescript\engine;

defined('MOODLE_INTERNAL') || die;

class handle_hide_block extends handler {

    /**
     * Hide all instances of blockname in a course.
     */
    public function execute(&$results, &$context, &$stack) {
        global $DB;

        $this->stack = $stack;

        if ($context->hidecourseid == 'current') {
            $context->hidecourseid = $context->courseid;
        }

        $parentcontext = \context_course::instance($context->hidecourseid);
        $course = $DB->get_record('course', array('id' => $context->hidecourseid));

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
                $bp = new \StdClass;
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
                    }
                }
                $bp->region = $bi->defaultregion;
                $bp->weight = $bi->defaultweight;
                $bp->visible = 0; // The most important thing.
                $DB->insert_record('block_positions', $bp);
            }
        }
    }

    public function check(&$context, &$stack) {
        global $DB;

        $this->stack = $stack;

        if (empty($context->blockname)) {
            $this->error('Hide block : Empty block name');
        }

        $block = $DB->get_record('block', array('name' => $context->blockname));
        if (empty($block)) {
            $this->error('Hide block : Block is not installed');
        } else {
            if ($block->visible) {
                $this->error('Hide block : Block is not enabled');
            }
        }

        if (empty($context->hidecourseid)) {
            $this->error('Empty course id');
        }

        if ($context->hidecourseid == 'current') {
            $context->hidecourseid = $context->courseid;
        }

        if (!is_numeric($context->hidecourseid)) {
            $this->error('Hide course : Target course id is not a number');
        }

        if (!$course = $DB->get_record('course', array('id' => $context->hidecourseid))) {
            $this->error('Hide course : Target course does not exist');
        }

        if ($course->format == 'page') {
            if (!empty($context->pageid)) {
                if (!$page = $DB->get_record('format_page', array('id' => $context->pageid))) {
                    $this->error('Hide block : Target course page (page format) does not exist');
                } else {
                    if ($page->courseid != $course->id) {
                        $this->error('Hide block : Target course page (page format) exists, but not in the required course');
                    }
                }
            } else {
                // If pageid not given, then hide all blocks if this type in the course.
                // this might be done using the format_page_item more easily.
                assert(1);
            }
        }
    }
}