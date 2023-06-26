<?php

namespace local_moodlescript\engine;

use \Exception;

class execution_exception extends Exception {

    public function __construct($message) {

        $message = 'EXE: '.$message;

        $trace = debug_backtrace();
        array_shift($trace);
        if ($tracepoint = array_shift($trace)) {
            $message .= "\nAt : {$tracepoint['file']} line {$tracepoint['line']} calling to {$tracepoint['function']}";
        } else {
            $message .= "\nAt : <unknown file> line <unknown line> calling to <unknown function> ";
        }

        $stacklength = 3; // hardcoded till pass to config.
        if ($stacklength == '*') {
            $i = 1;
            while ($tracepoint = array_shift($trace)) {
                $message .= "<br/>\n$i) : {$tracepoint['file']} line {$tracepoint['line']} calling to {$tracepoint['function']}";
                $i++;
            }
            parent::__construct($message);
            return;
        }

        if ($stacklength > 1) {
            if ($tracepoint = array_shift($trace)) {
                $message .= "<br/>\n1) : {$tracepoint['file']} line {$tracepoint['line']} calling to {$tracepoint['function']}";
            } else {
                $message .= "<br/>\n1) : <unknown file> line <unknown line> calling to <unknown function> ";
            }
        }
        if ($stacklength > 2) {
            if ($tracepoint = array_shift($trace)) {
                $message .= "<br/>\n2) : {$tracepoint['file']} line {$tracepoint['line']} calling to {$tracepoint['function']}";
            } else {
                $message .= "<br/>\n2) : <unknown file> line <unknown line> calling to <unknown function> ";
            }
        }
        if ($stacklength > 3) {
            if ($tracepoint = array_shift($trace)) {
                $message .= "<br/>\n2) : {$tracepoint['file']} line {$tracepoint['line']} calling to {$tracepoint['function']}";
            } else {
                $message .= "<br/>\n2) : <unknown file> line <unknown line> calling to <unknown function> ";
            }
        }

        parent::__construct($message);
    }

}