<?php
declare(strict_types=1);

namespace Meraki\Html\Calendar;
use Brick\DateTime\LocalDate;

final class Url
{
	private array $query = [];

	public function __construct(
		private string $path,
		string $query,
	) {
		$this->parseQuery($query);
	}

	public static function fromServer(): self
	{
		$parts = explode('?', $_SERVER['REQUEST_URI'] ?? '', 2);

		return new self($parts[0], $parts[1] ?? '');
	}

	public function getDate(): ?LocalDate
	{
		if (!isset($this->query['date'])) {
			return null;
		}

		return LocalDate::parse($this->query['date']);
	}

	public function getView(): ?string
	{
		if (!isset($this->query['view'])) {
			return null;
		}

		return $this->query['view'];
	}

	public function getSources(): ?array
	{
		if (!isset($this->query['sources'])) {
			return null;
		}

		return (array)$this->query['sources'];
	}

	public function queryGiven(string $key): bool
	{
		return isset($this->query[$key]);
	}

	public function withDate(LocalDate $date): self
	{
		$clone = clone $this;
		$clone->query['date'] = $date->__toString();

		return $clone;
	}

	public function withView(View|string $view): self
	{
		if ($view instanceof View) {
			$view = $view->type;
		}

		$clone = clone $this;
		$clone->query['view'] = $view;

		return $clone;
	}

	public function withSources(Source|string $source): self
	{
		if ($source instanceof Source) {
			$source = $source->id;
		}

		$clone = clone $this;

		if (!isset($clone->query['sources'])) {
			$clone->query['sources'] = [];
		}

		$clone->query['sources'][] = $source;

		return $clone;
	}

	public function getQueryAsString(): string
	{
		return http_build_query($this->query, '', '&', PHP_QUERY_RFC3986);
	}

	public function getQueryAsArray(): array
	{
		return $this->query;
	}

	public function __toString(): string
	{
		if (count($this->query) === 0) {
			return $this->path;
		}

		$url = $this->path . '?' . $this->getQueryAsString();

		return $url;
	}

	private function parseQuery(string $query): void
	{
		if ($query === '') {
			return;
		}

		$result = [];
		$pairs = explode('&', $query);

		foreach ($pairs as $pair) {
			[$name, $value] = explode('=', $pair, 2);

			if (isset($result[$name])) {
				if (is_array($result[$name])) {
					$result[$name][] = $value;
				} else {
					$result[$name] = [$result[$name], $value];
				}
			} else {
				$result[$name] = $value;
			}
		}

		$this->query = $result;
	}
}
