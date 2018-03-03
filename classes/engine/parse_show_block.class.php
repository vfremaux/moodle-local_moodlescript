<?php 


namespace local_moodlescript\engine;

use \StdClass;

class parse_show_block extends tokenizer {

    /*
     * Add keyword needs find what to add in the remainder
     */
    public function parse() {
        $this->trace('...Start parse '.$this->remainder);
        if (preg_match('/^([a-zA-Z0-9\:_-]+)\s+IN\s+([a-zA-Z]*)$/', trim($this->remainder), $matches)) {

            $handler = new handle_show_block();
            $context = new StdClass;

            $context->blockname = $matches[1];

            if ($matches[2] != 'current') {
                $identifier = new parse_identifier('course', $this->logger);
                $context->showcourseid = $identifier->parse($matches[2]);
            } else {
                $context->showcourseid = 'current';
            }

            $this->trace('...End parse ++');
            return array($handler, $context);
        } else {
            $this->trace('...End parse --');
            return [null, null];
        }
    }

}