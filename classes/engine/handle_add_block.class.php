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

class handle_add_block extends handler {

    public function execute($result, &$context, &$stack) {
        global $DB;

        $this->stack = $stack;

        // Get a block instance of the block class.
        $blockinstance = block_instance($context->blockname);

        $blockrecord = new \StdClass;
        $blockrecord->blockname = $context->blockname;

        if ($context->blockcourseid == 'current') {
            $context->blockcourseid = $context->courseid;
        }

        $parentcontext = \context_course::instance($context->blockcourseid);

        // Check for unicity.
        if (!$blockinstance->instance_allow_multiple()) {
            $params = array('blockname' => $context->blockname, 'parentcontextid' => $parentcontextid);
            if ($DB->get_record('block_instances', $params)) {
                $this->error('Could not instanciate block '.$context->blockname.' because already one in course');
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
        $this->log('Block '.$context->blockname.' instance added to course '.$context->blockcourseid);

        // Now eventually relocate the block locally if additional params have been given.
        $mustrelocate = false;
        $relocation = new \StdClass;
        $relocation->region = $blockrecord->defaultregion;
        $relocation->weight = $blockrecord->defaultweight;

        if (!empty($context->location)) {
            if ($context->location == 'left') {
                $mustrelocate = true;
                $relocation->region = 'side-pre';
            } else if ($context->location = 'right') {
                $mustrelocate = true;
                $relocation->region == 'side-post';
            } else {
                $relocation->region = $context->location;
            }
        } else {
            $relocation->region = $blockrecord->defaultregion;
        }

        if (!empty($context->position)) {
            if ($context->position == 'last') {
                $params = array('region' => $blockid, 'contextid' => $parentcontext->id);
                $maxpos = $DB->get_field('block_positions', 'MAX(weight)', $params);
                $relocation->weight = $maxpos + 1;
            } else if ($context->position == 'first') {
                $params = array('region' => $blockid, 'contextid' => $parentcontext->id);
                $maxpos = $DB->get_field('block_positions', 'MIN(weight)', $params);
                $relocation->weight = $maxpos - 1;
            } else {
                $relocation->weight = $context->position;
            }

            $mustrelocate = true;
        }

        if (!empty($context->pageid)) {
            $relocation->subpage = 'page-'.$context->pageid;
        }

        if ($mustrelocate) {
            // Its a new block ! there should be no records.
            $courseformat = $DB->get_field('course', 'format', array('id' => $context->blockcourseid));
            $relocation->blockinstanceid = $blockid;
            $relocation->visible = 1;
            $pagetype = 'course-view-' . $courseformat;
            $relocation->pagetype = $pagetype;
            $relocation->subpage = '';
            $coursecontext = \context_course::instance($context->blockcourseid);
            $relocation->contextid = $coursecontext->id;
            $DB->insert_record('block_positions', $relocation);
            $this->log('Block '.$context->blockname.' relocated in course '.$context->blockcourseid);
        }


        $result[] = $blockid;
        return $result;
    }

    public function check(&$context, &$stack) {
        global $DB, $CFG;

        $this->stack = $stack;

        if (empty($context->blockname)) {
            $this->error('empty blockname');
            $block = $DB->get_record('blocks', array('name' => $context->blockname));
            if (empty($block)) {
                $this->error('this block type is not installed');
            }
            if (!$block->visible) {
                $this->error('This block type is not exnabled ');
            }
        }

        if ($context->blockcourseid == 'current') {
            $context->blockcourseid = $context->courseid;
        }

        if (!$course = $DB->get_record('course', array('id' => $context->blockcourseid))) {
            $this->error('Missing target course for block insertion');
        }

        // Add more controls on params, location and region names.
        if (!empty($context->position)) {
            if (!in_array($context->position, array('last', 'first'))) {
                if (!is_numeric($context->position)) {
                    $this->error('Block position is invalid non numeric value.');
                }
            }
        }

        // Resolve static theme application (course then category overrides).
        if (!empty($context->location) && $course) {

            if (!in_array($context->location, array('left', 'right'))) {
                if (empty($CFG->themeorder)) {
                    $themeorder = array('course', 'category');
                } else {
                    $themeorder = $CFG->themeorder;
                }

                $theme = $CFG->theme;
                foreach ($themeorder as $themetype) {

                    switch ($themetype) {
                        case 'course':
                            if (!empty($CFG->allowcoursethemes) && !empty($course->theme)) {
                                $theme = $course->theme;
                                break 2; // Break foreach. Resolution is conmplete.
                            }
                        break;

                        case 'category':
                            if (!empty($CFG->allowcategorythemes)) {
                                $category = $DB->get_record('course_categories', array('id' => $course->category));
                                while ($category) {
                                    if (!empty($category->theme)) {
                                        $theme = $category->theme;
                                        break 2; // Break foreach. Resolution is complete.
                                    }
                                    $category = $DB->get_record('course_categories', array('id' => $category->parent));
                                }
                            }
                        break;
                    }
                }

                include_once($CFG->libdir.'/outputlib.php');
                try {
                    $themeconfig = \theme_config::load($theme);
                } catch (\Exception $e) {
                    $this->error('Something wrong in theme config. '.print_r($e, true));
                    return;
                }
                if (!$themeconfig) {
                    $this->warn('undefined theme config. ');
                    return;
                }
                $layoutregions = $themeconfig->layouts['course']['regions'];
                debug_trace("Theme regions $context->location in ". print_r($layoutregions, true));
                if (!in_array($context->location, $layoutregions)) {
                    debug_trace("Bad location");
                    $this->error('Block location region '.$context->location.' name is unknown in the theme used in target course.');
                }
                debug_trace("Location resolved");
            }
        }
    }

}