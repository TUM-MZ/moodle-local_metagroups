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
 * @package    local_metagroups
 * @copyright  2014 Paul Holden (pholden@greenhead.ac.uk)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_metagroups;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/metagroups/locallib.php');

class observers {

    /**
     * Group created
     *
     * @param \core\event\group_created $event
     * @return void
     */
    public static function group_created(\core\event\group_created $event) {
        global $DB;

        $child_group = $event->get_record_snapshot('groups', $event->objectid);

        $courseids = local_metagroups_parent_courses($child_group->courseid);
        foreach ($courseids as $courseid) {
            $parent_course = get_course($courseid);

            // If parent course doesn't use groups, we can skip synchronization.
            if (groups_get_course_groupmode($parent_course) == NOGROUPS) {
                continue;
            }

            local_metagroups_connect_child_group($parent_course, $child_group);
        }
    }

    /**
     * Group updated
     *
     * @param \core\event\group_updated $event
     * @return void
     */
    public static function group_updated(\core\event\group_updated $event) {
        global $DB;

        $child_group = $event->get_record_snapshot('groups', $event->objectid);

        $courseids = local_metagroups_parent_courses($child_group->courseid);
        foreach ($courseids as $courseid) {
            $parent_course = get_course($courseid);

            if ($parent_group = local_metagroups_get_parent_group($parent_course, $child_group)) {
                $parent_group->name = $child_group->name;

                groups_update_group($parent_group, false, false);
            }
        }
    }

    /**
     * Group deleted
     *
     * @param \core\event\group_deleted $event
     * @return void
     */
    public static function group_deleted(\core\event\group_deleted $event) {
        global $DB;

        $child_group = $event->get_record_snapshot('groups', $event->objectid);

        $courseids = local_metagroups_parent_courses($child_group->courseid);
        foreach ($courseids as $courseid) {
            $parent_course = get_course($courseid);

            if ($parent_group = local_metagroups_get_parent_group($parent_course, $child_group)) {
                groups_delete_group($parent_group);
            }
        }
    }

    /**
     * Group member added
     *
     * @param \core\event\group_member_added $event
     * @return void
     */
    public static function group_member_added(\core\event\group_member_added $event) {
        global $DB;

        $child_group = $event->get_record_snapshot('groups', $event->objectid);
        $user = \core_user::get_user($event->relateduserid, '*', MUST_EXIST);

        $courseids = local_metagroups_parent_courses($child_group->courseid);
        foreach ($courseids as $parent_course_id) {
            $parent_course = get_course($parent_course_id);

            if ($parent_group = local_metagroups_get_parent_group($parent_course, $child_group)) {
                groups_add_member($parent_group, $user, 'local_metagroups', $child_group->id);
            }
        }
    }

    /**
     * Group member removed
     *
     * @param \core\event\group_member_removed $event
     * @return void
     */
    public static function group_member_removed(\core\event\group_member_removed $event) {
        global $DB;

        $child_group = $event->get_record_snapshot('groups', $event->objectid);
        $user = \core_user::get_user($event->relateduserid, '*', MUST_EXIST);

        $courseids = local_metagroups_parent_courses($child_group->courseid);
        foreach ($courseids as $courseid) {
            $parent_course = get_course($courseid);

            if ($parent_group = local_metagroups_get_parent_group($parent_course, $child_group)) {
                groups_remove_member($parent_group, $user);
            }
        }
    }
}
