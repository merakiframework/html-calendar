<?php
declare(strict_types=1);

namespace Meraki\Html\Calendar;

use Brick\DateTime\Duration;
use Brick\DateTime\LocalTime;

/**
 * Represents a list of time slots between the earliest and latest hours
 * for the day. For example, if your day starts at 9am and ends at 5pm,
 * and the duration of each slot is 15 minutes, then the time slots will
 * be 9am, 9:15am, 9:30am, ..., 4:45pm, 5pm.
 *
 * The earliest and latest times are inclusive.
 */
final class TimeSlots implements \IteratorAggregate, \Countable
{
	public function __construct(public LocalTime $earliest, public LocalTime $latest, public Duration $duration)
	{
	}

	public static function allHours(Duration $duration): self
	{
		return new self(LocalTime::of(0, 0), LocalTime::of(23, 59), $duration);
	}

	/**
	 * Parses a string representation of time slots.
	 *
	 * The format is `<earliest>-<latest>/<duration>` (without angled brackets), where
	 * `earliest` and `latest` are in the format `HH:mm` and `duration` is in the format `PTnHnM`.
	 */
	public static function parse(string $timeslots): self
	{
		$parts = explode('-', $timeslots, 2);

		if (count($parts) !== 2) {
			throw new \InvalidArgumentException('Invalid time slots format.');
		}

		[$latest, $duration] = explode('/', $parts[1], 2);

		return new self(LocalTime::parse($parts[0]), LocalTime::parse($latest), Duration::parse($duration));
	}

	public static function typicalWorkDay(): self
	{
		return new self(LocalTime::of(9, 0), LocalTime::of(17, 0), Duration::ofMinutes(15));
	}

	public function contains(LocalTime $time): bool
	{
		foreach ($this as $slot) {
			if ($slot->equals($time)) {
				return true;
			}
		}

		return false;
	}

	public function getIterator(): \Generator
	{
		$time = $this->earliest;

		while ($time->isBeforeOrEqualTo($this->latest)) {
			yield $time;

			$time = $time->plusDuration($this->duration);
		}
	}

	public function __toArray(): array
	{
		return iterator_to_array($this);
	}

	public function count(): int
	{
		return count($this->__toArray());
	}
}
