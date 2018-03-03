<?php


namespace local_moodlescript\engine;

use \core_text;
use \StdClass;

require_once($CFG->dirroot.'/local/moodlescript/classes/engine/tokenizer.class.php');

/**
 * Having keyword parses and scans forward the script buffer until the next empty line. It
 * will collect name: value declarations to forge a $params structure in the context
 *
 */
class parse_having extends tokenizer {

    protected $table;

    public function parse() {
        global $DB;

        $params = new StdClass;

        while (!empty($this->parser->script)) {
            $line = array_shift($this->parser->script);
            if ($line == '' ||
                preg_match('/^[\/#;]/', $line) ||
                    preg_match('/^\s+$/', $line)) {
                // Stop scanning on first empty line.
                return $params;
            }

            $parts = explode(':', $line);
            $key = trim(array_shift($parts));

            // If line starts with an expected token, then put it back in buffer and terminate.
            if (in_array(core_text::strtoupper($key), $this->parser->keywords)) {
                array_push($this->parser->script, $line);
                return $params;
            }

            $value = trim(implode(':', $parts));
            $params->$key = $value;
        }
        return $params;
    }
}