<?php

require($CFG->dirroot.'/lib/formslib.php');

class PatternTestForm extends moodleform {

    public function definition() {

        $mform = $this->_form;

        $mform->addElement('textarea', 'phppattern', get_string('phppattern', 'local_moodlescript'), array('cols' => 120, 'rows' => 12));

        $mform->addElement('textarea', 'stringsample', get_string('stringsample', 'local_moodlescript'), array('cols' => 120, 'rows' => 5));

        $this->add_action_buttons();
    
    }

}