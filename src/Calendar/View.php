<?php
declare(strict_types=1);

namespace Meraki\Html\Calendar;

use Meraki\Html\Element;
use Meraki\Html\Calendar;

/**
 * @property-read \Brick\DateTime\Period $period
 */
abstract class View extends Element // extends Widget
{
	abstract public function connect(Calendar $calendar): void;
	// abstract public function build(Calendar $calendar): self;
}
