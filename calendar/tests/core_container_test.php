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
 * Event container tests.
 *
 * @package    core_calendar
 * @copyright  2017 Cameron Ball <cameron@cameron1729.xyz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use core_calendar\local\event\entities\action_event;
use core_calendar\local\event\entities\event;
use core_calendar\local\event\factories\event_factory;
use core_calendar\local\event\mappers\event_mapper;
use core_calendar\local\interfaces\event_factory_interface;
use core_calendar\local\interfaces\event_interface;
use core_calendar\local\interfaces\event_mapper_interface;

/**
 * Core container testcase.
 *
 * @copyright 2017 Cameron Ball <cameron@cameron1729.xyz>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_calendar_container_testcase extends advanced_testcase {
    /**
     * Test getting the event factory.
     *
     * @dataProvider get_event_factory_testcases()
     * @param \stdClass $dbrow Row from the "database".
     */
    public function test_get_event_factory($dbrow) {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $legacyevent = $this->create_event($dbrow);
        $factory = \core_calendar\local\event\core_container::get_event_factory();

        $this->assertInstanceOf(event_factory_interface::class, $factory);
        $this->assertInstanceOf(event_factory::class, $factory);

        $factory2 = \core_calendar\local\event\core_container::get_event_factory();

        $this->assertTrue($factory === $factory2);

        $dbrow->id = $legacyevent->id;
        $event = $factory->create_instance($dbrow);

        if (is_null($event)) {
            return;
        }

        $this->assertInstanceOf(event_interface::class, $event);
        $this->assertTrue($event instanceof event || $event instanceof action_event);

        $this->assertEquals($legacyevent->id, $event->get_id());
        $this->assertEquals($dbrow->description, $event->get_description()->get_value());
        $this->assertEquals($dbrow->format, $event->get_description()->get_format());
        $this->assertEquals($dbrow->courseid, $event->get_course()->get_id());

        if ($dbrow->groupid == 0) {
            $this->assertNull($event->get_group());
        } else {
            $this->assertEquals($dbrow->groupid, $event->get_group()->get_id());
        }

        $this->assertEquals($dbrow->userid, $event->get_user()->get_id());
        $this->assertEquals($legacyevent->id, $event->get_repeats()->get_id());
        $this->assertEquals($dbrow->modulename, $event->get_course_module()->get('modname'));
        $this->assertEquals($dbrow->instance, $event->get_course_module()->get('instance'));
        $this->assertEquals($dbrow->timestart, $event->get_times()->get_start_time()->getTimestamp());
        $this->assertEquals($dbrow->timemodified, $event->get_times()->get_modified_time()->getTimestamp());
        $this->assertEquals($dbrow->timesort, $event->get_times()->get_sort_time()->getTimestamp());

        if ($dbrow->visible == 1) {
            $this->assertTrue($event->is_visible());
        } else {
            $this->assertFalse($event->is_visible());
        }

        if (!$dbrow->subscriptionid) {
            $this->assertNull($event->get_subscription());
        } else {
            $this->assertEquals($event->get_subscription()->get_id());
        }
    }

    /**
     * Test getting the event mapper.
     */
    public function test_get_event_mapper() {
        $mapper = \core_calendar\local\event\core_container::get_event_mapper();

        $this->assertInstanceOf(event_mapper_interface::class, $mapper);
        $this->assertInstanceOf(event_mapper::class, $mapper);

        $mapper2 = \core_calendar\local\event\core_container::get_event_mapper();

        $this->assertTrue($mapper === $mapper2);
    }

    /**
     * Test cases for the get event factory test.
     */
    public function get_event_factory_testcases() {
        return [
            'Data set 1' => [
                'dbrow' => (object)[
                    'name' => 'Test event',
                    'description' => 'Hello',
                    'format' => 1,
                    'courseid' => 1,
                    'groupid' => 0,
                    'userid' => 1,
                    'repeatid' => 0,
                    'modulename' => 'assign',
                    'instance' => 2,
                    'eventtype' => 'due',
                    'timestart' => 1486396800,
                    'timeduration' => 0,
                    'timesort' => 1486396800,
                    'visible' => 1,
                    'timemodified' => 1485793098,
                    'subscriptionid' => null
                ]
            ],

            'Data set 2' => [
                'dbrow' => (object)[
                    'name' => 'Test event',
                    'description' => 'Hello',
                    'format' => 1,
                    'courseid' => 1,
                    'groupid' => 1,
                    'userid' => 1,
                    'repeatid' => 1,
                    'modulename' => 'assign',
                    'instance' => 2,
                    'eventtype' => 'due',
                    'timestart' => 1486396800,
                    'timeduration' => 0,
                    'timesort' => 1486396800,
                    'visible' => 1,
                    'timemodified' => 1485793098,
                    'subscriptionid' => null
                ]
            ]
        ];
    }

    /**
     * Helper function to create calendar events using the old code.
     *
     * @param array $properties A list of calendar event properties to set
     * @return event
     */
    protected function create_event($properties = []) {
        $record = new \stdClass();
        $record->name = 'event name';
        $record->eventtype = 'global';
        $record->timestart = time();
        $record->timeduration = 0;
        $record->timesort = 0;
        $record->type = 1;
        $record->courseid = 0;

        foreach ($properties as $name => $value) {
            $record->$name = $value;
        }

        $event = new \core_calendar\event($record);
        return $event->create($record, false);
    }
}
