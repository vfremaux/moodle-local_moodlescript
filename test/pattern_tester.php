<?php

namespace local_moodlescript\engine;

use context_system;
use moodle_url;
use Exception;

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

echo $OUTPUT->header();

if ($data = $form->get_data()) {
    $phppattern = str_replace('tokenizer', '\local_moodlescript\engine\tokenizer', $data->phppattern);
    try  {
        eval($phppattern);
        $result = (preg_match($pattern, $data->stringsample, $matches)) ? 'Match' : 'No match';
    } catch (Exception $ex) {
        echo '<pre>';
        echo "Php pattenr parisng error:\n";
        echo $ex->getMessage();
        echo '</pre>';
    }
}

if (!empty($result)) {
    echo '<pre>';
    echo "Input Pattern: $data->phppattern\n";
    echo "Resolved Pattern: $pattern\n";
    echo "Value: $data->stringsample\n";
    echo "Match: $result\n";

    if (!empty($matches)) {
        echo "\n";
        echo "SubMatches:\n";
        $i = 0;
        foreach ($matches as $match) {
            echo "   [$i] $match\n";
        }
    }

    echo '</pre>';
}

$form->display();
echo $OUTPUT->footer();