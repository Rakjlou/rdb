<?php
namespace Rdb\Repository;

use Psr\Container\ContainerInterface;

use Cocur\Slugify\Slugify;

use Rdb\Db\DatabaseInterface;
use Rdb\Definition\Definition;
use Rdb\Definition\Field;
use Rdb\Definition\FieldType;
use \PDO;

class DefinitionRepository
{
	const DEFINITION_TABLE = 'Definition';
	const FIELD_DEFINITION_TABLE = 'DefinitionField';

	public function __construct(
		protected DatabaseInterface $db,
		protected ContainerInterface $container
	) {}

	public function save(Definition $definition): Definition
	{
		if ($definition->id() !== null || $definition->name() === null)
			throw new \Exception(self::class . '::save Attempting to use save() on invalid entity');

		$pdo = $this->db->getPdo();
		$table = self::DEFINITION_TABLE;

		// Saving Definition
		$stmt = $pdo->prepare("INSERT INTO `$table` (`name`, `slug`, `scale_id`) VALUES (:name, :slug, :scale)");
		$stmt->bindValue(':name', $definition->name(), PDO::PARAM_STR);
		$stmt->bindValue(':slug', $definition->slug() ?? (new Slugify())->slugify($definition->name()), PDO::PARAM_STR);
		$stmt->bindValue(':scale', $definition->scale()->id(), PDO::PARAM_INT);
		$stmt->execute();

		// Saving DefinitionField
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

	public function update(Definition $definition): Definition
	{
		if ($definition->id() === null || $definition->name() === null)
			throw new \Exception(self::class . '::update Attempting to use update() on invalid entity');

		$pdo = $this->db->getPdo();
		$table = self::DEFINITION_TABLE;
		$reference = $this->findById($definition->id());
		$stmt = $pdo->prepare("UPDATE `$table` SET `name` = :name, `slug` = :slug, `scale_id` = :scale WHERE `id` = :id");
		$stmt->bindValue(':id', $definition->id(), PDO::PARAM_INT);
		$stmt->bindValue(':name', $definition->name(), PDO::PARAM_STR);
		$stmt->bindValue(':slug', $definition->slug(), PDO::PARAM_STR);
		$stmt->bindValue(':scale', $definition->scale()->id(), PDO::PARAM_INT);
		$stmt->execute();

		// Saving DefinitionField
		$fields = $definition->fields();
		$table = self::FIELD_DEFINITION_TABLE;

		foreach ($fields as $field)
		{
			if ($field->id() === null)
			{
				$stmt = $pdo->prepare("INSERT INTO `$table` (`def_id`, `type`, `name`) VALUES (:defId, :type, :name)");
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

	public function delete(Definition|int $definition): void
	{
		if ($definition instanceof Definition)
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
			$result[] = $this->createFromDbRow($row);
		return $result;
	}

	public function findById(int $id): ?Definition
	{
		$pdo = $this->db->getPdo();
		$table = self::DEFINITION_TABLE;
		$stmt = $pdo->prepare("SELECT * FROM `$table` WHERE id = :id");
		$stmt->bindValue(':id', $id, PDO::PARAM_INT);
		$stmt->execute();

		if ($row = $stmt->fetch())
			return $this->createFromDbRow($row);
		return null;
	}

	public function findBySlug(string $slug): ?Definition
	{
		$pdo = $this->db->getPdo();
		$table = self::DEFINITION_TABLE;
		$stmt = $pdo->prepare("SELECT * FROM `$table` WHERE slug = :slug");
		$stmt->bindValue(':slug', $slug, PDO::PARAM_STR);
		$stmt->execute();

		if ($row = $stmt->fetch())
			return $this->createFromDbRow($row);
		return null;
	}

	public function createFromDbRow(array $row): Definition
	{
		return $this->hydrateFields(new Definition(
			id: $row['id'],
			name: $row['name'],
			slug: $row['slug'],
			scale: $this->container->get('repository')->get('grading')->findById($row['scale_id'])
		));
	}

	public function hydrateFields(Definition $definition): Definition
	{
		if ($definition->id() === null)
			throw new \Exception(self::class . '::hydrateFields Attempting to hydrate fields on invalid entity');

		$table = self::FIELD_DEFINITION_TABLE;
		$stmt = $this->db->getPdo()->prepare("SELECT * FROM `$table` WHERE def_id = :id");
		$stmt->bindValue(':id', $definition->id(), PDO::PARAM_INT);
		$stmt->execute();

		while ($row = $stmt->fetch())
		{
			$definition->addField(new Field(
				def: $definition,
				id: $row['id'],
				name: $row['name'],
				type: FieldType::from($row['type'])
			));
		}

		return $definition;
	}
}
