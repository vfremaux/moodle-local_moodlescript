<?php 


namespace local_moodlescript\engine;


class command_list extends tokenizer {

    /*
     * Add keyword needs find what to add in the remainder
     */
    public function parse() {
        $this->trace('   Start parse list '.$this->remainder);
        if (preg_match('/^(\w+)(\s+)?(.*)$/', trim($this->remainder), $matches)) {
            $class = '\\local_moodlescript\\engine\\parse_list_'.\core_text::strtolower(trim($matches[1]));
            $classfile = 'parse_list_'.\core_text::strtolower(trim($matches[1]));

            if (!file_exists($this->coderoot.$classfile.'.class.php')) {
                $this->error('Missing parser class for command '.$class);
                $this->trace('   End parse -e');
                return [null, null];
            }
            include_once($this->coderoot.$classfile.'.class.php');

            $remainder = $matches[2];
            $tokenizer = new $class($remainder, $this->parser);
            $result = $tokenizer->parse();
            $this->trace('   End parse ++');
            return $result;
        } else {
            $this->trace('   End parse --');
            return [null, null];
        }
    }

}