<?php

namespace local_moodlescript\engine;

use \context_system;
use \moodle_url;

require('../../../config.php');

$context = context_system::instance();
$PAGE->set_context($context);
$url = new moodle_url('/local/moodlescript/test/pattern_tester.php');
$PAGE->set_url($url);

// Security.

require_login();
require_capability('moodle/site:config', $context);

require_once($CFG->dirroot.'/local/moodlescript/test/pattern_test_form.php');
require_once($CFG->dirroot.'/local/moodlescript/classes/engine/tokenizer.class.php');

$form = new \PatternTestForm();

if ($data = $form->get_data()) {
    $phppattern = str_replace('tokenizer', '\local_moodlescript\engine\tokenizer', $data->phppattern);
    eval($phppattern);
    $result = (preg_match($pattern, $data->stringsample)) ? 'Match' : 'No match';
}

echo $OUTPUT->header();

if (!empty($result)) {
    echo '<pre>';
    echo "Input Pattern: $data->phppattern\n";
    echo "Resolved Pattern: $pattern\n";
    echo "Value: $data->stringsample\n";
    echo "Match: $result\n";
    echo '</pre>';
}

$form->display();
echo $OUTPUT->footer();