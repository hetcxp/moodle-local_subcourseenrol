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
 * PHPUnit tests for role_resolver.
 *
 * @package    local_subcourseenrol
 * @category   test
 * @copyright  2026 Héctor Eduardo Terán Canelones
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_subcourseenrol;

/**
 * Role resolver unit tests.
 *
 * @package    local_subcourseenrol
 * @category   test
 * @copyright  2026 Héctor Eduardo Terán Canelones
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\local_subcourseenrol\local\role_resolver::class)]
class role_resolver_test extends \advanced_testcase {
    /**
     * Setup before each test.
     */
    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        \local_subcourseenrol\local\role_resolver::reset_cache();
    }

    /**
     * Test that a configured role assignable in CONTEXT_COURSE is resolved.
     */
    public function test_resolve_configured_role_assignable_in_course_context(): void {
        $roleid = $this->getDataGenerator()->create_role([
            'shortname' => 'customcourse',
            'name' => 'Custom Course Role',
        ]);
        set_role_contextlevels($roleid, [CONTEXT_COURSE]);
        set_config('roleid', $roleid, 'local_subcourseenrol');

        $resolved = \local_subcourseenrol\local\role_resolver::resolve_target_role();
        $this->assertInstanceOf(\stdClass::class, $resolved);
        $this->assertSame((int) $roleid, (int) $resolved->id);
        $this->assertSame('customcourse', $resolved->shortname);
    }

    /**
     * Test that a configured role not assignable in CONTEXT_COURSE falls back to student.
     */
    public function test_resolve_configured_role_not_assignable_in_course_falls_back_to_student(): void {
        $roleid = $this->getDataGenerator()->create_role([
            'shortname' => 'systemonly',
            'name' => 'System Only Role',
        ]);
        set_role_contextlevels($roleid, [CONTEXT_SYSTEM]);
        set_config('roleid', $roleid, 'local_subcourseenrol');

        $resolved = \local_subcourseenrol\local\role_resolver::resolve_target_role();
        $this->assertInstanceOf(\stdClass::class, $resolved);
        $this->assertSame('student', $resolved->shortname);
        $this->assertDebuggingCalled(
            "local_subcourseenrol: Configured roleid {$roleid} is not assignable in course context, " .
            'falling back to default.',
            DEBUG_DEVELOPER
        );
    }

    /**
     * Test that null is returned with debugging when no suitable role exists.
     */
    public function test_resolve_returns_null_with_debugging_when_no_role_available(): void {
        global $DB;

        set_config('roleid', 0, 'local_subcourseenrol');
        $DB->delete_records('role', ['shortname' => 'student']);
        $DB->delete_records('role', ['archetype' => 'student']);

        $resolved = \local_subcourseenrol\local\role_resolver::resolve_target_role();
        $this->assertNull($resolved);
        $this->assertDebuggingCalled(
            'local_subcourseenrol: No suitable enrolment role found (configured, student, or student archetype).',
            DEBUG_DEVELOPER
        );
    }
}
