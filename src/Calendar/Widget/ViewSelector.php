<?php
declare(strict_types=1);

namespace Meraki\Html\Calendar\Widget;

use Meraki\Html\Calendar\Widget;
use Meraki\Html\Calendar;
use Meraki\Html\Element;
use Meraki\Html\Attribute;

/**
 * The ViewSelector widget allows the user to select which view to display on the calendar.
 *
 * For example, if the calendar is displaying a week view, the ViewSelector widget will allow
 * the user to select a different view, such as a day view or a month view.
 */
final class ViewSelector extends Widget
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
		$buttonGroup = new Element('div');
		$buttonGroup->attributes
			->findOrCreate(Attribute\Class_::class, fn() => new Attribute\Class_())
			->add('button-group');

		foreach ($calendar::SUPPORTED_VIEWS as $view) {
			$button = new Element('a');
			$button->attributes->set(new Attribute\Href((string)$calendar->url->withView($view)));
			$button->setContent($this->prettifyViewName($view));
			$buttonGroup->appendContent($button);
		}

		$this->appendContent($buttonGroup);

		return $this;
	}

	private function prettifyViewName(string $viewName): string
	{
		$parts = explode('-', $viewName);
		$parts = array_map(fn(string $part): string => ucfirst($part), $parts);

		return implode(' ', $parts);
	}
}
