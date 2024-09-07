<?php
declare(strict_types=1);

namespace Meraki\Html\Calendar;

use Brick\DateTime\Duration;
use Brick\DateTime\LocalDate;
use Brick\DateTime\LocalTime;
use Brick\DateTime\ZonedDateTime;

/**
 * Represents an event in a calendar.
 *
 * @property-read string $self This is the URL or identifier to the event.
 * @property-read string $title The title of the event.
 * @property-read ZonedDateTime $when The date and time the event starts and the time zone it is in.
 * @property-read Duration $duration How long the event goes for
 * @property-read string $organiser The person or group that organised the event. They do not have to be attending.
 * @property-read string $location The location of the event. Can be a physical address, zoom link, etc.
 * @property-read string $description More detailed information about the event.
 * @property-read object[] $attendees A list of people who are attending the event.
 */
class Event
{
	public string $location = '';
	public string $description = '';
	public array $attendees = [];

	public function __construct(
		public string $self,
		public string $title,
		public ZonedDateTime $when,
		public Duration $duration,
		public ?object $organiser = null,
	) {
	}

	public function describe(string $description): self
	{
		$this->description = $description;

		return $this;
	}

	public function attendAt(string $location): self
	{
		$this->location = $location;

		return $this;
	}

	public function invite(object ...$attendees): self
	{
		$this->attendees = array_merge($this->attendees, $attendees);

		return $this;
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
		return $this->title;
	}
}
