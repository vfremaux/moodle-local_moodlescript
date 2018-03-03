<?php

namespace local_moodlescript\engine;

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
        'CATEGORY PATH' => 'CATEGORY_PATH'
    );

    public function __construct($script) {
        global $CFG;

        $this->stack = new stack();
        $this->script = explode("\n", $script);
        $this->coderoot = $CFG->dirroot.'/local/moodlescript/classes/engine/';

        include_once($CFG->dirroot.'/local/moodlescript/classes/engine/handler_base.class.php');

        $this->trace("Loading handles");
        $globs = glob($CFG->dirroot.'/local/moodlescript/classes/engine/handle*');
        foreach ($globs as $glob) {
            $this->trace("...Loading handle ".$glob);
            include_once($glob);
        }

        $this->trace("Loading parsers ");
        $globs = glob($CFG->dirroot.'/local/moodlescript/classes/engine/parse*');
        foreach ($globs as $glob) {
            $this->trace("...Loading parser ".$glob);
            include_once($glob);
        }
    }

    public function parse($globalcontext = array()) {
        global $CFG;

        $this->context = $globalcontext;

        $this->trace('Start parsing...');
        if (empty($this->script)) {
            $this->trace('No script to process');
            return null;
        }

        while (!empty($this->script)) {
            $line = array_shift($this->script);
            if ($line == '' ||
                preg_match('/^[\/#;]/', $line) ||
                    preg_match('/^\s+$/', $line)) {
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

                // Process the remainder to replace all the global context variables.
                foreach ($globalcontext as $key => $value) {
                    $pattern = '/^\:'.$key.'|(?:\s)\:'.$key.'/';
                    $remainder = preg_replace($pattern, $value, $remainder);
                }

                $class = '\\local_moodlescript\\engine\\command_'.\core_text::strtolower(trim($keyword));
                $classfile = 'command_'.\core_text::strtolower(trim($keyword));

                if (!file_exists($this->coderoot.$classfile.'.class.php')) {
                    $this->errorlog[] = 'invalid command '.$class;
                    return null;
                }
                include_once($this->coderoot.$classfile.'.class.php');

                $this->trace('Parsed command '.$class);
                $tokenizer = new $class($remainder, $this);
                list ($handler, $context) = $tokenizer->parse();
                if (!empty($handler)) {
                    $this->stack->register($handler, $context);
                }
            }
        }
        $this->trace('End parsing...');
        return $this->stack;
    }

    public function trace($msg) {
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
}