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

require_once($CFG->dirroot.'/lib/enrollib.php');

defined('MOODLE_INTERNAL') || die;

class handle_add_enrol_method extends handler {

    public function execute(&$results, &$stack) {
        global $DB;

        // Pass incoming context to internals.
        $this->stack = $stack;
        $context = $this->stack->get_current_context();

        $plugin = enrol_get_plugin($context->method);
        if ($context->enrolcourseid == 'current') {
            $course = $DB->get_record('course', array('id' => $context->courseid));
        } else {
            $course = $DB->get_record('course', array('id' => $context->enrolcourseid));
        }

        // Shift some entries to what moodle expects internally.
        if (isset($context->params->role)) {
            $role = $DB->get_record('role', array('shortname' => $context->params->role));
            $context->params->role = $role->id;
        }

        if (method_exists($plugin, 'script_attributes')) {
            // Translate the input map to internal storage given by the plugin.
            $attrmap = $plugin->script_attributes(true);
            $input = array();
            foreach ($context->params as $key => $value) {
                $input[$attrmap[$key]] = $value;
            }
        } else if ($context->method == 'meta') {
            // Hardcoded well-known mapping.
            $input['customint1'] = $context->params->supercourseid;
        } else {
            /**
             * Direct straight mapping. May not be very comfortable for scripters as
             * they will have to know the mapping of generic attributes such as customint1 customint2 etc.
             */
            $input = (array)$context->params;
        }

        $plugin->add_instance($course, $input);
        $this->log('Add Enrol Method : Enrol instance of "'.$this->context->method.'" added to course '.$course->id);
    }

    /**
     * Remind that Check MUST NOT alter the context. Just execute any pre-execution tests that might 
     * be necessary.
     * @param $array &$stack the script stack.
     */
    public function check(&$stack) {
        global $DB;

        // Pass incoming context to internals. Some calls may use them.
        $this->stack = $stack;
        $context = $this->stack->get_current_context();

        if (empty($context->method)) {
            $this->error('Add enrol method : Empty method name');
        }

        $plugin = enrol_get_plugin($context->method);
        if (empty($plugin)) {
            $this->error('Check Add enrol method : Unkown enrol method');
        }

        if (function_exists('debug_trace')) {
            debug_trace("Check enrolcourseid");
        }

        if ($context->enrolcourseid != 'current') {
            if (!$course = $DB->get_record('course', array('id' => $context->enrolcourseid))) {
                $this->error("Check Add enrol method : Enrol Course {$context->enrolcourseid} does not exist");
            }
        }

        // Check attributes map if exists in plugin.
        debug_trace("Up to check attributes in enrol plugin");
        if (method_exists($plugin, 'script_attributes')) {
            // Get plugin scriptdesc and check it.
            $attrmap = $plugin->script_attributes(false);
            $this->check_context_attributes($attrmap);
        }

        if (function_exists('debug_trace')) {
            debug_trace("Check Add Enrol Method : Enrol method Pre script check");
        }

        if (method_exists($plugin, 'script_check')) {
            // Invoke the specific plugin integrated contextual check.
            $plugin->script_check($context, $this);
        }

        if (isset($context->params->role)) {
            $role = $DB->get_record('role', array('shortname' => $context->params->role));
            if (empty($role)) {
                $this->error('Check Add Enrol Method : unkown role by shortname '.$context->params->role);
            }
        }
    }
}