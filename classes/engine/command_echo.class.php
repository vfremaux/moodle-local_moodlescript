<?php

/**
 * The echo command just print some literal or variables in log.
 *
 */
namespace local_moodlescript\engine;

class command_echo extends tokenizer {

    /*
     * Add keyword needs find what to print in log
     */
    public function parse() {
        $this->trace('   Start parse');

        $handler = new handle_echo();
        $context = new \StdClass;
        $context->argument = $this->remainder;

        $this->trace('   End parse ++');
        return array($handler, $context);
    }

}