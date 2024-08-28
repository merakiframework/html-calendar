<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;
use Meraki\Html\Attribute\Data;

final class Locale extends Attribute
{
	public function __construct(string $value)
	{
		parent::__construct('data-locale', $value);
	}
}
