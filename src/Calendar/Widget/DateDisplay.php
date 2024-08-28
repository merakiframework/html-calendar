<?php
declare(strict_types=1);

namespace Meraki\Html\Calendar\Widget;

use Meraki\Html\Calendar\Widget;
use Meraki\Html\Calendar;

/**
 * The DateDisplay widget displays the date range of the currently selected view.
 *
 * For example, if the selected view is a week view, the DateDisplay widget will
 * display the date range of the week, starting from the first day of the week to
 * the last day of the week.
 */
final class DateDisplay extends Widget
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
		$selected = $calendar->currentDateRange;

		// handle single day cases
		if ($selected->count() === 1) {
			$this->appendContent($calendar->prettifyDate($selected->getStart()));
			return $this;
		}

		$this->appendContent($calendar->prettifyDate($selected->getStart()) . ' - ' . $calendar->prettifyDate($selected->getEnd()));

		return $this;
	}
}
