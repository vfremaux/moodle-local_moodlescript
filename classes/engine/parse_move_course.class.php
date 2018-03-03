<?php 


namespace local_moodlescript\engine;

use \StdClass;

class parse_move_course extends tokenizer {

    /*
     * Add keyword needs find what to add in the remainder
     */
    public function parse() {
        $this->trace('...Start parse '.$this->remainder);
        if (preg_match('/^([a-zA-Z0-9\:_-]+)\s+TO\s+CATEGORY\s+([a-zA-Z0-9\:_-]*)$/', trim($this->remainder), $matches)) {

            $handler = new handle_move_course();
            $context = new StdClass;

            if ($matches[1] != 'current') {
                $identifier = new parse_identifier('course', $this->logger);
                $context->movecourseid = $identifier->parse($matches[1]);
            } else {
                $context->movecourseid = 'current';
            }

            $identifier = new parse_identifier('course_categories', $this->logger);
            $context->coursecatid = $identifier->parse($matches[2]);

            $this->trace('...End parse ++');
            return array($handler, $context);
        } else {
            $this->trace('...End parse --');
            return [null, null];
        }
    }

}