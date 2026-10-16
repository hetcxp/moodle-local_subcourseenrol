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

/**
 * Event observer class.
 *
 * @package    local_subcourseenrol
 * @copyright  2026 Héctor Eduardo Terán Canelones
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {
    /** @var bool|null Cached enabled setting per request. */
    private static ?bool $enabled = null;

    /** @var \stdClass|null Cached resolved target role per request. */
    private static ?\stdClass $targetrole = null;

    /** @var array<int, \stdClass|null> Cached resolved manual instances by course ID per request. */
    private static array $instances = [];

    /**
     * Reset static cache. Primarily used in unit testing.
     *
     * @return void
     */
    public static function reset_caches(): void {
        self::$enabled = null;
        self::$targetrole = null;
        self::$instances = [];
    }

    /**
     * Check if the plugin is enabled.
     *
     * @return bool
     */
    private static function is_enabled(): bool {
        if (self::$enabled === null) {
            self::$enabled = (bool) get_config('local_subcourseenrol', 'enabled');
        }
        return self::$enabled;
    }

    /**
     * Get the active enrolment in the master course.
     *
     * Prioritises perpetual enrolments (timeend = 0) over enrolments with a future expiry date.
     * Returns an object with timestart and timeend fields populated.
     *
     * @param int $courseid Master course ID.
     * @param int $userid User ID.
     * @return \stdClass|null Object with timestart (int) and timeend (int) or null if not found.
     */
    private static function get_master_enrolment(int $courseid, int $userid): ?\stdClass {
        global $DB;

        $now = time();
        $sql = "SELECT ue.timestart, ue.timeend
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON ue.enrolid = e.id
                 WHERE e.courseid = :courseid
                   AND ue.userid = :userid
                   AND ue.status = :status
                   AND e.status = :enrolstatus
                   AND ue.timestart <= :timestart
                   AND (ue.timeend = 0 OR ue.timeend > :timeend)
              ORDER BY CASE WHEN ue.timeend = 0 THEN 1 ELSE 0 END DESC,
                       ue.timeend DESC,
                       ue.timestart ASC,
                       ue.id ASC";
        $params = [
            'courseid'    => $courseid,
            'userid'      => $userid,
            'status'      => ENROL_USER_ACTIVE,
            'enrolstatus' => ENROL_INSTANCE_ENABLED,
            'timestart'   => $now,
            'timeend'     => $now,
        ];

        $enrolments = $DB->get_records_sql($sql, $params, 0, 1);
        if (empty($enrolments)) {
            return null;
        }

        return reset($enrolments) ?: null;
    }

    /**
     * Resolve the manual enrolment instance for the course.
     *
     * @param int $courseid Target course ID.
     * @return \stdClass|null
     */
    private static function resolve_enrol_instance(int $courseid): ?\stdClass {
        global $DB;

        if (array_key_exists($courseid, self::$instances)) {
            return self::$instances[$courseid];
        }

        if (!enrol_is_enabled('manual')) {
            debugging('local_subcourseenrol: Manual enrolment plugin is disabled.', DEBUG_DEVELOPER);
            self::$instances[$courseid] = null;
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
            self::$instances[$courseid] = null;
            return null;
        }

        self::$instances[$courseid] = reset($instances) ?: null;
        return self::$instances[$courseid];
    }

    /**
     * Resolve the target enrolment role.
     *
     * Prioritises the configured role ID if set. Otherwise resolves the 'student' role
     * by shortname or falls back to archetype roles.
     *
     * @return \stdClass|null
     */
    private static function resolve_target_role(): ?\stdClass {
        global $DB;

        if (self::$targetrole !== null) {
            return self::$targetrole;
        }

        $configroleid = (int) get_config('local_subcourseenrol', 'roleid');
        if ($configroleid > 0) {
            $configuredrole = $DB->get_record('role', ['id' => $configroleid], 'id, name, shortname, archetype', IGNORE_MISSING);
            if ($configuredrole) {
                self::$targetrole = $configuredrole;
                return self::$targetrole;
            }
            debugging(
                "local_subcourseenrol: Configured roleid {$configroleid} not found, falling back to default.",
                DEBUG_DEVELOPER
            );
        }

        $studentrole = $DB->get_record('role', ['shortname' => 'student'], 'id, name, shortname, archetype', IGNORE_MISSING);
        if ($studentrole) {
            self::$targetrole = $studentrole;
            return self::$targetrole;
        }

        $roles = get_archetype_roles('student');
        if (empty($roles)) {
            return null;
        }

        self::$targetrole = reset($roles);
        return self::$targetrole;
    }

    /**
     * Validate and extract event payload from subcourse view event.
     *
     * @param \mod_subcourse\event\course_module_viewed $event
     * @return array|null Array with targetcourseid, coursecontext, userid, mastercourseid, or null if invalid.
     */
    private static function extract_event_payload(\mod_subcourse\event\course_module_viewed $event): ?array {
        $subcourse = $event->get_record_snapshot('subcourse', $event->objectid);
        if (!$subcourse || empty($subcourse->refcourse)) {
            return null;
        }

        $targetcourseid = (int)$subcourse->refcourse;
        if ($targetcourseid <= 0) {
            return null;
        }

        $coursecontext = \context_course::instance($targetcourseid, IGNORE_MISSING);
        if (!$coursecontext) {
            return null;
        }

        $userid = (int)$event->userid;
        if ($userid <= 0) {
            debugging('local_subcourseenrol: Invalid userid in subcourse_viewed event.', DEBUG_DEVELOPER);
            return null;
        }
        if (isguestuser($userid)) {
            return null;
        }

        return [
            'targetcourseid' => $targetcourseid,
            'coursecontext'  => $coursecontext,
            'userid'         => $userid,
            'mastercourseid' => $event->courseid,
        ];
    }

    /**
     * Check if the user is eligible for enrolment.
     *
     * @param \context_course $coursecontext Target course context.
     * @param int $userid User ID.
     * @param int $instanceid Target enrol instance ID.
     * @return bool
     */
    private static function is_enrolment_eligible(\context_course $coursecontext, int $userid, int $instanceid): bool {
        global $DB;

        if (is_enrolled($coursecontext, $userid, '', true)) {
            return false;
        }

        // Do not reactivate or override an explicitly suspended enrolment.
        $existingue = $DB->get_record('user_enrolments', [
            'enrolid' => $instanceid,
            'userid'  => $userid,
        ], 'id, status', IGNORE_MISSING);

        if ($existingue && (int)$existingue->status === ENROL_USER_SUSPENDED) {
            return false;
        }

        return true;
    }

    /**
     * Dispatch auto-enrolment and fire audit event under concurrency lock.
     *
     * @param array $payload Event payload.
     * @param \stdClass $masterenrolment Active enrolment in master course.
     * @param \stdClass $instance Target manual enrol instance.
     * @param \stdClass $role Target role.
     * @return void
     */
    private static function dispatch_enrolment(
        array $payload,
        \stdClass $masterenrolment,
        \stdClass $instance,
        \stdClass $role
    ): void {
        global $DB;

        $targetcourseid = $payload['targetcourseid'];
        $userid = $payload['userid'];
        $coursecontext = $payload['coursecontext'];
        $mastercourseid = $payload['mastercourseid'];

        $enrolplugin = enrol_get_plugin('manual');
        if (!$enrolplugin) {
            debugging('local_subcourseenrol: Manual enrolment plugin not found.', DEBUG_DEVELOPER);
            return;
        }

        $lockfactory = \core\lock\lock_config::get_lock_factory('local_subcourseenrol');
        $lockkey = "enrol_{$targetcourseid}_{$userid}";
        $lock = $lockfactory->get_lock($lockkey, 5);
        if (!$lock) {
            debugging("local_subcourseenrol: Could not acquire lock for {$lockkey}.", DEBUG_DEVELOPER);
            return;
        }

        try {
            if (!self::is_enrolment_eligible($coursecontext, $userid, (int)$instance->id)) {
                return;
            }

            try {
                $enrolplugin->enrol_user(
                    $instance,
                    $userid,
                    $role->id,
                    (int)$masterenrolment->timestart,
                    (int)$masterenrolment->timeend
                );
            } catch (\Throwable $e) {
                debugging('local_subcourseenrol: Exception during enrol_user: ' . $e->getMessage() . "\n" .
                    $e->getTraceAsString(), DEBUG_DEVELOPER);
                return;
            }

            try {
                // Fetch the user_enrolments ID for the event objectid.
                $ue = $DB->get_record(
                    'user_enrolments',
                    ['enrolid' => $instance->id, 'userid' => $userid],
                    'id',
                    IGNORE_MISSING
                );
                $objectid = $ue ? (int)$ue->id : 0;
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
            } catch (\Throwable $e) {
                debugging('local_subcourseenrol: Exception while triggering user_autoenrolled event: ' .
                    $e->getMessage(), DEBUG_DEVELOPER);
            }
        } finally {
            $lock->release();
        }
    }

    /**
     * Observer for when a subcourse activity is viewed.
     *
     * @param \mod_subcourse\event\course_module_viewed $event
     */
    public static function subcourse_viewed(\mod_subcourse\event\course_module_viewed $event): void {
        if (!self::is_enabled()) {
            return;
        }

        $payload = self::extract_event_payload($event);
        if (!$payload) {
            return;
        }

        $instance = self::resolve_enrol_instance($payload['targetcourseid']);
        if (!$instance) {
            return;
        }

        if (!self::is_enrolment_eligible($payload['coursecontext'], $payload['userid'], (int)$instance->id)) {
            return;
        }

        $masterenrolment = self::get_master_enrolment($payload['mastercourseid'], $payload['userid']);
        if (!$masterenrolment) {
            return;
        }

        $targetrole = self::resolve_target_role();
        if (!$targetrole) {
            return;
        }

        self::dispatch_enrolment($payload, $masterenrolment, $instance, $targetrole);
    }
}
