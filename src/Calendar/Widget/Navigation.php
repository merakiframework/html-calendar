<?php
declare(strict_types=1);

namespace Meraki\Html\Calendar\Widget;

use Meraki\Html\Calendar\Widget;
use Meraki\Html\Calendar;
use Meraki\Html\Element;
use Meraki\Html\Attribute;

/**
 * The Navigation widget displays buttons to navigate through the previous, current, and next date ranges.
 *
 * For example, if the selected view is a week view, the Navigation widget will display buttons to navigate
 * to the previous week, the current week, and the next week.
 */
final class Navigation extends Widget
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
		$this->appendContent(
			$this->createPreviousButton($calendar),
			$this->createCurrentButton($calendar),
			$this->createNextButton($calendar),
		);

		return $this;
	}

	private function createPreviousButton(Calendar $calendar): Element
	{
		$previousPeriod = $calendar->getDateRangeThatIncludes($calendar->selectedDate->minusPeriod($calendar->period));

		$attrs = new Attribute\Set();
		$attrs->set(new Attribute\Class_('previous'));
		$attrs->set(new Attribute\Href((string)$calendar->url->withDate($calendar->selectedDate->minusPeriod($calendar->period))));
		$attrs->set(new Attribute\Title($calendar->prettifyDate($previousPeriod->getStart(), 'j F Y') . ' - ' . $calendar->prettifyDate($previousPeriod->getEnd(), 'j F Y')));

		$previous = new Element('a', $attrs);
		$previous->setContent('&lt;');

		return $previous;
	}

	private function createCurrentButton(Calendar $calendar): Element
	{
		$attrs = new Attribute\Set();
		$attrs->set(new Attribute\Class_('current'));
		$attrs->set(new Attribute\Href((string)$calendar->url->withDate($calendar->currentDate)));

		$content = '';

		if ($calendar->currentDateRange->count() === 1) {
			$content = 'Today';
		} elseif ($calendar->currentDateRange->count() === 7) {
			$content = 'This Week';
		} else {
			$content = $calendar->prettifyDate($calendar->currentDateRange->getStart(), 'j F Y') . ' - ' . $calendar->prettifyDate($calendar->currentDateRange->getEnd(), 'j F Y');
		}

		$current = new Element('a', $attrs);
		$current->setContent($content);

		return $current;
	}

	private function createNextButton(Calendar $calendar): Element
	{
		$nextPeriod = $calendar->getDateRangeThatIncludes($calendar->selectedDate->plusPeriod($calendar->period));

		$attrs = new Attribute\Set();
		$attrs->set(new Attribute\Class_('next'));
		$attrs->set(new Attribute\Href((string)$calendar->url->withDate($calendar->selectedDate->plusPeriod($calendar->period))));
		$attrs->set(new Attribute\Title($calendar->prettifyDate($nextPeriod->getStart(), 'j F Y') . ' - ' . $calendar->prettifyDate($nextPeriod->getEnd(), 'j F Y')));

		$next = new Element('a', $attrs);
		$next->setContent('&gt;');

		return $next;
	}
}
