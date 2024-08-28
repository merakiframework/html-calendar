<?php
declare(strict_types=1);

namespace Meraki\Html\Calendar;

use Meraki\Html\Element;
use Meraki\Html\Calendar;

abstract class Widget extends Element
{
	/**
	 * Connect the widget to the calendar.
	 *
	 * Usually, this is where the widget would listen for events
	 * and set up any necessary state.
	 */
	abstract public function connect(Calendar $calendar): void;

	/**
	 * Build the widget.
	 *
	 * This is where the widget would create its HTML structure.
	 */
	abstract public function build(Calendar $calendar): self;
}
