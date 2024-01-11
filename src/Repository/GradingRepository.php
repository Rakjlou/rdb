<?php
namespace Rdb\Repository;

use Psr\Container\ContainerInterface;

use Rdb\Db\DatabaseInterface;
use Rdb\Grading\Scale;
use Rdb\Grading\Criteria;
use \PDO;

class GradingRepository
{
	const SCALE_TABLE = 'GradingScale';
	const CRITERIA_TABLE = 'GradingCriteria';

	public function __construct(
		protected DatabaseInterface $db,
		protected ContainerInterface $container
	) {}

	public function save(Scale $scale): Scale
	{
		if ($scale->id() !== null || $scale->name() === null)
			throw new \Exception(self::class . '::save Attempting to use save() on invalid entity');

		$pdo = $this->db->getPdo();
		$table = self::SCALE_TABLE;

		// Saving Scale
		$stmt = $pdo->prepare("INSERT INTO `$table` (`name`) VALUES (:name)");
		$stmt->bindValue(':name', $scale->name(), PDO::PARAM_STR);
		$stmt->execute();

		// Saving ScaleField
		$scale->id($pdo->lastInsertId());
		$criterias = $scale->criterias();
		$table = self::CRITERIA_TABLE;

		foreach ($criterias as $criteria)
		{
			$stmt = $pdo->prepare("INSERT INTO `$table` (`scale_id`, `name`, `min_value`, `max_value`) VALUES (:scaleId, :name, :min, :max)");
			$stmt->bindValue(':scaleId', $scale->id(), PDO::PARAM_INT);
			$stmt->bindValue(':name', $criteria->name(), PDO::PARAM_STR);
			$stmt->bindValue(':min', $criteria->min(), PDO::PARAM_INT);
			$stmt->bindValue(':max', $criteria->max(), PDO::PARAM_INT);
			$stmt->execute();
			$criteria->id($pdo->lastInsertId());
		}

		return $scale;
	}

	public function update(Scale $scale): Scale
	{
		if ($scale->id() === null || $scale->name() === null)
			throw new \Exception(self::class . '::update Attempting to use update() on invalid entity');

		$pdo = $this->db->getPdo();
		$table = self::SCALE_TABLE;
		$reference = $this->findById($scale->id());
		$stmt = $pdo->prepare("UPDATE `$table` SET `name` = :name WHERE `id` = :id");
		$stmt->bindValue(':id', $scale->id(), PDO::PARAM_INT);
		$stmt->bindValue(':name', $scale->name(), PDO::PARAM_STR);
		$stmt->execute();

		// Saving ScaleField
		$criterias = $scale->criterias();
		$table = self::CRITERIA_TABLE;

		foreach ($criterias as $criteria)
		{
			if ($criteria->id() === null)
			{
				$stmt = $pdo->prepare("INSERT INTO `$table` (`scale_id`, `name`, `min_value`, `max_value`) VALUES (:scale, :name, :min, :max)");
				$stmt->bindValue(':scale', $scale->id(), PDO::PARAM_INT);
				$stmt->bindValue(':name', $criteria->name(), PDO::PARAM_STR);
				$stmt->bindValue(':min', $criteria->min(), PDO::PARAM_INT);
				$stmt->bindValue(':max', $criteria->max(), PDO::PARAM_INT);
				$stmt->execute();
				$criteria->id($pdo->lastInsertId());
			}
			else
			{
				$stmt = $pdo->prepare("UPDATE `$table` SET `scale_id` = :scale, `name` = :name, `min_value` = :min, `max_value` = :max WHERE `id` = :id");
				$stmt->bindValue(':id', $criteria->id(), PDO::PARAM_INT);
				$stmt->bindValue(':scale', $scale->id(), PDO::PARAM_INT);
				$stmt->bindValue(':name', $criteria->name(), PDO::PARAM_STR);
				$stmt->bindValue(':min', $criteria->min(), PDO::PARAM_INT);
				$stmt->bindValue(':max', $criteria->max(), PDO::PARAM_INT);
				$stmt->execute();
			}
		}

		$criteriaIdsToDelete = array_values(array_diff(
			array_map(fn($elem) => $elem->id(), $reference->criterias()),
			array_map(fn($elem) => $elem->id(), $criterias)
		));

		$placeholders = implode(',', array_fill(0, count($criteriaIdsToDelete), '?'));
		$stmt = $pdo->prepare("DELETE FROM `$table` WHERE `id` IN ($placeholders)");
		$stmt->execute($criteriaIdsToDelete);
		return $scale;
	}

	public function delete(Scale|int $scale): void
	{
		if ($scale instanceof Scale)
			$id = $scale->id();
		else if (is_int($scale) && $scale > 0)
			$id = $scale;
		else
			throw new \Exception(self::class . '::delete Attempting to use delete() on invalid entity ' . $scale);

		$table = self::SCALE_TABLE;
		$stmt = $this->db->getPdo()->prepare("DELETE FROM `$table` WHERE id = :id");
		$stmt->bindValue(':id', $id, PDO::PARAM_INT);
		$stmt->execute();
	}

	public function findAll(): array
	{
		$result = [];
		$pdo = $this->db->getPdo();
		$table = self::SCALE_TABLE;
		$stmt = $pdo->prepare("SELECT * FROM `$table`");
		$stmt->execute();

		while ($row = $stmt->fetch())
			$result[] = $this->hydrateCriterias(new Scale(id: $row['id'], name: $row['name']));
		return $result;
	}

	public function findById(int $id): ?Scale
	{
		$pdo = $this->db->getPdo();
		$table = self::SCALE_TABLE;
		$stmt = $pdo->prepare("SELECT * FROM `$table` WHERE id = :id");
		$stmt->bindValue(':id', $id, PDO::PARAM_INT);
		$stmt->execute();

		if ($row = $stmt->fetch())
			return $this->hydrateCriterias(new Scale(id: $row['id'], name: $row['name']));

		return null;
	}

	public function hydrateCriterias(Scale $scale): Scale
	{
		if ($scale->id() === null)
			throw new \Exception(self::class . '::hydrateCriterias Attempting to hydrate criterias on invalid entity');

		$table = self::CRITERIA_TABLE;
		$stmt = $this->db->getPdo()->prepare("SELECT * FROM `$table` WHERE scale_id = :id");
		$stmt->bindValue(':id', $scale->id(), PDO::PARAM_INT);
		$stmt->execute();

		while ($row = $stmt->fetch())
		{
			$scale->addCriteria(new Criteria(
				scale: $scale,
				id: $row['id'],
				name: $row['name'],
				min: $row['min_value'],
				max: $row['max_value'],
			));
		}

		return $scale;
	}
}
