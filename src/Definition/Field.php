<?php
namespace Rdb\Definition;

class Field
{
	public function __construct(
		protected ?Definition $def = null,
		protected ?int $id = null,
		protected ?string $name = null,
		protected ?FieldType $type = null
	) {}

	public function id(?int $id = null): null|int|Field
	{
		if ($id === null)
			return $this->id;
		$this->id = $id;
		return $this;
	}

	public function def(?Definition $def = null): null|Definition|Field
	{
		if ($def === null)
			return $this->def;
		$this->def = $def;
		return $this;
	}

	public function name(?string $name = null): null|string|Field
	{
		if ($name === null)
			return $this->name;
		$this->name = trim($name);
		return $this;
	}

	public function type(null|string|FieldType $type = null): null|FieldType|Field
	{
		if ($type === null)
			return $this->type;
		else if ($type instanceof FieldType)
			$this->type = $type;
		else
			$this->type = FieldType::tryFrom($type);
		return $this;
	}
}
