<?php 


namespace local_moodlescript\engine;

use \StdClass;

class command_enrol extends tokenizer {

    /*
     * Add keyword needs find what to add in the remainder
     */
    public function parse() {
        global $USER;

        $this->trace('   Start parse '.$this->remainder);
        $pattern1 = '/^([a-zA-Z0-9\:_-]+)\s+IN\s+([a-zA-Z0-9\:_-]+)\s+(HAVING)?$/';
        $pattern2 = '/^([a-zA-Z0-9\:_-]+)\s+IN\s+([a-zA-Z0-9\:_-]+)\s+AS\s+([a-zA-Z0-9\:_-]+)(?:\s+)?(USING)?(?:\s+)?([a-zA-Z]+)?$/';
        if (preg_match($pattern1, $this->remainder, $matches)) {

            $handler = new handle_enrol();

            $context = new StdClass;

            $targetuser = $matches[1];
            $identifier = new parse_identifier('user', $this->logger);
            if ($targetuser == 'current') {
                $context->userid = $USER->id;
            } else {
                $context->userid = $identifier->parse($targetuser);
            }

            $target = $matches[2];
            $identifier = new parse_identifier('course', $this->logger);
            if ($targetuser == 'current') {
                $context->enrolcourseid = 'current';
            } else {
                $context->enrolcourseid = $identifier->parse($target);
            }

            $this->parse_having($matches[3], $context);

            if (!empty($context->params->role)) {
                $identifier = new parse_identifier('role', $this->logger);
                $context->role = $identifier->parse($context->params->role);
            }

            $this->trace('   End parse ++');
            return [$handler, $context];

        } else if (preg_match($pattern2, $this->remainder, $matches)) {

            $handler = new handle_enrol();

            $context = new StdClass;

            $targetuser = $matches[1];
            $identifier = new parse_identifier('user', $this->logger);
            if ($targetuser == 'current') {
                $context->userid = $USER->id;
            } else {
                $context->userid = $identifier->parse($targetuser);
            }

            $target = $matches[2];
            $identifier = new parse_identifier('course', $this->logger);
            if ($targetuser == 'current') {
                $context->enrolcourseid = 'current';
            } else {
                $context->enrolcourseid = $identifier->parse($target);
            }

            $identifier = new parse_identifier('role', $this->logger);
            $context->roleid = $identifier->parse($matches[3]);

            if (!empty($matches[4])) {
                $context->method = $matches[4];
            } else {
                $context->method = 'manual';
            }

            $this->trace('   End parse ++');
            return [$handler, $context];
        } else {
            $this->trace('   End parse --');
            return [null, null];
        }
    }

}