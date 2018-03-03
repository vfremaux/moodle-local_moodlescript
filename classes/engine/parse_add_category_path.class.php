<?php


namespace local_moodlescript\engine;
require_once($CFG->dirroot.'/lib/coursecatlib.php');

use \StdClass;

class parse_add_category_path extends tokenizer {

    /*
     * Add keyword needs find what to add in the remainder
     */
    public function parse() {
        $this->trace('   Start parse : '.$this->remainder);
        $pattern = '/^(.*?)\s+IN\s+([a-zA-Z0-9\\:_-]+)(?:\s+)?(HAVING)?\s*$/';
        if (preg_match($pattern, trim($this->remainder), $matches)) {

            $handler = new \local_moodlescript\engine\handle_add_category_path();
            $context = new StdClass;
            $context->path = trim($matches[1]);

            if (empty($matches[2])) {
                $context->parentcategoryid = 0;
            } else {
                $parenttarget = $matches[2];
                $identifier = new \local_moodlescript\engine\parse_identifier('course_categories', $this->parser);
                $context->parentcategoryid = $identifier->parse($parenttarget);
            }

            if (!empty($matches[3])) {
                $having = new \local_moodlescript\engine\parse_having('', $this->parser);
                $params = $having->parse();
                $context->params = $params;
            }

            $this->trace('   End parse ++');
            return array($handler, $context);
        } else {
            $this->trace('...End parse --');
            return [null, null];
        }
    }

}