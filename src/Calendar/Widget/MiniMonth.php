<?php
declare(strict_types=1);

namespace Meraki\Html\Calendar\Widget;

use Brick\DateTime\Month;
use Brick\DateTime\LocalDate;
use Brick\DateTime\DayOfWeek;
use Brick\DateTime\ZonedDateTime;
use Meraki\Html\Calendar\Widget;
use Meraki\Html\Calendar;
use Meraki\Html\Element;
use Meraki\Html\Attribute;

/**
 * A widget that displays a mini month view of the calendar.
 *
 * Each date in the mini month view is a button that the user can click to navigate to that date.
 * The mini month view is useful for quickly navigating to a specific date and always shows the current month.
 */
final class MiniMonth extends Widget
{
	public function __construct()
	{
		parent::__construct('div');
	}

	public function connect(Calendar $calendar): void
	{
		return;
	}

	public function build(Calendar $calendar): self
	{
		/** @var ZonedDateTime $currentDateTime */
		$currentDateTime = $calendar->now;
		/** @var DayOfWeek $weekStartsOn */
		$weekStartsOn = $calendar->weekStartsOn;
		/** @var LocalDate $currentYear */
		$currentDate = $currentDateTime->getDate();
		/** @var Month $currentMonth */
		$currentMonth = $currentDateTime->getMonth();
		$daysInMonth = $currentMonth->getLength($currentDate->isLeapYear());
		$currentDay = $currentDateTime->getDayOfMonth();
		$firstDayOfMonth = $currentDateTime->minusDays($currentDay - 1);
		$lastDayOfMonth = $currentDateTime->plusDays($daysInMonth - $currentDay);
		/** @var int $leadingDays */
		$leadingDays = $firstDayOfMonth->getDayOfWeek()->value - $weekStartsOn->value;
		/** @var int $trailingDays */
		$trailingDays = 7 - $lastDayOfMonth->getDayOfWeek()->value;
		$periodStart = $firstDayOfMonth->minusDays($leadingDays);
		$periodEnd = $lastDayOfMonth->plusDays($trailingDays);

		$monthYear = $this->buildYearMonth($currentDate);
		$dayNames = $this->buildDayNames($weekStartsOn);
		$days = $this->buildDays($periodStart, $periodEnd, $currentDateTime, $calendar);

		$this->setContent($monthYear, $dayNames, $days);

		return $this;
	}

	private function buildDays(ZonedDateTime $periodStart, ZonedDateTime $periodEnd, ZonedDateTime $currentDate, Calendar $calendar): Element
	{
		$days = new Element('div');
		$days->attributes->add(new Attribute\Class_('days'));

		while ($periodStart->isBeforeOrEqualTo($periodEnd)) {
			/** @var ZonedDateTime $date */
			$date = $periodStart;
			$day = new Element('time');
			$day->attributes->add(new Attribute\Class_('day'));
			$day->attributes->add(new Attribute('datetime', $date->getDate()->__tostring()));
			$day->attributes->add(new Attribute\Data('day', (string)$date->getDayOfWeek()->value));

			if ($date->getMonth() === $currentDate->getMonth()) {
				$day->attributes->get(Attribute\Class_::class)->add('current-month');
			} elseif ($date->getMonth() === $currentDate->minusMonths(1)->getMonth()) {
				$day->attributes->get(Attribute\Class_::class)->add('previous-month');
			} else {
				$day->attributes->get(Attribute\Class_::class)->add('next-month');
			}

			if ($date->getDate()->isEqualTo($currentDate->getDate())) {
				$day->attributes->add(new Attribute\Data('today', ''));
			}

			if ($date->getDayOfWeek()->isWeekend()) {
				$day->attributes->add(new Attribute\Data('weekend', ''));
			}

			// add week
			$day->attributes->add(new Attribute\Data('week', (string)$date->getDate()->getYearWeek()->getWeek()));

			$url = $calendar->url->withDate($date->getDate());
			$url = $url->withView('day');

			$link = new Element('a');
			$link->attributes->add(new Attribute\Href((string)$url));
			$link->setContent((string)$date->getDayOfMonth());

			// $span = new Element('span');
			// $span->setContent((string)$date->getDayOfMonth());

			$day->setContent($link);
			$days->appendContent($day);
			$periodStart = $periodStart->plusDays(1);
		}

		return $days;
	}

	private function buildDayNames(DayOfWeek $weekStartsOn): Element
	{
		$dayNames = new Element('ol');
		$dayNames->attributes->add(new Attribute\Class_('day-names'));

		for ($i = 1; $i <= 7; $i++) {
			$day = new Element('li');
			// $weekday->attributes->add(new Attribute\Class_('weekday'));
			$day->attributes->add(new Attribute\Data('day', (string)$weekStartsOn->value));

			if ($weekStartsOn->isWeekend()) {
				$day->attributes->add(new Attribute\Data('weekend', ''));
			}

			$abbr = new Element('abbr');
			$abbr->attributes->add(new Attribute\Title(ucfirst(strtolower($weekStartsOn->name))));
			$abbr->setContent(substr(ucfirst(strtolower($weekStartsOn->name)), 0, 2));

			$day->setContent($abbr);
			$dayNames->appendContent($day);
			$weekStartsOn = $weekStartsOn->plus(1);
		}

		return $dayNames;
	}

	private function buildYearMonth(LocalDate $currentDate): Element
	{
		$header = new Element('time');
		$header->attributes->add(new Attribute\Class_('year-month'));
		$header->attributes->add(new Attribute('datetime', $currentDate->getYear() . '-' . str_pad((string)$currentDate->getMonthValue(), 2, '0', \STR_PAD_LEFT)));

		$year = new Element('span');
		$year->attributes->add(new Attribute\Class_('year'));
		$year->setContent((string) $currentDate->getYear());

		$month = new Element('span');
		$month->attributes->add(new Attribute\Class_('month'));
		$month->setContent($currentDate->getMonth()->name);

		$header->setContent($year, $month);

		return $header;
	}
}
