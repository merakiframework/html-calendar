<?php
declare(strict_types=1);

namespace Meraki\Html;

use Brick\DateTime\DefaultClock;
use Meraki\Html\Calendar;
use Meraki\TestSuite\TestCase;

/**
 * @covers Calendar
 */
final class CalendarTest extends TestCase
{
	/**
	 * @test
	 */
	public function is_an_element(): void
	{
		$clock = DefaultClock::get();
		$sources = new Calendar\Source\Set();
		$calendar = new Calendar($clock, $sources);

		$this->assertInstanceOf(Element::class, $calendar);
	}
}
