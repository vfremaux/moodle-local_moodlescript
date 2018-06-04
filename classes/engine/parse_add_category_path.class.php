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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/coursecatlib.php');

use \StdClass;

class parse_add_category_path extends tokenizer {

    /*
     * Add keyword needs find what to add in the remainder
     */
    public function parse() {
        $this->trace('   Start parse : '.$this->remainder);

        $pattern = '/^';
        $pattern .= tolenizer::QUOTED_EXT_IDENTIFIER.tokenizer::SP;
        $pattern .= 'IN'.tokenizer::SP.tokenizer::IDENTIFIER.tokenizer::OPT_SP;
        $pattern .= '(IF NOT EXISTS)?'.tokenizer::OPT_SP;
        $pattern .= '(HAVING)?'.tokenizer::OPT_SP;
        $pattern .= '$/';

        if (preg_match($pattern, trim($this->remainder), $matches)) {

            $handler = new \local_moodlescript\engine\handle_add_category_path();
            $context = new StdClass;
            $haserrors = false;
            $context->path = trim($matches[1]);
            $context->path = preg_replace('/^[\'"]|[\'"]$/', '', $context->path); // Remove eventual quoting.

            if (empty($context->path)) {
                $haserrors = true;
                $this->error('Add category path: Empty path');
            }

            if (empty($matches[2])) {
                $context->parentcategoryid = 0;
            } else {
                $parenttarget = $matches[2];
                $identifier = new \local_moodlescript\engine\parse_identifier('course_categories', $this->parser);
                $context->parentcategoryid = $identifier->parse($parenttarget);
            }

            if (!empty($matches[3])) {
                $context->options = new \StdClass;
                $context->options->ifnotexists = true;
            }

            if (!empty($matches[4])) {
                $having = new \local_moodlescript\engine\parse_having('', $this->parser);
                $params = $having->parse();
                $context->params = $params;
            }

            if ($haserrors) {
                $this->trace('...End parse +e');
                return [null, null];
            }

            $this->trace('   End parse ++');
            return array($handler, $context);
        } else {
            $this->trace('...End parse --');
            return [null, null];
        }
    }

}