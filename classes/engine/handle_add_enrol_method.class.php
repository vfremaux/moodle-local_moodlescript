<?php


namespace local_moodlescript\engine;

defined('MOODLE_INTERNAL') || die;

class handle_add_enrol_method extends handler {

    public function execute($result, &$context, &$logger) {
        global $DB;

        // Pass incoming context to internals.
        $this->log = &$logger;
        $this->context = &$context;

        $plugin = enrol_get_plugin($context->method);
        if ($context->enrolcourseid == 'current') {
            $course = $DB->get_record('course', array('id' => $context->courseid));
        } else {
            $course = $DB->get_record('course', array('id' => $context->enrolcourseid));
        }

        // Shift some entries to what moodle expects internally.
        if (isset($context->params->role)) {
            $role = $DB->get_record('role', array('shortname' => $context->params->role));
            $context->params->role = $role->id;
        }

        if (method_exists($plugin, 'script_attributes')) {
            // Translate the input map to internal storage given by the plugin.
            $attrmap = $plugin->script_attributes(true);
            $input = array();
            foreach ($context->params as $key => $value) {
                $input[$attrmap[$key]] = $value;
            }
        } else {
            // Direct straight mapping. May not be very comfortable for scripters.
            $input = (array)$context->params;
        }

        $plugin->add_instance($course, $input);
        $this->log('Enrol instance of "'.$this->context->method.'" added to course '.$course->id);
    }

    public function check(&$context, &$logger) {
        global $DB;

        // Pass incoming context to internals.
        $this->log = &$logger;
        $this->context = &$context;

        if (empty($context->method)) {
            $this->error('empty method');
        }

        $plugin = enrol_get_plugin($context->method);
        if (empty($plugin)) {
            $this->error('unkown enrol method');
        }

        // Check attributes map if exists in plugin.
        if (method_exists($plugin, 'script_attributes')) {
            // Get plugin scriptdesc and check it.
            $attrmap = $plugin->script_attributes(false);
            $this->check_context_attributes($attrmap);
        }

        if (method_exists($plugin, 'script_check')) {
            // Invoke the specific plugin integrated contextual check.
            $plugin->script_check($context, $this);
        }

        if (isset($context->params->role)) {
            $role = $DB->get_record('role', array('shortname' => $context->params->role));
            if (empty($role)) {
                $this->error('unkown role by shortname '.$context->params->role);
            }
        }

        return (!empty($this->errorlog));
    }

}