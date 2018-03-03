<?php


namespace local_moodlescript\engine;

use \stdclass;

/**
 * implements a posthandler stack to be executed after a deployment.
 *
 * the stack can register some handlers and execute the handler sequence
 */
class stack {

    protected $stack;

    protected $contexts;

    protected $logger;

    protected $haserrors;

    public function __construct() {
        $this->stack = array();
    }

    /**
     * Register the handlers to be processed with an execution context.
     */
    public function register(handler $handler, $context = null) {

        if (is_null($handler)) {
            return;
        }

        $this->stack[] = $handler;
        $this->contexts[] = $context;
        $this->logger = [];
    }

    /**
     * Processes all the stack in order propagating a result object;
     */
    public function execute($globalcontext = null) {

        $this->logger = array();

        $result = null;
        if (!empty($this->stack)) {
            foreach ($this->stack as $handler) {
                $context = array_shift($this->contexts);
                if (!empty($globalcontext)) {
                    // Add/override with global context.
                    foreach ($globalcontext as $key => $value) {
                        $context->$key = $value;
                    }
                }
                $result = $handler->execute($result, $context, $this->logger);
            }
        }
        return $result;
    }

    /**
     * Processes all the stack in order propagating a result object;
     */
    public function check($globalcontext = null) {

        $this->logger = array();

        if (!empty($this->stack)) {
            $i = 0;

            $this->haserrors = false;
            foreach ($this->stack as $handler) {
                $context = $this->contexts[$i];
                if (!empty($globalcontext)) {
                    // Add/override with global context.
                    foreach ($globalcontext as $key => $value) {
                        $context->$key = $value;
                    }
                }
                // Collects all possible check results in errorlog.
                $this->haserrors |= $handler->check($context, $this->logger);
                $i++;
            }
        }
        return $this->haserrors;
    }

    /**
     * Get the result of the log.
     */
    public function get_log() {
        return $this->logger;
    }

    /**
     * Get the error status of the stack.
     */
    public function has_errors() {
        return $this->haserrors;
    }

    /**
     * Get the result of the log.
     */
    public function print_log() {
        return implode("\n", $this->logger);
    }

    public function print_stack() {

        $str = '';
        if (!empty($this->stack)) {
            $i = 0;
            foreach ($this->stack as $stacktask) {
                $str .= get_class($stacktask)." (";
                $context = $this->contexts[$i];
                foreach ($context as $key => $value) {
                    if ($key != 'params' && $key != 'options') {
                        $str .= "$key: $value, ";
                    } else if ($key == 'params') {
                        $str .= 'params[ ';
                        foreach ($value as $paramkey => $paramvalue) {
                            $str .= "$paramkey: $paramvalue, ";
                        }
                        $str .= '], ';
                    } else {
                        // Options.
                        $str .= 'OPTS[ ';
                        foreach ($value as $paramkey => $paramvalue) {
                            $str .= "$paramkey: $paramvalue, ";
                        }
                        $str .= '], ';
                    }
                }
                $str .= ")\n";
                $i ++;
            }
        } else {
            return 'Empty stack.';
        }

        return $str;
    }
}