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

require_once($CFG->dirroot.'/lib/coursecatlib.php');

class handle_add_category_path extends handler {

    public function execute($result, &$context, &$logger) {
        global $DB;

        // Pass incoming context to internals.
        $this->log = &$logger;
        $this->context = &$context;

        $categories = explode('/', $context->path);

        $parentcategoryid = $context->parentcategoryid;

        while ($catname = array_shift($categories)) {

            $catdata = new \Stdclass;
            $catdata->parent = $parentcategoryid;
            $catdata->name = trim($catname);

            if (empty($categories)) {
                // If the last one (the one to create or update in the path.
                if (!empty($context->params)) {
                    foreach ($context->params as $key => $value) {
                        $catdata->$key = $value;
                    }
                }
                if (!empty($catdata->description)) {
                    $catdata->description = str_replace('\\n', "\n", $catdata->description);
                }
            }

            $updated = false;
            // idnumber is the only external unique identification.
            $params = array('parent' => $parentcategoryid, 'name' => $catdata->name);
            if ($oldcat = $DB->get_record('course_categories', $params)) {
                $catdata->id = $oldcat->id;
                $DB->update_record('course_categories', $catdata);
                $cat = $oldcat;
                $this->log("Category {$oldcat->id} updated ");
            } else {
                $cat = \coursecat::create($catdata);
                if ($context->parentcategoryid) {
                    $parentcat = $DB->get_field('course_categories', 'name', array('id' => $parentcategoryid));
                    $this->log('Category '.$catdata->name.' added to parent cat '.$parentcat);
                } else {
                    $this->log('Category '.$catdata->name.' added to root cat ');
                }
            }

            // For next turn.
            $parentcategoryid = $cat->id;
        }
    }

    public function check(&$context, &$stack) {
        global $DB;

        // Pass incoming context to internals.
        $this->stack = &$stack;
        $this->context = &$context;

        if (empty($context->path)) {
            $this->error('Empty paths not allowed');
        }

        if (empty($context->parentcategoryid) && $context->parentcategoryid !== 0) {
            $this->error('Missing parent category (can be 0)');
        }

        if ($context->parentcategoryid) {
            if (!$DB->record_exists('course_categories', array('id' => $context->parentcategoryid))) {
                $this->error('Parent category does not exist');
            }
        }

        if (empty($context->options->ifnotexists)) {
            if (!empty($context->params->idnumber) &&
                $DB->record_exists('course_categories', array('idnumber' => $context->params->idnumber))) {
                $this->error('Category IDNumber is already used');
            }
        }

        // Auto check attributes.
        $attrdesc = array(
            'idnumber' => array(
                'output' => 'idnumber',
                'required' => 0,
            ),
            'visible' => array(
                'output' => 'visible',
                'required' => 0,
                'default' => 1,
            ),
            'description' => array(
                'output' => 'description',
                'required' => 0,
            ),
        );

        $this->check_context_attributes($attrdesc);
    }

}