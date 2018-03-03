<?php 


namespace local_moodlescript\engine;

use \StdClass;

class command_unenrol extends tokenizer {

    /*
     * Add keyword needs find what to add in the remainder
     */
    public function parse() {
        global $USER;

        $this->trace('   Start parse '.$this->remainder);
        if (preg_match('/^([a-zA-Z0-9:_-]+)\s+FROM\s+([a-z\:A-Z0-9_-]+)\s*(?:AS)?\s*([a-zA-Z_]+)?\s*$/', $this->remainder, $matches)) {

            $handler = new handle_unenrol();

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
                $context->unenrolcourseid = 'current';
            } else {
                $context->unenrolcourseid = $identifier->parse($target);
            }

            if ($role = @$matches[3]) {
                $context->role = $role;
            }

            $this->trace('   End parse ++');
            return [$handler, $context];
        } else if (preg_match('/^([a-zA-Z0-9:_-]+)\s+FROM\s+([a-z\:A-Z0-9_-]+)\s*(HAVING)\s*$/', $this->remainder, $matches)) {

            $handler = new handle_unenrol();

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
                $context->unenrolcourseid = 'current';
            } else {
                $context->unenrolcourseid = $identifier->parse($target);
            }

            $this->parse_having($matches[3], $context);

            $this->trace('   End parse ++');
            return [$handler, $context];
        } else {
            $this->trace('   End parse --');
            return [null, null];
        }
    }

}