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
 * PHPUnit tests for user_autoenrolled event.
 *
 * @package    local_subcourseenrol
 * @category   test
 * @copyright  2026 Héctor Eduardo Terán Canelones
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_subcourseenrol;

/**
 * Event tests.
 *
 * @package    local_subcourseenrol
 * @category   test
 * @copyright  2026 Héctor Eduardo Terán Canelones
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\local_subcourseenrol\event\user_autoenrolled::class)]
class event_test extends \advanced_testcase {
    /**
     * Setup before each test.
     */
    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Test basic event instantiation and properties.
     */
    public function test_event_properties(): void {
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $context = \context_course::instance($course->id);

        $objectid = 10;
        $event = \local_subcourseenrol\event\user_autoenrolled::create([
            'objectid' => $objectid,
            'userid' => $user->id,
            'courseid' => $course->id,
            'relateduserid' => $user->id,
            'context' => $context,
            'other' => [
                'mastercourseid' => 999,
            ],
        ]);

        $this->assertSame('c', $event->crud);
        $this->assertSame(\core\event\base::LEVEL_PARTICIPATING, $event->edulevel);
        $this->assertSame('user_enrolments', $event->objecttable);
        $this->assertSame($objectid, $event->objectid);
        $this->assertSame($user->id, $event->userid);
        $this->assertSame($course->id, $event->courseid);
        $this->assertSame($user->id, $event->relateduserid);
    }

    /**
     * Test event name.
     */
    public function test_get_name(): void {
        $name = \local_subcourseenrol\event\user_autoenrolled::get_name();
        $this->assertSame(get_string('event_user_autoenrolled', 'local_subcourseenrol'), $name);
    }

    /**
     * Test event description.
     */
    public function test_get_description(): void {
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $context = \context_course::instance($course->id);

        $event = \local_subcourseenrol\event\user_autoenrolled::create([
            'objectid' => 10,
            'userid' => $user->id,
            'courseid' => $course->id,
            'relateduserid' => $user->id,
            'context' => $context,
            'other' => [
                'mastercourseid' => 42,
            ],
        ]);

        $description = $event->get_description();
        $this->assertStringContainsString("id '$user->id'", $description);
        $this->assertStringContainsString("id '$course->id'", $description);
        $this->assertStringContainsString("id '42'", $description);
    }

    /**
     * Test event URL.
     */
    public function test_get_url(): void {
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $context = \context_course::instance($course->id);

        $event = \local_subcourseenrol\event\user_autoenrolled::create([
            'objectid' => 10,
            'userid' => $user->id,
            'courseid' => $course->id,
            'relateduserid' => $user->id,
            'context' => $context,
            'other' => [
                'mastercourseid' => 42,
            ],
        ]);

        $url = $event->get_url();
        $expected = new \moodle_url('/user/index.php', ['id' => $course->id]);
        $this->assertEquals($expected, $url);
    }

    /**
     * Test validate_data throws coding_exception when mastercourseid is missing.
     */
    public function test_validate_data_missing_mastercourseid(): void {
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $context = \context_course::instance($course->id);

        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessage("The 'mastercourseid' value must be set in other.");

        \local_subcourseenrol\event\user_autoenrolled::create([
            'objectid' => 10,
            'userid' => $user->id,
            'courseid' => $course->id,
            'relateduserid' => $user->id,
            'context' => $context,
            'other' => [],
        ]);
    }

    /**
     * Test backup/restore objectid mapping.
     */
    public function test_get_objectid_mapping(): void {
        $mapping = \local_subcourseenrol\event\user_autoenrolled::get_objectid_mapping();
        $this->assertIsArray($mapping);
        $this->assertSame(
            ['db' => 'user_enrolments', 'restore' => \core\event\base::NOT_MAPPED],
            $mapping
        );
    }

    /**
     * Test backup/restore other data mapping.
     */
    public function test_get_other_mapping(): void {
        $mapping = \local_subcourseenrol\event\user_autoenrolled::get_other_mapping();
        $this->assertIsArray($mapping);
        $this->assertSame(
            ['mastercourseid' => ['db' => 'course', 'restore' => \core\event\base::NOT_MAPPED]],
            $mapping
        );
    }
}
