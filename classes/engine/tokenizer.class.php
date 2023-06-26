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
 * EVAL_OPERATOR : In order, equals, differs, less (or less or equal) than, more (or more or equal) than, matches, matches not.
 * OPT_ prefix stands for "optional token" that may NOT be in syntax.
 * QUOTED_ part stands for "single or double quote value"
 * ULOGICAL : stands for "Unary Logical" (defaults to binary)
 * ...
 */
abstract class tokenizer {

    const SP = '\\s+';
    const OPT_SP = '\\s*?';
    const NON_SPACE = '\\S+';
    const OPT_NON_SPACE = '\\S*?';
    const ALPHANUM = '([a-zA-Z0-9]+)';
    const OPT_ALPHANUM = '([a-zA-Z0-9]*)';
    const INTNUMBER = '([0-9]+)';
    const OPT_INTNUMBER = '([0-9]*)';
    const NUMBER = '([0-9.]+)';
    const OPT_NUMBER = '([0-9.]*)';
    const TOKEN = '([a-zA-Z0-9_]+)';
    const OPT_TOKEN = '([a-zA-Z0-9_]*)';
    const IDENTIFIER = '([a-zA-Z0-9:_-]+)';
    const OPT_IDENTIFIER = '([a-zA-Z0-9:_-]*)';
    const QUOTED_IDENTIFIER = '([a-zA-Z0-9:_-]+|[\\\'"][a-zA-Z0-9 :_-]+?["\\\'])';
    const OPT_QUOTED_IDENTIFIER = '([a-zA-Z0-9\:_-]*|[\\\'"]?[a-zA-Z0-9 :_-]*?["\\\']?)';
    const EVAL_OPERATOR = '(=|\\!=|\\<=?|\\>=?|\\~=|\\!~=|hasrolein|isenrolledin|hasloggedin|isloggedin|hasgradesin|hasstarted|hascompleted|isincohort|isincategory|isempty|isinsubs|isingroup)';
    const LOGICAL_OPERATOR = '(AND|OR|XOR)';
    const OPT_LOGICAL_OPERATOR = '((?:AND|OR|XOR)*)';
    const ULOGICAL_OPERATOR = '(NOT)';
    const OPT_ULOGICAL_OPERATOR = '((?:NOT)*)';
    const QUOTED_EXT_LITTERAL = '("[@a-zA-Z0-9àéèüïäëöûîôêâËÄÜÏÖçêâûîÂÛÎÔÊ.,\'\\/ :_-]+?")';
    const OPT_QUOTED_EXT_LITTERAL = '("?[@a-zA-Z0-9àéèüïäëöûîôêâËÄÜÏÖçêâûîÂÛÎÔÊ.,\'\\/ \:,_-]*?"?)';
    const QUOTED_EXT_IDENTIFIER = '((?:[.,@a-zA-Z0-9àéèüïäëöûîôêâËÄÜÏÖçêâûîÂÛÎÔÊ\/\:_-]+?|[\'"]?[@a-zA-Z0-9àéèüïäëöûîôêâËÄÜÏÖçêâûîÂÛÎÔÊ.,\/ \:_-]+?["\'])+)';
    const OPT_QUOTED_EXT_IDENTIFIER = '((?:[.,@a-zA-Z0-9àéèüïäëöûîôêâËÄÜÏÖçêâûîÂÛÎÔÊ\/\:_-]+?|[\'"]?[@a-zA-Z0-9àéèüïäëöûîôêâËÄÜÏÖçêâûîÂÛÎÔÊ.,\/ \:_-]+?["\'])*)';
    const CTXVAR = '\\{?(?!<runtime)\\:([a-zA-Z0-9_]+)\\}?';
    const CTXMAPVAR = '\\{?(?<!runtime)\\:([a-zA-Z0-9_]+)\\[([:]?[a-zA-Z0-9_]+)\\]\\}?';
    const RUNTIMECTXVAR = '\\{?runtime\\:?([a-zA-Z0-9_]+)\\}?';
    const RUNTIMECTXMAPVAR = '\\{?runtime\\:([a-zA-Z0-9_]+)\\[([:]?[a-zA-Z0-9_]+)\\]\\}?';

    protected $remainder;

    protected $parser;

    protected $coderoot;

