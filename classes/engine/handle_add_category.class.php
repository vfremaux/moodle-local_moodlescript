<?php


namespace local_moodlescript\engine;

require_once($CFG->dirroot.'/lib/coursecatlib.php');

defined('MOODLE_INTERNAL') || die;

class handle_add_category extends handler {

    public function execute($result, &$context, &$logger) {
        global $DB;

        // Pass incoming context to internals.
        $this->log = &$logger;
        $this->context = &$context;

        $catdata = new \Stdclass;
        $catdata->parent = $context->parentcategoryid;
        $catdata->name = $context->name;
        if (!empty($context->params)) {
            foreach ($context->params as $key => $value) {
                $catdata->$key = $value;
            }
        }
        if (!empty($catdata->description)) {
            $catdata->description = str_replace('\\n', "\n", $catdata->description);
        }

        $updated = false;
        if (!empty($context->options->ifnotexists) && !empty($context->params->idnumber)) {
            // idnumber is the only external unique identification.
            if ($oldcat = $DB->get_record('course_categories', array('idnumber' => $context->params->idnumber))) {
                $catdata->id = $oldcat->id;
                $DB->update_record('course_categories', $catdata);
                $this->log("Category {$oldcat->id} updated ");
                $updated = true;
            }
        }

        if (!$updated) {
            \coursecat::create($catdata);
            if ($context->parentcategoryid) {
                $parentcat = $DB->get_field('course_categories', 'name', array('id' => $context->parentcategoryid));
                $this->log('Category added to parent cat '.$parentcat);
            } else {
                $this->log('Category added to root cat ');
            }
        }
    }

    public function check(&$context, &$logger) {
        global $DB;

        // Pass incoming context to internals.
        $this->log = &$logger;
        $this->errorlog = &$logger;
        $this->context = &$context;

        if (empty($context->name)) {
            $this->error('Empty name not allowed');
        }

        if (empty($context->parentcategoryid) && $context->parentcategoryid !== 0) {
            $this->error('Missing parent category (can be 0)');
        }

        if ($context->parentcategoryid) {
            if (!$DB->record_exists('course_categories', array('id' => $context->parentcategoryid))) {
                $this->error('Parent category does not exist');
            }
        }

        if (empty($context->options->ifnotexists)) {
            if (!empty($context->params->idnumber) &&
                $DB->record_exists('course_categories', array('idnumber' => $context->params->idnumber))) {
                $this->error('Category IDNumber is already used');
            }
        }

        // Auto check attributes.
        $attrdesc = array(
            'idnumber' => array(
                'output' => 'idnumber',
                'required' => 0,
            ),
            'visible' => array(
                'output' => 'visible',
                'required' => 0,
                'default' => 1,
            ),
            'description' => array(
                'output' => 'description',
                'required' => 0,
            ),
        );

        $this->check_context_attributes($attrdesc);

        return (!empty($this->errorlog));
    }

}