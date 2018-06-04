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

class parse_add_category extends tokenizer {

    /*
     * Add keyword needs find what to add in the remainder
     */
    public function parse() {
        $this->trace('   Start parse add category : '.$this->remainder);

        $pattern = '/^';
        $pattern .= tokenizer::QUOTED_EXT_IDENTIFIER.tokenizer::SP;
        $pattern .= '(IF NOT EXISTS)?'.tokenizer::OPT_SP;
        $pattern .= 'IN'.tokenizer::SP.tokenizer::IDENTIFIER.tokenizer::SP;
        $pattern .= '(HAVING)?'.tokenizer::OPT_SP;
        $pattern .= '$/';

        $pattern2 = '/^';
        $pattern2 .= tokenizer::QUOTED_EXT_IDENTIFIER.tokenizer::SP;
        $pattern2 .= '(IF NOT EXISTS)?'.tokenizer::OPT_SP;
        $pattern2 .= '(HAVING)?'.tokenizer::OPT_SP;
        $pattern2 .= '$/';

        if (preg_match($pattern, trim($this->remainder), $matches)) {

            $handler = new \local_moodlescript\engine\handle_add_category();
            $context = new StdClass;
            $context->name = trim($matches[1]);
            $context->name = preg_replace('/^\'"|\'"$/', '', $context->name); // Unquote.

            $context->options = new \StdClass;
            $context->options->ifnotexists = !empty($matches[2]);
            $parenttarget = @$matches[3];
            $having = @$matches[4];

            $identifier = new \local_moodlescript\engine\parse_identifier('course_categories', $this->parser);
            $context->parentcategoryid = $identifier->parse($parenttarget);

            if (!empty($having)) {
                $having = new \local_moodlescript\engine\parse_having('', $this->parser);
                $params = $having->parse();
                $context->params = $params;
            }

            $this->trace('   End parse ++');
            return array($handler, $context);
        } else if (preg_match($pattern2, trim($this->remainder), $matches)) {
            // Root category.

            $handler = new \local_moodlescript\engine\handle_add_category();
            $context = new StdClass;
            $context->name = trim($matches[1]);
            $context->name = preg_replace('/^\'"|\'"$/', '', $context->name); // Unquote.

            $context->options = new \StdClass;
            $context->options->ifnotexists = !empty($matches[2]);
            $having = @$matches[3];

            $context->parentcategoryid = 0;

            if (!empty($having)) {
                $having = new \local_moodlescript\engine\parse_having('', $this->parser);
                $params = $having->parse();
                $context->params = $params;
            }

            $this->trace('   End parse ++');
            return array($handler, $context);
        } else {
            $this->trace('...End parse --');
            return [null, null];
        }
    }

}