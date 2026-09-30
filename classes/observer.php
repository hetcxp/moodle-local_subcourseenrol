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
 * Event observer for local_subcourseenrol.
 *
 * @package    local_subcourseenrol
 * @copyright  2026 Héctor Eduardo Terán Canelones
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_subcourseenrol;

defined('MOODLE_INTERNAL') || die();


/**
 * Event observer class.
 *
 * @package    local_subcourseenrol
 * @copyright  2026 Héctor Eduardo Terán Canelones
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {
    /**
     * Check if the plugin is enabled.
     *
     * @return bool
     */
    private static function is_enabled(): bool {
        return (bool) get_config('local_subcourseenrol', 'enabled');
    }

    /**
     * Get the active enrolment in the master course.
     *
     * Prioritises perpetual enrolments (timeend = 0) over enrolments with a future expiry date.
     * Returns an object with only the timeend field populated.
     *
     * @param int $courseid Master course ID.
     * @param int $userid User ID.
     * @return \stdClass|null Object with timeend (int) or null if not found.
     */
    private static function get_master_enrolment(int $courseid, int $userid): ?\stdClass {
        global $DB;

        $now = time();
        $sql = "SELECT ue.timeend
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON ue.enrolid = e.id
                 WHERE e.courseid = :courseid
                   AND ue.userid = :userid
                   AND ue.status = :status
                   AND e.status = :enrolstatus
                   AND ue.timestart < :timestart
                   AND (ue.timeend = 0 OR ue.timeend > :timeend)
              ORDER BY CASE WHEN ue.timeend = 0 THEN 1 ELSE 0 END DESC,
                       ue.timeend DESC";
        $params = [
            'courseid'    => $courseid,
            'userid'      => $userid,
            'status'      => ENROL_USER_ACTIVE,
            'enrolstatus' => ENROL_INSTANCE_ENABLED,
            'timestart'   => $now,
            'timeend'     => $now,
        ];

        $enrolment = $DB->get_record_sql($sql, $params, IGNORE_MULTIPLE);
        return $enrolment ?: null;
    }

    /**
     * Resolve the manual enrolment instance for the course.
     *
     * @param int $courseid Target course ID.
     * @return \stdClass|null
     */
    private static function resolve_enrol_instance(int $courseid): ?\stdClass {
        global $DB;

        if (!enrol_is_enabled('manual')) {
            debugging('local_subcourseenrol: Manual enrolment plugin is disabled.', DEBUG_DEVELOPER);
            return null;
        }

        $instances = $DB->get_records('enrol', [
            'courseid' => $courseid,
            'enrol'    => 'manual',
            'status'   => ENROL_INSTANCE_ENABLED,
        ], 'sortorder, id ASC');

        if (empty($instances)) {
            debugging(
                "local_subcourseenrol: No enabled manual enrolment instance found in course {$courseid}. Skipping auto-enrolment.",
                DEBUG_DEVELOPER
            );
            return null;
        }

        return reset($instances) ?: null;
    }

    /**
     * Resolve the student role.
     *
     * @return \stdClass|null
     */
    private static function resolve_student_role(): ?\stdClass {
        global $DB;

        $studentrole = $DB->get_record('role', ['shortname' => 'student'], 'id', IGNORE_MISSING);
        if ($studentrole) {
            return $studentrole;
        }

        $roles = get_archetype_roles('student');
        if (empty($roles)) {
            return null;
        }

        return reset($roles);
    }

    /**
     * Observer for when a subcourse activity is viewed.
     *
     * @param \mod_subcourse\event\course_module_viewed $event
     */
    public static function subcourse_viewed(\mod_subcourse\event\course_module_viewed $event): void {
        global $DB;

        if (!self::is_enabled()) {
            return;
        }

        $subcourse = $event->get_record_snapshot('subcourse', $event->objectid);
        if (!$subcourse || empty($subcourse->refcourse)) {
            return;
        }

        $targetcourseid = (int)$subcourse->refcourse;
        if ($targetcourseid <= 0) {
            return;
        }

        $coursecontext = \context_course::instance($targetcourseid, IGNORE_MISSING);
        if (!$coursecontext) {
            return;
        }

        $userid = $event->userid;
        $mastercourseid = $event->courseid;

        if (is_enrolled($coursecontext, $userid, '', true)) {
            return;
        }

        $masterenrolment = self::get_master_enrolment($mastercourseid, $userid);
        if (!$masterenrolment) {
            return;
        }

        $instance = self::resolve_enrol_instance($targetcourseid);
        if (!$instance) {
            return;
        }

        // Do not reactivate or override an explicitly suspended enrolment.
        $existingue = $DB->get_record('user_enrolments', [
            'enrolid' => $instance->id,
            'userid'  => $userid,
        ], 'id, status', IGNORE_MISSING);
        if ($existingue && (int)$existingue->status === ENROL_USER_SUSPENDED) {
            return;
        }

        $studentrole = self::resolve_student_role();
        if (!$studentrole) {
            return;
        }

        $enrolplugin = enrol_get_plugin('manual');
        if (!$enrolplugin) {
            debugging('local_subcourseenrol: Manual enrolment plugin not found.', DEBUG_DEVELOPER);
            return;
        }

        $enrolplugin->enrol_user($instance, $userid, $studentrole->id, time(), (int)$masterenrolment->timeend);

        // Fetch the user_enrolments ID for the event objectid.
        $ue = $DB->get_record('user_enrolments', ['enrolid' => $instance->id, 'userid' => $userid], 'id', IGNORE_MISSING);
        $objectid = $ue ? $ue->id : 0;
        if ($objectid === 0) {
            debugging(
                'local_subcourseenrol: Could not retrieve user_enrolments id after enrol_user; event objectid will be 0.',
                DEBUG_DEVELOPER
            );
        }

        // Log the auto-enrolment.
        $autoenrolevent = \local_subcourseenrol\event\user_autoenrolled::create([
            'objectid' => $objectid,
            'userid' => $userid,
            'courseid' => $targetcourseid,
            'relateduserid' => $userid,
            'context' => $coursecontext,
            'other' => [
                'mastercourseid' => $mastercourseid,
            ],
        ]);
        $autoenrolevent->trigger();
    }
}
