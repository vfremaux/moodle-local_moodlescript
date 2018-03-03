<?php 


namespace local_moodlescript\engine;

use \StdClass;

class parse_remove_block extends tokenizer {

    /*
     * Add keyword needs find what to add in the remainder
     */
    public function parse() {
        $this->trace('...Start parse ');
        if (preg_match('/^([a-zA-Z0-9_-]+?)\s+FROM\s+([a-zA-Z0-9:_-]+)\s*(HAVING)?\s*$/', $this->remainder, $matches)) {

            $handler = new \local_moodlescript\engine\handle_remove_block();
            $context = new StdClass;
            $context->blockname = $matches[1];

            $target = $matches[2];
            $identifier = new \local_moodlescript\engine\parse_identifier('course', $this->logger);
            if ($target == 'current') {
                $context->blockcourseid = $target;
            } else {
                $context->blockcourseid = $identifier->parse($target);
            }

            $this->parse_having(@$matches[3], $context);

            $this->trace('...End parse ++');
            return array($handler, $context);
        } else {
            $this->trace('...End parse --');
            return [null, null];
        }
    }

}