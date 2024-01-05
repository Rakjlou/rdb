<?php
namespace Rdb\Reviewable;

class DefinitionField
{
	/*
			INSERT OR IGNORE INTO ReviewableFieldDef (def_id, type, name) VALUES
  (:defId, 'text', 'imdb'),
  (:defId, 'text', 'director'),
  (:defId, 'integer', 'seen')
	*/
	public function __construct(
		protected ?Definition $def = null,
		protected ?int $id = null,
		protected ?string $name = null,
		protected ?DefinitionFieldType $type = null
	) {}

	public function id(?int $id = null): null|int|DefinitionField
	{
		if ($id === null)
			return $this->id;
		$this->id = $id;
		return $this;
	}

	public function def(?Definition $def = null): null|Definition|DefinitionField
	{
		if ($def === null)
			return $this->def;
		$this->def = $def;
		return $this;
	}

	public function name(?string $name = null): null|string|DefinitionField
	{
		if ($name === null)
			return $this->name;
		$this->name = trim($name);
		return $this;
	}

	public function type(null|string|DefinitionFieldType $type = null): null|DefinitionFieldType|DefinitionField
	{
		if ($type === null)
			return $this->type;
		else if ($type instanceof DefinitionFieldType)
			$this->type = $type;
		else
			$this->type = DefinitionFieldType::tryFrom($type);
		return $this;
	}
}
