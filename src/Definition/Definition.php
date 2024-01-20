<?php
namespace Rdb\Definition;

use Cocur\Slugify\Slugify;

use Rdb\Grading\Scale;

class Definition
{
	public function __construct(
		protected ?int $id = null,
		protected ?string $name = null,
		protected ?string $slug = null,
		protected array $fields = [],
		protected ?Scale $scale = null
	) {}

	public function id(?int $id = null): null|int|Definition
	{
		if ($id === null)
			return $this->id;
		$this->id = $id;
		return $this;
	}

	public function name(?string $name = null): null|string|Definition
	{
		if ($name === null)
			return $this->name;
		$this->name = trim($name);
		return $this->slug((new Slugify)->slugify($this->name));
	}

	public function slug(?string $slug = null): null|string|Definition
	{
		if ($slug === null)
			return $this->slug;
		$this->slug = trim($slug);
		return $this;
	}

	public function scale(?Scale $scale = null): null|Scale|Definition
	{
		if ($scale === null)
			return $this->scale;
		$this->scale = $scale;
		return $this;
	}

	public function fields(?array $fields = null): null|array|Definition
	{
		if ($fields === null)
			return $this->fields;

		foreach ($fields as $field)
			$field->def($this);

		$this->fields = $fields;
		return $this;
	}

	public function addField(Field $field): Definition
	{
		$field->def($this);
		$this->fields[] = $field;
		return $this;
	}

	public function __toString(): string
	{
		$fields = implode(', ', array_map(fn($elem) => $elem->name() . '(' . $elem->type()->value . ')', $this->fields));
		return "Definition(id: {$this->id}, name: {$this->name}, fields: [{$fields}])";
	}
}
