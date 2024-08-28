<?php
declare(strict_types=1);

namespace Meraki\Html\Calendar;
use Brick\DateTime\DayOfWeek;

/**
 * Represents the operating hours of a calendar.
 *
 * The operating hours are the time slots that the calendar is open for
 * and closed for in a day. For example, if the calendar is open from
 * 9am to 5pm with a lunch break from 12pm to 1pm, the operating hours
 * would be 9am to 12pm and 1pm to 5pm.
 *
 * Normally, the operating hours would be the same for each day of the
 * week, but this class allows for different operating hours on different
 * days.
 */
final class OperatingHours
{
	public TimeSlots $monday;
	public TimeSlots $tuesday;
	public TimeSlots $wednesday;
	public TimeSlots $thursday;
	public TimeSlots $friday;
	public TimeSlots $saturday;
	public TimeSlots $sunday;

	public function __construct(array $operatingHours)
	{
		if (count($operatingHours) > 7) {
			throw new \InvalidArgumentException('Operating hours must be provided for each day of the week.');
		}

		$days = DayOfWeek::all();

		foreach ($operatingHours as $dayOfWeek => $hours) {
			if (!($dayOfWeek instanceof DayOfWeek)) {
				throw new \InvalidArgumentException('Day of week must be an instance of DayOfWeek.');
			}

			// ensure each day has been provided and only once
			if (!in_array($dayOfWeek, $days)) {
				throw new \InvalidArgumentException('Operating hours must be provided for each day of the week.');
			}

			if (!($hours instanceof TimeSlots)) {
				throw new \InvalidArgumentException('Operating hours must be an instance of TimeSlots.');
			}
		}
	}
}
