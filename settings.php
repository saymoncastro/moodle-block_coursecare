<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Site administration settings for the CourseCare block.
 *
 * @package    block_coursecare
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    // Large-file threshold in MB (1 MB = 1,048,576 bytes): a positive whole number.
    $settings->add(new admin_setting_configtext(
        'block_coursecare/largefilethreshold',
        new lang_string('settings:largefilethreshold', 'block_coursecare'),
        new lang_string('settings:largefilethreshold_desc', 'block_coursecare'),
        50,
        '/^[1-9][0-9]{0,6}$/',
        8
    ));

    $setting = new admin_setting_configduration(
        'block_coursecare/reanalysisinterval',
        new lang_string('settings:reanalysisinterval', 'block_coursecare'),
        new lang_string('settings:reanalysisinterval_desc', 'block_coursecare'),
        DAYSECS,
        HOURSECS
    );
    $setting->set_min_duration(HOURSECS);
    $settings->add($setting);

    $setting = new admin_setting_configduration(
        'block_coursecare/stalenessthreshold',
        new lang_string('settings:stalenessthreshold', 'block_coursecare'),
        new lang_string('settings:stalenessthreshold_desc', 'block_coursecare'),
        3 * DAYSECS,
        HOURSECS
    );
    $setting->set_min_duration(HOURSECS);
    $settings->add($setting);
}
