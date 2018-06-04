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

/**
 * A tokenizer class processes a single token and 
 * decides what to do with the remainder of the line and/or the script entire buffer.
 *
 * OPT_ prefix stands for optional.
 *
 * SP : Spaces
 * ALPHANUM : Alphanumeric
 * TOKEN : Token (alphanumeric + '_') for internal element naming.
 * IDENTIFIER : Token + '-' and ':'. Allowing field:token type string
 * QUOTED_IDENTIFIER : Identifier accepting quoted string litterals as value. E.g. name:"SOME NAME"
 * QUOTED_EXT_IDENTIFIER : Quoted identifier accepting extended charset strings.
 */
abstract class tokenizer {

    const SP = '\\s+';
    const OPT_SP = '\\s*?';
    const NON_SPACE = '\\S+';
    const OPT_NON_SPACE = '\\S*?';
    const ALPHANUM = '([a-zA-Z0-9]+)';
    const OPT_ALPHANUM = '([a-zA-Z0-9]*)';
    const TOKEN = '([a-zA-Z0-9_]+)';
    const OPT_TOKEN = '([a-zA-Z0-9_]*)';
    const IDENTIFIER = '([a-zA-Z0-9\:_-]+)';
    const OPT_IDENTIFIER = '([a-zA-Z0-9\:_-]*)';
    const QUOTED_IDENTIFIER = '([a-zA-Z0-9\:_-]+|[\\\'"][a-zA-Z0-9 \:_-]+?["\\\'])';
    const OPT_QUOTED_IDENTIFIER = '([a-zA-Z0-9\:_-]*|[\\\'"]?[a-zA-Z0-9 \:_-]*?["\\\']?)';
    const QUOTED_EXT_LITTERAL = '([\\\'"]?[@a-zA-Z0-9àéèüïäëöûîôêâËÄÜÏÖçêâûîÂÛÎÔÊ.\\/ \:_-]+?["\\\']?)';
    const OPT_QUOTED_EXT_LITTERAL = '([\\\'"]?[@a-zA-Z0-9àéèüïäëöûîôêâËÄÜÏÖçêâûîÂÛÎÔÊ.\\/ \:_-]*?["\\\']?)';
    const QUOTED_EXT_IDENTIFIER = '([.@a-zA-Z0-9àéèüïäëöûîôêâËÄÜÏÖçêâûîÂÛÎÔÊ\\/\:_-]+|[@a-zA-Z0-9àéèüïäëöûîôêâËÄÜÏÖçêâûîÂÛÎÔÊ\:_-]+?[\\\'"]?[@a-zA-Z0-9àéèüïäëöûîôêâËÄÜÏÖçêâûîÂÛÎÔÊ.\\/ \:_-]+?["\\\']?)';
    const OPT_QUOTED_EXT_IDENTIFIER = '([.@a-zA-Z0-9àéèüïäëöûîôêâËÄÜÏÖçêâûîÂÛÎÔÊ\\/\:_-]*|[a-zA-Z0-9àéèüïäëöûîôêâËÄÜÏÖçêâûîÂÛÎÔÊ@\:_-]*?[\\\'"]?[@a-zA-Z0-9àéèüïäëöûîôêâËÄÜÏÖçêâûîÂÛÎÔÊ.\\/ \:_-]*?["\\\']?)';

    protected $remainder;

    protected $parser;

    protected $coderoot;

    public function __construct($remainder, &$parser) {
        global $CFG;

        $this->remainder = $remainder;
        $this->parser = $parser;
        $this->coderoot = $CFG->dirroot.'/local/moodlescript/classes/engine/';
    }

    public abstract function parse();

    protected function trace($msg) {
        $this->parser->trace($msg);
    }

    protected function error($msg) {
        $this->parser->error($msg);
    }

    protected function standard_sub_parse($matches, $class, $classfile) {

        if (!file_exists($this->coderoot.$classfile.'.class.php')) {
            $this->error('Missing parser class for command '.$class);
            $this->trace('   End parse -e');
            return [null, null];
        }
        include_once($this->coderoot.$classfile.'.class.php');

        $remainder = $matches[2];
        $tokenizer = new $class($remainder, $this->parser);
        $result = $tokenizer->parse();
        $this->trace('   End parse ++');
        return $result;
    }

    protected function parse_having($input, &$output) {
        if ($input) {
            $having = new \local_moodlescript\engine\parse_having('', $this->parser, $this->logger);
            if ($params = $having->parse()) {
                foreach ($params as $key => $value) {
                    $output->$key = $value;
                }
            }
        }
    }
}