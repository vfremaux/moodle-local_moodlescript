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

use \moodle_exception;

class handle_remove_module extends handler {

    public function execute(&$results, &$stack) {
        global $DB;

        $this->stack = &$stack;

        if ($context->removemoduleid == 'current') {
            if (!empty($context->moduleid)) {
                $context->removemoduleid = $context->moduleid;
            } else {
                if (empty($context->ifexists)) {
                    throw new moodle_exception("Remove module runtime : No current module id in stack");
                }
            }
        } else {
            $parentcontext = \context_course::instance($context->blockcourseid);
        }

        //TODO : Rewrite to remove course module

        return true;
    }

    /**
     * Remind that Check MUST NOT alter the context. Just execute any pre-execution tests that might 
     * be necessary.
     * @param $array &$stack the script stack.
     */
    public function check(&$stack) {
        global $DB;

        $this->stack = &$stack;
        $context = $this->stack->get_current_context();

        if (!empty($context->modulename)) {
            $module = $DB->get_record('modules', array('name' => $context->modulename));
            if (empty($module)) {
                $error = "Check remove module: module {$context->modulename} is not installed";
            }
            if (!$module->visible) {
                $error = "Check remove module : module {$context->modulename} not enabled";
            }

            if (empty($context->instanceid)) {
                $error = "Check remove module : module {$context->modulename} removed by instance but empty id";
            }

            if (!$DB->record_exists($context->modulename, ['id' => $context->instanceid])) {
                $error = "Check remove module : module {$context->modulename} removed by instance but not exists";
            }

            if (!empty($context->ifexists)) {
                $this->warn($error);
            } else {
                $this->error($error);
            }
        } else {

            if (empty($context->removemoduleid)) {
                $this->error('Check remove module : Empty module id');
            }

            if (!$this->is_runtime($context->removemoduleid)) {
                $removemoduleid = 0;
                if ($context->removemoduleid != 'current') {
                    $removemoduleid = $context->removemoduleid;
                } else {
                    if (isset($context->moduleid)) {
                        $removemoduleid = $context->moduleid;
                    }
                }

                if (!$DB->get_record('course_modules', array('id' => $removemoduleid))) {
                    if (!empty($context->ifexists)) {
                        $this->warn('Check remove module : Target module '.$context->removemoduleid.' does not exist');
                    } else {
                        $this->error('Check remove module : Target module '.$context->removemoduleid.' does not exist');
                    }
                }
            }
        }
    }
}