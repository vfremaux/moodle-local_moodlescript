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

class parse_group_user extends tokenizer {

    public static $samples;

    public function __construct($remainder, &$parser) {
        parent::__construct($remainder, $parser);
        self::$samples = "GROUP USER username:<username> IN idnumber:<groupidnum>\n\n";
        self::$samples = "GROUP USER username:<username> IN name:\"<groupname>\" IN COURSE shortname:<courseshortname>\n";
    }

    /*
     * Add keyword needs find what to add in the remainder
     */
    public function parse() {
        $this->trace('...Start parse group user '.$this->remainder);

        $pattern = '/^';
        $pattern .= tokenizer::QUOTED_EXT_IDENTIFIER.tokenizer::SP;
        $pattern .= 'IN'.tokenizer::SP.tokenizer::QUOTED_EXT_IDENTIFIER.tokenizer::OPT_SP;
        $pattern .= '(IN COURSE)?'.tokenizer::OPT_SP.tokenizer::QUOTED_EXT_IDENTIFIER.tokenizer::OPT_SP;
        $pattern .= '$/';

        if (preg_match($pattern, $this->remainder, $matches)) {

            $handler = new \local_moodlescript\engine\handle_group_user();

            $context = new StdClass;

            $user = $matches[1];
            $identifier = new \local_moodlescript\engine\parse_identifier('user', $this->logger);
            if ($user == 'current') {
                $context->groupuserid = $user;
            } else {
                $context->groupuserid = $identifier->parse($user, 'username');
            }

            $context->groupcourseid = 0;
            $target = $matches[2];
            $identifier = new \local_moodlescript\engine\parse_identifier('groups', $this->logger);
            if ($target == 'current') {
                $context->groupgroupid = $target;
            } else {
                // First resolve course if we have it.
                $allgood = false;
                if (!empty($matches[3])) {
                    $ctarget = $matches[3];
                    $cidentifier = new \local_moodlescript\engine\parse_identifier('course', $this->logger);
                    $context->groupcourseid = $cidentifier->parse($ctarget, 'shortname');
                    $allgood = true;
                } else {
                    // Trap error if we are asking for name and have no course to qualify.
                    if (preg_match('/name:/', $target)) {
                        $this->error('Resolving group by name needs course to be given');
                    } else {
                        $allgood = true;
                    }
                }

                if ($allgood) {
                    $context->groupgroupid = $identifier->parse($target, 'idnumber', 'parse', $context->groupcourseid);
                }
            }

            $this->trace('...End parse ++');
            return array($handler, $context);
        } else {
            $this->trace('...End parse --');
            return [null, null];
        }
    }

}