<?php
require __DIR__ . '/../vendor/autoload.php';

use \DI\Container;
use \Rdb\ContainerFactory;

function logMsg(string $msg)
{
	$fileName = basename(__FILE__);
	echo "[{$fileName}] $msg" . PHP_EOL;
}

function makeDataDirectory(string $path)
{
	if (!is_dir($path))
	{
		mkdir($path, 0755);
		logMsg("Created data directory $path !");
	}
	else
		logMsg("Data directory already exists: $path");
}

function makeDbFile(Container $container)
{
	try
	{
		$container->get('db');
		logMsg("SQLite database successfully created !");
	}
	catch (PDOException $e)
	{
		logMsg("Database connection failed: " . $e->getMessage());
		exit(1);
	}
}

function createReviewableTables($container)
{
	$db = $container->get('db');
	$sql = file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, 'install-db', 'create-reviewables.sql']));
	$ret = $db->getPdo()->exec($sql);
	logMsg("Reviewable tables created !");
}

$projectRootDirectory = dirname(__DIR__);
$projectDataDirectory = $projectRootDirectory . DIRECTORY_SEPARATOR . 'data';
$container = ContainerFactory::get();

makeDataDirectory($projectDataDirectory);
makeDbFile($container);
createReviewableTables($container);
