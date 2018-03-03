<?php


namespace local_moodlescript\engine;

defined('MOODLE_INTERNAL') || die();

abstract class handler {

    protected $errorlog;

    protected $context;

    protected $log;

    /**
     * Executes the handler action.
     * @param object $result provides the previous result object of the handler chain.
     * @param objectref &$context the context for this handler execution instance
     * @param objectref &$logger a stirng array where to log any output
     */
    abstract function execute($result, &$context, &$logger);

    /**
     * Checks the validity of input context. Will fill the internal errorlog for the caller.
     * This might be used in a "check chain" when updating a script.
     * @param objectref &$context the context for this handler execution instance
     */
    abstract function check(&$context, &$logger);

    protected function error($errormsg) {
        $this->errorlog[] = $errormsg;
    }

    protected function log($msg) {
        $this->log[] = $msg;
    }

    public function errorlog() {
        return implode("\n", $this->errorlog);
    }

    /**
     * Given an attribute mapping description, checks that there are no unsupported
     * attributes
     */
    public function check_context_attributes($attrmap) {

        if (empty($attrmap)) {
            return;
        }

        foreach ($attrmap as $key => $desc) {

            if (!is_array($desc)) {
                throw new moodle_exception('check_context_attributes expects a description, not a scalar mapping');
            }

            if ($desc['required']) {
                if (!array_key_exists($key, $this->context->params)) {
                    $this->error("Required attribute $key not provided in input");
                }
            }
        }

        if (!empty($this->context->params)) {
            foreach ($this->context->params as $key => $value) {
                if (!in_array($key, array_keys($attrmap))) {
                    $this->error("Attribute $key not supported in input");
                }
            }
        }
    }
}