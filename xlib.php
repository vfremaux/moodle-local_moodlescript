<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * External calls to moodlescripts
 *
 * @package    local_moodlescript
 * @category   test
 * @copyright  2013 Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/local/moodlescript/lib.php');
require_once($CFG->dirroot.'/local/moodlescript/classes/engine/stack.class.php');

/**
 * Generates a setting adding a focussed condition and adds it to the provided setting page.
 * @param objectref &$settingpage 
 * @param string $settingkey the setting key. Must be unique.
 * @param int $i if settings are numerically indexed, the numeric index.
 */
function local_moodlescript_add_display_condition(&$settingpage, $settingkey, $i = 0) {
    global $OUTPUT;

    if ($i) {
        $title = get_string('dispconditionsetting', 'local_moodlescript')." $i";
    } else {
        $title = get_string('dispconditionsetting', 'local_moodlescript');
    }
    $description = get_string('dispconditionsetting_desc', 'local_moodlescript');
    $description .= $OUTPUT->help_icon('moodlescriptexpressions', 'local_moodlescript'); 
    $default = '';
    $setting = new admin_setting_configtext($settingkey, $title, $description, $default);
    $settingpage->add($setting);
}

/**
 * Sets up a parser and evaluates an expression.
 */
function local_moodlescript_evaluate_expression($expression) {
    static $parser;

    local_moodlescript_load_engine();

    if (empty($expression)) {
        return true;
    }

    $script = '';
    if (is_null($parser)) {
        $parser = new \local_moodlescript\engine\parser($script);
    }
    $evaluable = new \local_moodlescript\engine\evaluable_expression($expression, $parser);
    $parseresult = $evaluable->parse();
    return $evaluable->evaluate();
}