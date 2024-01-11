<?php
namespace Rdb\Grading;

class Criteria
{
	public function __construct(
		protected ?Scale $scale = null,
		protected ?int $id = null,
		protected ?string $name = null,
		protected ?int $min = null,
		protected ?int $max = null,
	) {}

	public function id(?int $id = null): null|int|Criteria
	{
		if ($id === null)
			return $this->id;
		$this->id = $id;
		return $this;
	}

	public function scale(?Scale $scale = null): null|Scale|Criteria
	{
		if ($scale === null)
			return $this->scale;
		$this->scale = $scale;
		return $this;
	}

	public function name(?string $name = null): null|string|Criteria
	{
		if ($name === null)
			return $this->name;

		$trimmed = trim($name);
		$this->name = empty($trimmed) ? null : $trimmed;
		return $this;
	}

	public function min(?int $min = null): null|int|Criteria
	{
		if ($min === null)
			return $this->min;
		$this->min = $min;
		return $this;
	}

	public function max(?int $max = null): null|int|Criteria
	{
		if ($max === null)
			return $this->max;
		$this->max = $max;
		return $this;
	}
}
