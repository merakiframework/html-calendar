<?php
declare(strict_types=1);

namespace Meraki\Html\Calendar;

use Brick\DateTime\Duration;
use Brick\DateTime\LocalDate;
use Brick\DateTime\LocalTime;
use Brick\DateTime\ZonedDateTime;

final class Event
{
	public function __construct(
		public string $id,
		public string $name,
		public ZonedDateTime $when,
		public Duration $duration,
	) {
	}

	public function occursBetween(ZonedDateTime $from, ZonedDateTime $to): bool
	{
		$eventEnd = $this->when->plusDuration($this->duration);

		return $this->when->isAfterOrEqualTo($from) && $eventEnd->isBeforeOrEqualTo($to);
	}

	/**
	 * Checks if the event occurs on the given date.
	 */
	public function occursOn(LocalDate $date): bool
	{
		$eventEnd = $this->when->plusDuration($this->duration);

		return $this->when->getDate()->isAfterOrEqualTo($date) && $eventEnd->getDate()->isBeforeOrEqualTo($date);
	}

	/**
	 * Checks if the event occurs at the given time.
	 */
	public function occursAt(LocalTime $time): bool
	{
		$eventEnd = $this->when->plusDuration($this->duration);

		return $this->when->getTime()->isBeforeOrEqualTo($time) && $eventEnd->getTime()->isAfterOrEqualTo($time);
	}

	public function __toString(): string
	{
		return $this->name;
	}
}
