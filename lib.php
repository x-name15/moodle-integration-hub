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
 * Library functions for Integration Hub.
 *
 * @package    local_integrationhub
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Adds Integration Hub link to the Moodle navigation.
 *
 * @param global_navigation $navigation The global navigation object.
 */
function local_integrationhub_extend_navigation(global_navigation $navigation)
{
    global $PAGE;

    $context = context_system::instance();
    if (has_capability('local/integrationhub:view', $context)) {
        $node = $navigation->add(
            get_string('pluginname', 'local_integrationhub'),
            new moodle_url('/local/integrationhub/index.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'integrationhub',
            new pix_icon('i/settings', '')
        );
        $node->showinflatnavigation = true;
    }
}
