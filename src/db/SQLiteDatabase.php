<?php
namespace Rdb\Db;

use \PDO;
use \PDOException;
use \PDOStatement;

class SQLiteDatabase implements DatabaseInterface
{
	private PDO $db;

	static public function create(array $config = [])
	{
		try {
			return new self(...$config);
		} catch (PDOException $e) {
			throw new PDOException('Error connecting to SQLite database: ' . $e->getMessage());
		}
	}

	public function __construct(string $file)
	{
		try {
			$this->db = new PDO('sqlite:' . $file);
			$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->db->exec('PRAGMA foreign_keys = ON;');
		} catch (PDOException $e) {
			throw new PDOException('Error connecting to SQLite database: ' . $e->getMessage());
		}
	}

	public function disconnect(): void
	{
		$this->db = null;
	}

	public function executeQuery(string $query, array $params = []): PDOStatement
	{
		$stmt = $this->db->prepare($query);

		if (!empty($params))
		{
			foreach ($params as $key => $value)
			{
				$type = $this->getPdoTypeFromVariable($value);

				if ($type === null)
					$stmt->bindParam($key, $value);
				else
					$stmt->bindParam($key, $value, $type);
			}
		}

		$stmt->execute();
		return $stmt;
	}

	public function getResults(PDOStatement $stmt): array
	{
		$results = [];

		while ($row = $stmt->fetchAll(PDO::FETCH_ASSOC)) {
			$results[] = $row;
		}

		$stmt = null;

		return $results;
	}

	public function getPdo(): PDO
	{
		return $this->db;
	}

	protected function getPdoTypeFromVariable($var)
	{
		switch (gettype($var))
		{
			case 'integer': PDO::PARAM_INT;
			case 'string': PDO::PARAM_STR;
			default: return null;
		}
	}
}
