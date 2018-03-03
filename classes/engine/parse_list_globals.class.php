<?php 


namespace local_moodlescript\engine;

use \StdClass;

class parse_list_globals extends tokenizer {

    /*
     *
     */
    public function parse() {
        $this->trace('...Start parse '.$this->remainder);
        $this->trace('...End parse ++');

        $handler = new handle_list_globals();
        $context = new StdCLass;
        return array($handler, $context);
    }

}