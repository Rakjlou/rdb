<?php
namespace Rdb\Reviewable\Repository;

use Rdb\Db\DatabaseInterface;
use Rdb\Reviewable\Definition as ReviewableDefinition;
use Rdb\Reviewable\DefinitionField as ReviewableDefinitionField;
use Rdb\Reviewable\DefinitionFieldType as ReviewableDefinitionFieldType;
use \PDO;

class Definition
{
	const DEFINITION_TABLE = 'ReviewableDef';
	const FIELD_DEFINITION_TABLE = 'ReviewableFieldDef';

	public function __construct(
		protected DatabaseInterface $db
	) {}

	public function save(ReviewableDefinition $definition): ReviewableDefinition
	{
		if ($definition->id() !== null || $definition->name() === null)
			throw new \Exception(self::class . '::save Attempting to use save() on invalid entity');

		$pdo = $this->db->getPdo();
		$table = self::DEFINITION_TABLE;

		// Saving ReviewableDef
		$stmt = $pdo->prepare("INSERT INTO `$table` (`name`) VALUES (:name)");
		$stmt->bindValue(':name', $definition->name(), PDO::PARAM_STR);
		$stmt->execute();

		// Saving ReviewableFieldDef
		$definition->id($pdo->lastInsertId());
		$fields = $definition->fields();
		$table = self::FIELD_DEFINITION_TABLE;

		foreach ($fields as $field)
		{
			$stmt = $pdo->prepare("INSERT INTO `$table` (def_id, type, name) VALUES (:defId, :type, :name)");
			$stmt->bindValue(':defId', $definition->id(), PDO::PARAM_INT);
			$stmt->bindValue(':type', $field->type()->value, PDO::PARAM_STR);
			$stmt->bindValue(':name', $field->name(), PDO::PARAM_STR);
			$stmt->execute();
			$field->id($pdo->lastInsertId());
		}

		return $definition;
	}

	public function update(ReviewableDefinition $definition): ReviewableDefinition
	{
		if ($definition->id() === null || $definition->name() === null)
			throw new \Exception(self::class . '::update Attempting to use update() on invalid entity');

		$pdo = $this->db->getPdo();
		$table = self::DEFINITION_TABLE;
		$reference = $this->findById($definition->id());
		$stmt = $pdo->prepare("UPDATE `$table` SET `name` = :name WHERE `id` = :id");
		$stmt->bindValue(':id', $definition->id(), PDO::PARAM_INT);
		$stmt->bindValue(':name', $definition->name(), PDO::PARAM_STR);
		$stmt->execute();

		// Saving ReviewableFieldDef
		$fields = $definition->fields();
		$table = self::FIELD_DEFINITION_TABLE;

		foreach ($fields as $field)
		{
			if ($field->id() === null)
			{
				$stmt = $pdo->prepare("INSERT INTO `$table` (def_id, type, name) VALUES (:defId, :type, :name)");
				$stmt->bindValue(':defId', $definition->id(), PDO::PARAM_INT);
				$stmt->bindValue(':type', $field->type()->value, PDO::PARAM_STR);
				$stmt->bindValue(':name', $field->name(), PDO::PARAM_STR);
				$stmt->execute();
				$field->id($pdo->lastInsertId());
			}
			else
			{
				$stmt = $pdo->prepare("UPDATE `$table` SET `name` = :name, `type` = :type WHERE `id` = :id");
				$stmt->bindValue(':id', $field->id(), PDO::PARAM_INT);
				$stmt->bindValue(':name', $field->name(), PDO::PARAM_STR);
				$stmt->bindValue(':type', $field->type()->value, PDO::PARAM_STR);
				$stmt->execute();
			}
		}

		$fieldIdsToDelete = array_values(array_diff(
			array_map(fn($elem) => $elem->id(), $reference->fields()),
			array_map(fn($elem) => $elem->id(), $fields)
		));

		
		$placeholders = implode(',', array_fill(0, count($fieldIdsToDelete), '?'));
		$stmt = $pdo->prepare("DELETE FROM `$table` WHERE `id` IN ($placeholders)");
		$stmt->execute($fieldIdsToDelete);
		return $definition;
	}


	public function delete(ReviewableDefinition|int $definition): void
	{
		if ($definition instanceof ReviewableDefinition)
			$id = $definition->id();
		else if (is_int($definition) && $definition > 0)
			$id = $definition;
		else
			throw new \Exception(self::class . '::delete Attempting to use delete() on invalid entity ' . $definition);

		$table = self::DEFINITION_TABLE;
		$stmt = $this->db->getPdo()->prepare("DELETE FROM `$table` WHERE id = :id");
		$stmt->bindValue(':id', $id, PDO::PARAM_INT);
		$stmt->execute();
	}

	public function findAll(): array
	{
		$result = [];
		$pdo = $this->db->getPdo();
		$table = self::DEFINITION_TABLE;
		$stmt = $pdo->prepare("SELECT * FROM `$table`");
		$stmt->execute();

		while ($row = $stmt->fetch())
			$result[] = $this->hydrateFields(new ReviewableDefinition(id: $row['id'], name: $row['name']));
		return $result;
	}

	public function findById(int $id): ?ReviewableDefinition
	{
		$pdo = $this->db->getPdo();
		$table = self::DEFINITION_TABLE;
		$stmt = $pdo->prepare("SELECT * FROM `$table` WHERE id = :id");
		$stmt->bindValue(':id', $id, PDO::PARAM_INT);
		$stmt->execute();

		if ($row = $stmt->fetch())
			return $this->hydrateFields(new ReviewableDefinition(id: $row['id'], name: $row['name']));

		return null;
	}

	public function hydrateFields(ReviewableDefinition $definition): ReviewableDefinition
	{
		if ($definition->id() === null)
			throw new \Exception(self::class . '::hydrateFields Attempting to hydrate fields on invalid entity');

		$table = self::FIELD_DEFINITION_TABLE;
		$stmt = $this->db->getPdo()->prepare("SELECT * FROM `$table` WHERE def_id = :id");
		$stmt->bindValue(':id', $definition->id(), PDO::PARAM_INT);
		$stmt->execute();

		while ($row = $stmt->fetch())
		{
			$definition->addField(new ReviewableDefinitionField(
				def: $definition,
				id: $row['id'],
				name: $row['name'],
				type: ReviewableDefinitionFieldType::from($row['type'])
			));
		}

		return $definition;
	}
}
