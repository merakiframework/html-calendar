<?php
declare(strict_types=1);

namespace Meraki\Html\Calendar;

use Brick\DateTime\ZonedDateTime;
use Brick\DateTime\LocalDate;

/**
 * @property-read string $id
 * @property-read string $name
 * @property-read bool $selected
 * @property-read string $colour
 */
interface Source extends \IteratorAggregate, \Countable
{
	public function getAllEventsBetween(ZonedDateTime $from, ZonedDateTime $to): array;
	public function equals(Source $source): bool;
	public function select(): self;
	public function deselect(): self;
	public function getAllEventsFor(ZonedDateTime $date): array;

	/**
	 * Retrieve all events that occur on a specific date.
	 *
	 * The date is assumed to be in the same time zone as the source.
	 */
	public function getAllEventsOn(LocalDate $date): array;
}
