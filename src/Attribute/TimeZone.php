<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;
use Meraki\Html\Attribute\Data;

final class TimeZone extends Attribute
{
	public function __construct(string $value)
	{
		parent::__construct('data-time-zone', $value);
	}
}
