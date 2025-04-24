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

require_once($CFG->dirroot.'/course/format/page/classes/page.class.php');
require_once($CFG->dirroot.'/course/format/page/classes/tree.class.php');

use stdClass;
use moodle_exception;
use format_page\course_page;

class handle_add_coursepage extends handler {

    public function execute(&$results, &$stack) {
        global $DB;

        $this->stack = $stack;
        $context = $this->stack->get_current_context();

        if (!empty($context->addcourseid)) {
            if ($context->addcourseid == 'current') {
                $context->addcourseid = $context->courseid;
            }

            if (!$DB->record_exists('course', ['id' => $context->addcourseid])) {
                throw new moodle_exception("Add CoursePage runtime : Target course {$context->addcourseid} does not exist");
            }
        } else {
            // This is a subpage.
            if ($context->addparentid == 'current') {
                $context->addparentid = $context->pageid;
            }

            if (!$DB->record_exists('format_page', ['id' => $context->addparentid])) {
                throw new moodle_exception("Add CoursePage runtime : Target course page {$context->addparentid} does not exist");
            }
        }

        // Create a format page
        $coursepage = new course_page;
        $coursepage->nameone = $context->fullname;

        if (empty($context->params->nametwo)) {
            $coursepage->nametwo = $coursepage->nameone;
        } else {
            $coursepage->nametwo = $context->params->nametwo;
        }

        if (empty($context->params->displaymenu)) {
            $coursepage->displaymenu = 1;
        } else {
            $coursepage->displaymenu = $context->params->displaymenu;
        }

        if (empty($context->params->display)) {
            $coursepage->display = course_page::$displaycodes['PUBLISHED'];
        } else {
            $coursepage->display = course_page::$displaycodes[$context->params->display];
        }

        if (!$this->stack->is_context_frozen()) {
            $this->stack->update_current_context('pageid', $pageid);
        }

        if (!empty($context->addcourseid)) {
            $coursepage->parent = 0;
            $coursepage->courseid = $context->addcourseid;
        } else {
            $coursepage->parent = $context->addparentid;
            $parentpage = course_page::get($context->addparentid);
            $coursepage->courseid = $parentpage->courseid;
        }

        // Add all other attributes if exist in Having.
        $pageattributes = ['idnumber', 'prefleftwidth', 'prefcenterwidth', 'prefrightwidth',
                            'bsprefleftwidth', 'bsprefcenterwidth', 'bsprefrightwidth',
                            'template', 'globaltemplate', 'showbuttons',
                            'datefrom', 'dateto', 'relativeweek'];
        foreach ($pageattributes as $attr) {
            if (!empty($context->params->$attr)) {
                $coursepage->$attr = $context->params->$attr;
            }
        }

        // locates it and save it.
        if ($context->location == 'AFTER') {
            $prevcoursepage = course_page::get($context->locationid);
            $sortorder = $prevcoursepage->sortorder + 1;
        } else if ($context->location == 'BEFORE') {
            $prevcoursepage = course_page::get($context->locationid);
            $sortorder = $prevcoursepage->sortorder;
        } else if ($context->location == 'AT START') {
            $sortorder = 0;
        } else if ($context->location == 'AT END') {
            $sortorder = tree::get_next_page_sortorder($coursepage->courseid, $coursepage->parent);
        }

        // Eventually freeses the slot.
        tree::insert_page_sortorder($prevcoursepage->courseid, $prevcoursepage->parent, $sortorder);
        $coursepage->sortorder = $sortorder;
        $coursepage->save();
        tree::fix($prevcoursepage->courseid); // Fix all sections and orders.

        if (!$this->stack->is_context_frozen()) {
            $this->stack->update_current_context('pageid', $coursepage->id);
        }

        $results[] = $coursepage->id;
        return $coursepage->id;
    }

    /**
     * Remind that Check MUST NOT alter the context. Just execute any pre-execution tests that might 
     * be necessary.
     * @param $array &$stack the script stack.
     */
    public function check(&$stack) {
        global $DB, $CFG;

        $this->stack = $stack;
        $context = $this->stack->get_current_context();

        if (empty($context->fullname)) {
            $this->error("Check Add CoursePage : Empty page name");
        }

        if (!empty($context->addcourseid)) {
            if ($context->addcourseid != 'current') {
                if (!$DB->record_exists('course', ['id' => $context->addcourseid])) {
                    $this->error("Check Add CoursePage : Target course {$context->addcourseid} does not exist");
                }
            }
        } else {
            // This is a subpage.
            if (empty($context->addparentid)) {
                $this->error("Check Add CoursePage : Target page ID is empty");
            }

            if ($context->addparentid != 'current') {
                if (!$DB->record_exists('format_page', ['id' => $context->addparentid])) {
                    throw new moodle_exception("Check Add CoursePage : Target course page {$context->addparentid} does not exist");
                }
            }
        }
    }
}