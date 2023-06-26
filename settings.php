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
 * Global settings.
 *
 * @package    local_moodlescript
 * @category   local
 * @author     Valery Fremaux <valery.fremaux@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Needs this condition or there is error on login page.
    $settings = new admin_settingpage('localsettingmoodlescript', get_string('pluginname', 'local_moodlescript'));
    $ADMIN->add('localplugins', $settings);

    $key = 'local_moodlescript/missingvariableoutput';
    $label = get_string('configmissingvariableoutput', 'local_moodlescript');
    $desc = get_string('configmissingvariableoutput_desc', 'local_moodlescript');

    $options = [
        'blank' => get_string('blank', 'local_moodlescript'),
        'signalled' => get_string('signalled', 'local_moodlescript'),
        'ignored' => get_string('ignored', 'local_moodlescript'),
    ];

    $settings->add(new admin_setting_configselect($key, $label, $desc, 'blank', $options));
}
