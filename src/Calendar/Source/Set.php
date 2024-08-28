<?php
declare(strict_types=1);

namespace Meraki\Html\Calendar\Source;

use Meraki\Html\Calendar\Source;

final class Set implements \IteratorAggregate, \Countable
{
	private array $sources = [];

	public function __construct(Source ...$sources)
	{
		array_map([$this, 'mutableAdd'], $sources);
	}

	public function add(Source $source, Source ...$sources): self
	{
		$copy = clone $this;
		$copy->mutableAdd($source, ...$sources);

		return $copy;
	}

	public function getByName(string $name): Source
	{
		$source = $this->find(fn(Source $source): bool => $source->name === $name);

		if ($source !== null) {
			return $source;
		}

		throw new \InvalidArgumentException('Source not found in list.');
	}

	public function getById(string $id, string ...$ids): self
	{
		return $this->filter(fn(Source $source): bool => $source->id === $id || in_array($source->id, $ids, true));
	}

	protected function mutableAdd(Source $source, Source ...$sources): void
	{
		$sources = array_merge([$source], $sources);

		foreach ($sources as $source) {
			if (!$this->contains($source)) {
				$this->sources[] = $source;
			}
		}
	}

	public function first(): ?Source
	{
		return $this->sources[0] ?? null;
	}

	public function last(): ?Source
	{
		return $this->sources[count($this->sources) - 1] ?? null;
	}

	public function select(string $id, string ...$ids): self
	{
		foreach ($this->sources as $source) {
			if ($source->id === $id || in_array($source->id, $ids, true)) {
				$source->select();
			} else {
				$source->deselect();
			}
		}

		return $this;
	}

	public function contains(Source $source): bool
	{
		foreach ($this->sources as $s) {
			if ($s->equals($source)) {
				return true;
			}
		}

		return false;
	}

	public function remove(Source $source): self
	{
		return $this->filter(fn(Source $s): bool => $s !== $source);
	}

	public function find(callable|Source $predicate): self
	{
		if ($predicate instanceof Source) {
			$predicate = fn(Source $source): bool => $source === $predicate;
		}

		return $this->filter($predicate);
	}

	public function filter(callable $predicate): self
	{
		$sources = array_filter($this->sources, $predicate);

		return new self(...$sources);
	}

	public function map(callable $transform): self
	{
		$sources = array_map($transform, $this->sources);

		return new self(...$sources);
	}

	public function get(Source $source): Source
	{
		$found = $this->find($source);

		if (($first = $found->first()) !== null) {
			return $first;
		}

		throw new \InvalidArgumentException('Source not found in list.');
	}

	public function selectAll(): self
	{
		foreach ($this->sources as $source) {
			$source->select();
		}

		return $this;
	}

	public function deselectAll(): self
	{
		foreach ($this->sources as $source) {
			$source->deselect();
		}

		return $this;
	}

	public function getSelected(): self
	{
		return $this->filter(fn(Source $source): bool => $source->selected);
	}

	public function getUnselected(): self
	{
		return $this->filter(fn(Source $source): bool => !$source->selected);
	}

	public function getIterator(): \Traversable
	{
		return new \ArrayIterator($this->sources);
	}

	public function count(): int
	{
		return count($this->sources);
	}

	public function __clone()
	{
		$this->sources = array_map(fn(Source $source): Source => clone $source, $this->sources);
	}
}
