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
 * Upgrade steps for the CourseCare block.
 *
 * @package    block_coursecare
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade the CourseCare block.
 *
 * @param int $oldversion The version of the plugin that is installed.
 * @return bool
 */
function xmldb_block_coursecare_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026101601) {
        // Feature 002: storage diagnostic tables.
        // Define table block_coursecare_diag to be created.
        $table = new xmldb_table('block_coursecare_diag');

        // Adding fields to table block_coursecare_diag.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('requeststatus', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timerequested', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('timestarted', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('timeattempted', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('timecompleted', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('lastattemptfailed', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('failurecount', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('failurereason', XMLDB_TYPE_CHAR, '30');
        $table->add_field('methodversion', XMLDB_TYPE_INTEGER, '4');
        $table->add_field('largethreshold', XMLDB_TYPE_INTEGER, '20');
        $table->add_field('filecount', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('logicalbytes', XMLDB_TYPE_INTEGER, '20');
        $table->add_field('uniquecount', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('uniquebytes', XMLDB_TYPE_INTEGER, '20');
        $table->add_field('largecount', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('largebytes', XMLDB_TYPE_INTEGER, '20');
        $table->add_field('largeuniquecount', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('largeuniquebytes', XMLDB_TYPE_INTEGER, '20');
        $table->add_field('unclassifiedcount', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('unclassifiedbytes', XMLDB_TYPE_INTEGER, '20');
        $table->add_field('refinternalcount', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('refinternalbytes', XMLDB_TYPE_INTEGER, '20');
        $table->add_field('refexternalcount', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('refexternalbytes', XMLDB_TYPE_INTEGER, '20');
        $table->add_field('refundeterminedcount', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('brcount', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('brbytes', XMLDB_TYPE_INTEGER, '20');
        $table->add_field('bruniquecount', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('bruniquebytes', XMLDB_TYPE_INTEGER, '20');
        $table->add_field('partial', XMLDB_TYPE_INTEGER, '1');
        $table->add_field('largelinkstruncated', XMLDB_TYPE_INTEGER, '1');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table block_coursecare_diag.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Adding indexes to table block_coursecare_diag.
        $table->add_index('courseid', XMLDB_INDEX_UNIQUE, ['courseid']);
        $table->add_index('requeststatus-timeattempted', XMLDB_INDEX_NOTUNIQUE, ['requeststatus', 'timeattempted']);

        // Conditionally launch create table for block_coursecare_diag.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table block_coursecare_cat to be created.
        $table = new xmldb_table('block_coursecare_cat');

        // Adding fields to table block_coursecare_cat.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('diagid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('category', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL);
        $table->add_field('filecount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('logicalbytes', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('uniquecount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('uniquebytes', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table block_coursecare_cat.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('diagid', XMLDB_KEY_FOREIGN, ['diagid'], 'block_coursecare_diag', ['id']);

        // Adding indexes to table block_coursecare_cat.
        $table->add_index('diagid-category', XMLDB_INDEX_UNIQUE, ['diagid', 'category']);

        // Conditionally launch create table for block_coursecare_cat.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table block_coursecare_act to be created.
        $table = new xmldb_table('block_coursecare_act');

        // Adding fields to table block_coursecare_act.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('diagid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('cmid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('filecount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('logicalbytes', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('materialbytes', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('submissionbytes', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('otherbytes', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('unclassifiedbytes', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('largecount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('largebytes', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('largeuniquecount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('largeuniquebytes', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table block_coursecare_act.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('diagid', XMLDB_KEY_FOREIGN, ['diagid'], 'block_coursecare_diag', ['id']);

        // Adding indexes to table block_coursecare_act.
        $table->add_index('diagid-cmid', XMLDB_INDEX_UNIQUE, ['diagid', 'cmid']);

        // Conditionally launch create table for block_coursecare_act.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table block_coursecare_large to be created.
        $table = new xmldb_table('block_coursecare_large');

        // Adding fields to table block_coursecare_large.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('diagid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('fileid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('cmid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('filesize', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table block_coursecare_large.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('diagid', XMLDB_KEY_FOREIGN, ['diagid'], 'block_coursecare_diag', ['id']);

        // Conditionally launch create table for block_coursecare_large.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_block_savepoint(true, 2026101601, 'coursecare');
    }

    if ($oldversion < 2026101900) {
        // Feature 006: history of the storage diagnostic.
        $table = new xmldb_table('block_coursecare_hist');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('timeday', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecompleted', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('methodversion', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('partial', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('filecount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('logicalbytes', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('uniquecount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('uniquebytes', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('materialbytes', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('submissionbytes', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('otherbytes', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('unclassifiedbytes', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('courseid-timeday', XMLDB_INDEX_UNIQUE, ['courseid', 'timeday']);
        $table->add_index('timeday', XMLDB_INDEX_NOTUNIQUE, ['timeday']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // The current result of each course, a real dated measurement, becomes its first point.
        require_once(__DIR__ . '/upgradelib.php');
        block_coursecare_seed_history();

        upgrade_block_savepoint(true, 2026101900, 'coursecare');
    }

    if ($oldversion < 2026102200) {
        // Feature 009: the suggestions found by the analysis (activity ids and kind, no personal data). Existing
        // records stay null, meaning "not calculated yet", until the next analysis of each course.
        $table = new xmldb_table('block_coursecare_diag');
        $field = new xmldb_field('suggestions', XMLDB_TYPE_TEXT, null, null, null, null, null, 'largelinkstruncated');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_block_savepoint(true, 2026102200, 'coursecare');
    }

    return true;
}
