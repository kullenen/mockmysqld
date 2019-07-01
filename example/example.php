<?php

require '../vendor/autoload.php';

use MockMysqld\Options;
use MockMysqld\Server;
use MockMysqld\Scripts;
use MockMysqld\filter\ChangeToMemoryEngineFilter;

// define init scripts
$scripts = new Scripts(['./db.sql', './structure.sql'], new ChangeToMemoryEngineFilter);

// define mysqld command line options
$options = new Options(['tmpdir' => '/tmp/mockmysqld-example']);

// start server
$server = new Server($options, $scripts);
echo "Starting mock server...\n";
$server->start();

echo "Testing...\n";
try {

	// connect to db
	$db = new PDO(
		'mysql:dbname=example_db;host=127.0.0.1;port=13306',
		'example_user'
	);

	// some operations
	echo "Adding data...\n";
	$db->exec("INSERT INTO `items` (`name`) values ('Name a'), ('Name b'), ('Name c')");
	echo "Receiving data...\n";
	print_r($db->query('SELECT * FROM `items`')->fetchAll(PDO::FETCH_ASSOC));

} catch (Exception $e) {
	echo "$e\n";
}

// stop server
echo "Stopping mock server...\n";
$server->stop();
