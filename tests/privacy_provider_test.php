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
 * PHPUnit tests for privacy provider.
 *
 * @package    local_subcourseenrol
 * @category   test
 * @copyright  2026 Héctor Eduardo Terán Canelones
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_subcourseenrol;

/**
 * Privacy provider unit tests.
 *
 * @package    local_subcourseenrol
 * @category   test
 * @copyright  2026 Héctor Eduardo Terán Canelones
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\local_subcourseenrol\privacy\provider::class)]
class privacy_provider_test extends \advanced_testcase {
    /**
     * Test that provider implements null_provider and returns valid reason string.
     */
    public function test_null_provider_contract(): void {
        $this->assertTrue(
            is_a(\local_subcourseenrol\privacy\provider::class, \core_privacy\local\metadata\null_provider::class, true)
        );

        $reason = \local_subcourseenrol\privacy\provider::get_reason();
        $this->assertIsString($reason);
        $this->assertSame('privacy:metadata', $reason);
        $this->assertTrue(get_string_manager()->string_exists($reason, 'local_subcourseenrol'));
    }
}
