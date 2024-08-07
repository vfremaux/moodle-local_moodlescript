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

use \StdClass;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/local/moodlescript/classes/engine/parse_identifier.class.php');
require_once($CFG->dirroot.'/local/moodlescript/classes/engine/parse_having.class.php');
require_once($CFG->dirroot.'/local/moodlescript/classes/engine/stack.class.php');

class parser {

    /**
     * A code root path to get handlers from.
     */
    protected $coderoot;

    /**
     * The executable stack resulting of a script parsing.
     */
    protected $stack;

    /**
     * The script to parse (string)
     */
    public  $script;

    /**
     * Has script being parsed ? 
     */
    protected  $parsed;

    /**
     * A string array storing parsing errors.
     */
    protected $errorlog;

    /**
     * A string array storing parsing trace.
     */
    protected $trace;

    /**
     * An externally provided variable stub for making 
     * replacement of non final symbols.
     */
    protected $context;

    /**
     * the authorized verbs.
     */
    public $keywords = array('ADD,REMOVE,HIDE,SHOW,BACKUP,LIST');

    /**
     * Translates replaces some inline instruction sequences to cope internal implementation
     * formalism.
     */
    public $translates = array(
        'ENROL METHOD' => 'ENROL_METHOD',
        'CATEGORY PATH' => 'CATEGORY_PATH',
        'ROLE ASSIGN' => 'ROLE_ASSIGN',
        'ROLE ASSIGNMENT' => 'ROLE_ASSIGNMENT',
        'ROLE ASSIGNMENTS' => 'ROLE_ASSIGNMENTS',
        'PROFILE VALUE' => 'PROFILE_VALUE',
    );

    /**
     * Builds a parser object with a script inside, ready to parse.
     * @param string $script
     */
    public function __construct($script) {
        global $CFG;

        $this->stack = new stack();
        $this->script = explode("\n", $script);
        $this->coderoot = $CFG->dirroot.'/local/moodlescript/classes/engine/';
        $this->parsed = false;

        include_once($CFG->dirroot.'/local/moodlescript/classes/engine/handler_base.class.php');

        $this->trace("Loading handles");
        $globs = glob($CFG->dirroot.'/local/moodlescript/classes/engine/handle*');
        foreach ($globs as $glob) {
            if ($CFG->debug == DEBUG_DEVELOPER && !defined('PHPUNIT_TEST')) {
                $this->trace("...Loading handle ".$glob);
            }
            include_once($glob);
        }

        $this->trace("Loading parsers ");
        $globs = glob($CFG->dirroot.'/local/moodlescript/classes/engine/parse*');
        foreach ($globs as $glob) {
            if ($CFG->debug == DEBUG_DEVELOPER && !defined('PHPUNIT_TEST')) {
                $this->trace("...Loading parser ".$glob);
            }
            include_once($glob);
        }
    }

    /**
     * Public accessor.
     */
    public function get_context() {
        return $this->context;
    }

