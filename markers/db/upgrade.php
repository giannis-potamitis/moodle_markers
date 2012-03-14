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
 * This file keeps track of upgrades to the markers module
 *
 * Sometimes, changes between versions involve alterations to database
 * structures and other major things that may break installations. The upgrade
 * function in this file will attempt to perform all the necessary actions to
 * upgrade your older installation to the current version. If there's something
 * it cannot do itself, it will tell you what you need to do.  The commands in
 * here will all be database-neutral, using the functions defined in DLL libraries.
 *
 * @package    mod
 * @subpackage markers
 * @copyright  2011 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute markers upgrade from the given old version
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_markers_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager(); // loads ddl manager and xmldb classes

    // And upgrade begins here. For each one, you'll need one
    // block of code similar to the next one. Please, delete
    // this comment lines once this file start handling proper
    // upgrade code.

    // if ($oldversion < YYYYMMDD00) { //New version in version.php
    //
    // }

    // Lines below (this included)  MUST BE DELETED once you get the first version
    // of your module ready to be installed. They are here only
    // for demonstrative purposes and to show how the markers
    // iself has been upgraded.

    // For each upgrade block, the file markers/version.php
    // needs to be updated . Such change allows Moodle to know
    // that this file has to be processed.

    // To know more about how to write correct DB upgrade scripts it's
    // highly recommended to read information available at:
    //   http://docs.moodle.org/en/Development:XMLDB_Documentation
    // and to play with the XMLDB Editor (in the admin menu) and its
    // PHP generation posibilities.

    // First example, some fields were added to install.xml on 2007/04/01
    

    if ($oldversion < 2011122802) {

        // Define table markers_subassess to be created
        $table = new xmldb_table('markers_subassess');

        // Adding fields to table markers_subassess
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('mapid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('subcatid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('ranksid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

        // Adding keys to table markers_subassess
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('foreign_mapid', XMLDB_KEY_FOREIGN, array('mapid'), 'markers_map', array('id'));
        $table->add_key('foreign_subcatid', XMLDB_KEY_FOREIGN, array('subcatid'), 'cat_subcat', array('id'));
        $table->add_key('foreign_ranksid', XMLDB_KEY_FOREIGN, array('ranksid'), 'cat_ranks', array('id'));

        // Conditionally launch create table for markers_subassess
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // markers savepoint reached
        upgrade_plugin_savepoint(true, 2011122802, 'local', 'markers');
    }




		return true;

}

