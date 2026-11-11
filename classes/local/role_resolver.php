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
 * Role resolver for local_subcourseenrol.
 *
 * @package    local_subcourseenrol
 * @copyright  2026 Héctor Eduardo Terán Canelones
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_subcourseenrol\local;

/**
 * Resolves enrolment roles with static caching.
 *
 * @package    local_subcourseenrol
 * @copyright  2026 Héctor Eduardo Terán Canelones
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class role_resolver {
    /** @var \stdClass|null Cached resolved target role per request. */
    private static ?\stdClass $targetrole = null;

    /**
     * Reset static cache. Primarily used in unit testing.
     *
     * @return void
     */
    public static function reset_cache(): void {
        self::$targetrole = null;
    }

    /**
     * Resolve the target enrolment role.
     *
     * Prioritises the configured role ID if set. Otherwise resolves the 'student' role
     * by shortname or falls back to archetype roles.
     *
     * @return \stdClass|null
     */
    public static function resolve_target_role(): ?\stdClass {
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
}
