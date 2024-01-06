<?php
namespace Rdb\Reviewable;

class Definition
{
	public function __construct(
		protected ?int $id = null,
		protected ?string $name = null,
		protected array $fields = []
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

	public function addField(DefinitionField $field): Definition
	{
		$field->def($this);
		$this->fields[] = $field;
		return $this;
	}

	public function removeField(DefinitionField|int $field)
	{
		if ($field instanceof DefinitionField && $field->name() !== null)
			$this->fields(array_filter($this->fields, fn($elem) => $elem->name() !== $field->name()));
		else if ($field instanceof int)
			$this->fields(array_filter($this->fields, fn($elem) => $elem->id() !== $field));
		else
			error_log(self::class . "::removeField ignoring invalid parameter '{$field}'");
	}

	public function __toString(): string
	{
		$fields = implode(', ', array_map(fn($elem) => $elem->name() . '(' . $elem->type()->value . ')', $this->fields));
		return "Definition(id: {$this->id}, name: {$this->name}, fields: [{$fields}])";
	}
}
