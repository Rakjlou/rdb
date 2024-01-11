<?php
namespace Rdb\Grading;

class Scale
{
	public function __construct(
		protected ?int $id = null,
		protected ?string $name = null,
		protected array $criterias = []
	) {}

	public function id(?int $id = null): null|int|Scale
	{
		if ($id === null)
			return $this->id;
		$this->id = $id;
		return $this;
	}

	public function name(?string $name = null): null|string|Scale
	{
		if ($name === null)
			return $this->name;

		$trimmed = trim($name);
		$this->name = empty($trimmed) ? null : $trimmed;
		return $this;
	}

	public function criterias(?array $criterias = null): null|array|Scale
	{
		if ($criterias === null)
			return $this->criterias;

		foreach ($criterias as $criteria)
			$criteria->scale($this);

		$this->criterias = $criterias;
		return $this;
	}

	public function addCriteria(Criteria $criteria): Scale
	{
		$criteria->scale($this);
		$this->criterias[] = $criteria;
		return $this;
	}
}
