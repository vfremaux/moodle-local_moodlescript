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

class handle_add_course extends handler {

    public function execute($result, &$context, &$stack) {
        global $SESSION;

        $acceptedattrs = array('idnumber', 'visible', 'format', 'timestart');

        // TODO : convert input time format to unix timestamp.

        $this->stack = $stack;

        $courserec = new StdClass;
        $courserec->shortname = $context->shortname;
        $courserec->fullname = $context->fullname;

        if ($context->addcoursecatid == 'current') {
            $courserec->category = $context->coursecatid;
        } else {
            $courserec->category = $context->addcoursecatid;
        }

        // Aggregate params.
        foreach ($context->params as $key => $value) {
            if (in_array($key, $acceptedattrs)) {
                $courserec->$key = $value;
            }
        }

        $SESSION->nocoursetemplateautoenrol = true;
        $newcourse = create_course($data);
        $SESSION->nocoursetemplateautoenrol = false;

        $result[] = $newcourse->id;
        return $result;
    }

    public function check(&$context, &$stack) {
        global $DB;

        $this->stack = $stack;

        if (empty($context->fullname)) {
            $this->error('empty fullname');
        }

        if (empty($context->shortname)) {
            $this->error('empty shortname');
        }

        if ($oldcourse = $DB->get_record('course', array('shortname' => $context->shortname))) {
            $this->error('shortname already used');
        }

        if ($context->addcoursecatid == 'current') {
            $context->addcoursecatid = $context->coursecatid;
        }

        if (!$coursecat = $DB->get_record('course_categories', array('id' => $context->addcoursecatid))) {
            $this->error('Missing target course category for course creation');
        }

    }

}