<?php
namespace Rdb\Db;

use \PDO;
use \PDOStatement;

interface DatabaseInterface {
	static public function create(array $config = []);
	public function disconnect(): void;
	public function executeQuery(string $query, array $params = []): PDOStatement;
	public function getResults(PDOStatement $stmt): array;
	public function getPdo(): PDO;
}
