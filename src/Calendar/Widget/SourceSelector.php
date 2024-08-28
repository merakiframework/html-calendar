<?php
declare(strict_types=1);

namespace Meraki\Html\Calendar\Widget;

use Meraki\Html\Calendar\Widget;
use Meraki\Html\Calendar;
use Meraki\Html\Element;
use Meraki\Html\Attribute;

/**
 * A widget that allows the user to select which sources to display on the calendar.
 *
 * For example, if the calendar is displaying events from multiple sources, the
 * SourceSelector widget will allow the user to select which sources to display.
 */
final class SourceSelector extends Widget
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
		$ul = new Element('ul');

		$form = new Element('form');
		$form->attributes->set(new Attribute\Method('get'));
		$form->attributes->set(new Attribute\Action((string)$calendar->url));

		$sourceUpdateButton = new Element('li');
		$input = new Element('input');
		$input->attributes->set(new Attribute\Type('submit'));
		$input->attributes->set(new Attribute\Value('Update Sources'));

		$sourceUpdateButton->appendContent($input);
		$form->appendContent($ul);

		/** @var Calendar\Source $source */
		foreach ($calendar->sources as $source) {
			$li = new Element('li');
			$li->attributes->set(new Attribute\Style(['--calendar-source-colour' => $source->colour]));

			$label = new Element('label');
			$label->attributes->set(new Attribute\For_('meraki-calendar-source-'.$source->id));
			$label->appendContent($source->name);

			$input = new Element('input');
			$input->attributes->set(new Attribute\Id('meraki-calendar-source-' .$source->id));
			$input->attributes->set(new Attribute\Type('checkbox'));
			$input->attributes->set(new Attribute\Name('sources'));
			$input->attributes->set(new Attribute\Value($source->id));
			$input->attributes->set(Attribute\Autocomplete::off());

			if ($source->selected) {
				$input->attributes->set(new Attribute\Checked());
			}

			$li->appendContent($input, $label);
			$ul->appendContent($li);
		}

		$ul->appendContent(...$this->createPredefinedQueryParameters($calendar->url));

		$ul->appendContent($sourceUpdateButton);
		$this->appendContent($form);

		return $this;
	}

	private function createPredefinedQueryParameters(Calendar\Url $url): array
	{
		$predefinedQueryParameters = [];

		foreach ($url->getQueryAsArray() as $name => $value) {
			if ($name === 'sources') {
				continue;
			}

			if (is_array($value)) {
				foreach ($value as $singleValue) {
					$predefinedQueryParameters[] = $this->createHiddenInput($name, $singleValue);
				}
			} else {
				$predefinedQueryParameters[] = $this->createHiddenInput($name, $value);
			}
		}

		return $predefinedQueryParameters;
	}

	private function createHiddenInput(string $name, string $value): Element
	{
		$input = new Element('input');
		$input->attributes->set(new Attribute\Type('hidden'));
		$input->attributes->set(new Attribute\Name($name));
		$input->attributes->set(new Attribute\Value($value));

		return $input;
	}
}
