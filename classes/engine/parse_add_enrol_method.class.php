<?php 


namespace local_moodlescript\engine;

use \StdClass;

class parse_add_enrol_method extends tokenizer {

    /*
     * Add keyword needs find what to add in the remainder
     */
    public function parse() {
        global $CFG;

        $this->trace('   Start parse : '.$this->remainder);
        if (preg_match('/^([a-zA-Z]+?)\s+TO\s+?([a-zA-Z0-9:]+)?\s*(HAVING)?.*$/', $this->remainder, $matches)) {

            $handler = new \local_moodlescript\engine\handle_add_enrol_method();
            $context = new StdClass;
            $context->method = $matches[1];

            $target = $matches[2];
            $having = @$matches[3];

            if (!empty($having)) {
                $having = new \local_moodlescript\engine\parse_having('', $this->parser);
                $params = $having->parse();
                $context->params = $params;
            }

            if (empty($target)) {
                $this->errorlog[] = 'Empty course target for enrol method '.$context->method;
                $this->trace('   End parse -e');
                return [null, null];
            }

            $identifier = new \local_moodlescript\engine\parse_identifier('course', $this->parser);

            if ($target == 'current') {
                $context->enrolcourseid = $target;
            } else {
                $context->enrolcourseid = $identifier->parse($target);
            }

            $this->trace('   End parse ++');
            return array($handler, $context);
        } else {
            $this->trace('   End parse --');
            return [null, null];
        }
    }

}