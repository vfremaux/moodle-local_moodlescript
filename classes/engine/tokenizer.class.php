<?php 


namespace local_moodlescript\engine;

/**
 * A tokenizer class processes a single token and 
 * decides what to do with the remainder of the line and/or the script entire buffer.
 */
abstract class tokenizer {

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