    /**
     * Main parsing scenario. Context starts as empty, or preloaded from an external provided globalcontext.
     * @param object $globalcontext A data stub given at start of the parsing 
     */
    public function parse(StdClass $globalcontext) {
        global $CFG;

        if (!empty($this->parsed)) {
            // This parser already has been parsed and has a stack ready to execute.
            // Do not attempt to parse id gain as script has been consumed.
            // If you need to reparse the same script with another input context, then consider building a new parser
            // instance.
            return $this->stack;
        }

        if (is_null($globalcontext)) {
            $this->context = new StdClass;
        } else {
            $this->context = $globalcontext;
        }

        $this->trace('Parser: Start parsing...');
        if (empty($this->script)) {
            $this->trace('Parser: No script lines to process');
            $this->debug($this->print_trace());
            return null;
        }

        while (!empty($this->script)) {
            $line = array_shift($this->script);

            if ($line == '' ||
                preg_match('/^[\/#;]/', $line) ||
                    preg_match('/^\s+$/', $line)) {
                // Remove empty or comments lines.
                continue;
            }

            // Make translates.
            foreach ($this->translates as $from => $to) {
                $line = str_replace($from, $to, $line);
            }

            // Get first token and build a tokenprocessor. this token processor should return a configured handler.
            // the tokenizers will instanciante other tokenizers in cascade to consume the rest of the syntax.
            // Each further tokenizer will help choosing the correct handler object or add data to its context.
            if (preg_match('/^(\w+?)\s+(.*)$/', $line, $matches)) {

                $keyword = $matches[1];
                $remainder = $matches[2];

                $remainder = $this->global_replace($remainder);
                if (is_null($remainder)) {
                    $this->trace("Parser Exception: Empty arguments command $keyword found.");
                    $this->debug($this->print_trace());
                    return null;
                }

                $this->debug('Parser Class: parsing script line (replaced) '.$keyword.' '.$remainder);

                $class = '\\local_moodlescript\\engine\\command_'.\core_text::strtolower(trim($keyword));
                $classfile = 'command_'.\core_text::strtolower(trim($keyword));

                if (!file_exists($this->coderoot.$classfile.'.class.php')) {
                    $this->errorlog[] = 'Parser Exception: invalid command '.$class;
                    $this->debug('Parser Exception: invalid command '.$class);
                    return null;
                }
                include_once($this->coderoot.$classfile.'.class.php');

                $this->trace('Parsed command '.$class);
                $tokenizer = new $class($remainder, $this);
                list ($handler, $context) = $tokenizer->parse();
                $this->trace("\n");
                if (!empty($handler)) {
                    $this->stack->register($handler, $context);
                }
            }
        }
        $this->trace('Parser: End parsing...');
        $this->debug("Parse trace:\n".$this->print_trace());
        $this->parsed = true;
        return $this->stack;
    }

    public function trace($msg) {
        global $CFG;

        if (!((defined('PHPUNIT_TEST') && PHPUNIT_TEST == true) || (defined('NO_DEBUG_DISPLAY') && NO_DEBUG_DISPLAY == true))) {
            if ($CFG->debug >= DEBUG_DEVELOPER) {
                echo $msg."\n";
            }
            return;
        }
        $this->trace[] = $msg;
    }

    public function error($msg) {
        $this->errorlog[] = $msg;
    }

    public function has_errors() {
        return !empty($this->errorlog);
    }

    public function print_errors() {
        if (empty($this->errorlog)) {
            return "No errors.\n";
        }
        return implode("\n", $this->errorlog);
    }

    public function debug($msg) {
        global $CFG;

        if ($CFG->debug == DEBUG_DEVELOPER) {
            if (function_exists('debug_trace')) {
                debug_trace($msg);
            }
        }
    }

    public function print_stack() {
        if (!is_null($this->stack)) {
            return $this->stack->print_stack();
        }
    }

    public function print_trace() {
        if (!empty($this->trace)) {
            return implode("\n", $this->trace);
        }
    }

    public function global_replace($input) {
        // Process the input to replace all the global context variables.
        foreach ($this->context as $key => $value) {
            if (empty($key)) {
                continue;
            }
            if (!is_string($key)) {
                $this->errorlog[] = 'Invalid non scalar key in global context';
                return null;
            }
            // Be carefull that till here, $value may be an array !
            if ($key == 'config') {
                /*
                 * special case if an environmental component has dropped his global config in the
                 * global context.
                 */
                foreach ($value as $configkey => $configvalue) {
                    $configvalue = str_replace(':', '_', $configvalue); // Filter syntaxic harmful chars.
                    $pattern = '/^\:'.$configkey.'|(?<=[^a-zA-Z0-9])\:'.$configkey.'/';
                    $input = preg_replace($pattern, $configvalue, $input);
                }
                continue;
            }

            $value = str_replace(':', '_', $value); // Filter syntaxic harmful chars.
            if (!is_string($value)) {
                $this->errorlog[] = 'Invalid non scalar replacement value in global context for key '.$key;
                return null;
            }
            $pattern = '/^\:'.$key.'|(?<=[^a-zA-Z0-9])\:'.$key.'/';
            $IN = $input;
            $input = preg_replace($pattern, $value, $input);
        }

        return $input;
    }
}