    public function __construct($remainder, parser &$parser) {
        global $CFG;

        $this->remainder = $remainder;
        $this->parser = $parser;
        $this->coderoot = $CFG->dirroot.'/local/moodlescript/classes/engine/';
    }

    /**
     * An abstract function wich is specialized in each specific parser.
     */
    public abstract function parse();

    protected function trace($msg) {
        $this->parser->trace($msg);
    }

    protected function error($msg) {
        $this->parser->error($msg);
    }

    protected function standard_sub_parse($matches, $class, $classfile) {

        $subpath = $this->coderoot.$classfile.'.class.php';
        if (!file_exists($subpath)) {
            $this->error('Missing parser class for command '.$class);
            $this->trace('   End parse -e');
            return [null, null];
        }
        include_once($subpath);

        $remainder = $matches[2];
        $tokenizer = new $class($remainder, $this->parser);
        $result = $tokenizer->parse();
        $this->trace('   End parse ++');
        return $result;
    }

    /**
     * Parses an "HAVING" extension of the command that results in several
     * extra lines with additional attributes.
     * @param string $input Inputt buffer to parse
     * @param objectref &$output an output object container to receive parsed attributes.
     * @param bool $asarray
     */
    protected function parse_having($input, &$output, $asarray = false) {
        if ($input) {
            $having = new parse_having('', $this->parser);
            if ($params = $having->parse()) {
                foreach ($params as $key => &$value) {
                    $params->$key = $this->resolve_variables($value);
                }
                if ($asarray) {
                    $output->params = (array) $params;
                } else {
                    $output->params = $params;
                }
            }
            return;
        }

        if ($asarray) {
            $output->params = [];
        }
    }

    /**
     * Removes eventual start and end quote from a string.
     * @param string $str
     */
    protected function unquote($str) {
        if (empty($str)) {
            return '';
        }

        $str = preg_replace('/^[\"\']/', '', $str);
        $str = preg_replace('/[\"\']$/', '', $str);
        return $str;
    }

    /**
     * Searches and replace variable expressions in a string, taking values from context.
     * Searches for parse time resolvable variables.
     *
     * @TODO Prospective : at first, we apply missing output rules. Next we might convert missing variables
     * to "runtime" variables and let the run stage decide.
     * 
     * @param string $str The input string.
     * @param object $context The parser's context to get vars from.
     */
    protected function resolve_variables($str) {

        // echo "Var resolver input : $str <br/>";

        $config = get_config('local_moodlescript');

        $context = $this->parser->get_context();

        // First search for maps.
        if (preg_match_all('/'.tokenizer::CTXMAPVAR.'/', $str, $matches)) {
            for ($i = 0; $i < count($matches[0]); $i++) {
                $full = $matches[0][$i];
                $mapname = $matches[1][$i];
                $keyname = $matches[2][$i];

                $replaced = false;
                if (isset($context->$mapname)) {
                    if (array_key_exists($keyname, $context->$mapname)) {
                        $str = str_replace($full, $context->$mapname[$keyname], $str);
                        $replaced = true;
                    }
                }

                if (!$replaced) {
                    switch ($config->missingvariableoutput) {
                        case 'blank' : 
                            $str = str_replace($full, '', $str);
                            break;
                        case 'signalled' : 
                            $str = str_replace($full, '{missing[]}', $str);
                            break;
                        case 'ignored' : 
                            break;
                    }
                }
            }
        }

        // Next search for simple vars.
        if (preg_match_all('/'.tokenizer::CTXVAR.'/', $str, $matches)) {
            for ($i = 0; $i < count($matches[0]); $i++) {
                $full = $matches[0][$i];
                $varname = $matches[1][$i];

                $replaced = false;
                if (isset($context->$varname)) {
                    $str = str_replace($full, $context->$varname, $str);
                    $replaced = true;
                }

                if (!$replaced) {
                    switch ($config->missingvariableoutput) {
                        case 'blank' : 
                            $str = str_replace($full, '', $str);
                            break;
                        case 'signalled' : 
                            $str = str_replace($full, '{missing}', $str);
                            break;
                        case 'ignored' : 
                            break;
                    }
                }
            }
        }
        return $str;
    }

    public function print_trace() {
        $this->parser->print_trace();
    }

    public function print_errors() {
        $this->parser->print_errors();
    }
}