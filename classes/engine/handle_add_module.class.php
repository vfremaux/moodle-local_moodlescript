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

use \StdClass;
require_once($CFG->dirroot.'/local/moodlescript/classes/exceptions/execution_exception.class.php');

class handle_add_module extends handler {

    public function execute(&$results, &$stack) {
        global $DB;

        $this->stack = $stack;
        $context = $this->stack->get_current_context();

        $module = $DB->get_record('modules', ['name' => $context->modtype]);
        if ($module->visible == 0) {
            throw new execution_exception("Add Module Runtime : Module is disabled in administration");
        }

        $instance = new StdClass;
        $instance->course = $context->modcourseid;
        // Load instance with all instance known attributes.
        foreach ($context->params as $key => $value) {
            if (!in_array($key, ['idnumber', 'visible'])) {
                $instance->$key = $value;
            }
        }

        // Load lib for the module class
        $libfile = $CFG->dirroot.'/mod/'.$context->modtype.'/lib.php';
        $addfunc = $context->modtype.'_add_instance';
        $instance->id = $addfunc($instance);

        // Now make a suitable CM
        $cm = new StdClass;
        $cm->instance = $instance->id;
        $cm->module = $module->id;

        // Resolve courseid if runtime
        if ($context->modcourseid == 'current') {
            $cm->course = $context->courseid;
        } else {
            $cm->course = $context->modcourseid;
        }

        if ($this->is_runtime($cm->course)) {
            $identifier = new parse_identifier('course', $this);
            $cm->course = $identifier->parse($cm->course, 'idnumber', 'runtime');
        }

        if ($context->targettype == 'section') {
            $cm->section = $context->modsectionid;
        } else {
            // Resolve pageid if runtime
            if ($context->modpageid == 'current') {
                $context->modpageid = $context->pageid;
            }

            if ($this->is_runtime($context->modpageid)) {
                $identifier = new parse_identifier('format_page', $this);
                $context->modpageid = $identifier->parse($context->modpageid, 'idnumber', 'runtime');
            }

            if (!$page = $DB->get_record('format_page', ['id' => $context->modpageid])) {
                throw new execution_exception("Add Module runtime : Page format page {$context->modpageid} does not exist.");
            }

            $cm->section = $page->sectionid;
        }

        $cm->idnumber = '';
        if (!empty($context->params->idnumber)) {
            $cm->idnumber = $context->params->idnumber;
        }

        $cm->visible = 1;
        if (isset($context->params->visible)) {
            $cm->visible = $context->params->visible;
        }

        // Insert the course module in course.
        if (!$cm->id = add_course_module($cm)) {
            throw new execution_exception("Add Module Runtime : Error inserting course module");
        }

        if (!$sectionid = course_add_cm_to_section($course, $cm->id, $section)) {
            throw new execution_exception("Add Module Runtime : Error adding course module to section");
        }

        if (!$DB->set_field('course_modules', 'section', $sectionid, array('id' => $cm->id))) {
            throw new execution_exception("Add Module Runtime : Error in postbinding section to module");
        }

        if (!$this->stack->is_context_frozen()) {
            $this->stack->update_current_context('moduleid', $moduleid);
        }

        $results[] = $moduleid;
        return $moduleid;
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

        $module = $DB->get_record('modules', ['name' => $context->modtype]);

        if (!$module) {
            $this->error("Check Add Module : No such module {$context->modtype}");
        }

        if ($module->visible == 0) {
            $this->error("Check Add Module : Module {$context->modtype} is disabled in administration");
        }

        if ($context->targettype == 'section') {
            if (empty($context->modsectionid)) {
                $this->error("Check Add Module : No section id given");
            }

            if (!$this->is_runtime($context->modsectionid)) {
                if (!$DB->record_exists('course_sections', ['id' => $context->modsectionid])) {
                    $this->error("Check Add Module : Section {$context->modsectionid} does not exist");
                }
            }
        } else {
            if (!is_dir($CFG->dirroot.'/course/format/page')) {
                $this->error("Check Add Module : Adding to a format page but not installed.");
            }

            if (empty($context->modpageid)) {
                $this->error("Check Add Module : No page id given");
            }

            if ($context->modpageid != 'current') {
                if (!$this->is_runtime($context->modpageid)) {
                    if (!$DB->record_exists('format_page', ['id' => $context->modpageid])) {
                        $this->error("Check Add Module : Course page {$context->modpageid} does not exist");
                    }
                }
            }
        }
    }
}