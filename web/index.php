<?php
require("vendor/autoload.php");

$machine = new \Machine\Machine();
$machine->addPlugin("Link");
$machine->addPlugin("Database");
$machine->plugin("Database")->setupSqlite("sample.db");

$machine->addPage("/", function($machine) {
	$db = $machine->plugin("Database");
	$tables = $db->getTables();
	
    return [
        "template" => "admin.php",
        "data" => [
			"tables" => $tables
		]
    ];
});

$machine->addPage("/{tablename}/list/{p}/", function($machine, $tablename, $p) {
	$db = $machine->plugin("Database");
	$tables = $db->getTables();
	
	$n = 50;
	$records = $db->find($tablename, "LIMIT ?	OFFSET ?", [$n, ($p - 1) * $n]);
	$count = $db->countRecords($tablename, "");
	
    return [
        "template" => "admin.php",
        "data" => [
			"tables" => $tables,
			"records" => $records,
			"count" => $count
		]
    ];
});

$machine->addPage("/{tablename}/{id}/", function($machine, $tablename, $id) {
	$db = $machine->plugin("Database");
	$tables = $db->getTables();
	$record = $db->load($tablename, $id);
	
    return [
        "template" => "admin.php",
        "data" => [
			"tables" => $tables,
			"record" => $record
		]
    ];
});

$machine->addAction("/api/tables/", "GET", function($machine) {
	// list tables
	$db = $machine->plugin("Database");
	$data = $db->getTables();
	
	$machine->setResponseCode(200);
	$machine->setResponseBody(json_encode($data));
});

$machine->addAction("/api/{tablename}/list/{p}/{n}/", "GET", function($machine, $tablename, $p, $n) {
	// list records of a table
	$db = $machine->plugin("Database");
	$data = [];
	$data["records"] = $db->find($tablename, "LIMIT ?	OFFSET ?", [$n, ($p - 1) * $n]);
	$data["count"] = $db->countRecords($tablename, "");
	
	$machine->setResponseCode(200);
	$machine->setResponseBody(json_encode($data));	
});

$machine->addAction("/api/{tablename}/{id}/", "GET", function($machine, $tablename, $id) {
	// list fields of a record
	$db = $machine->plugin("Database");
	$data = $db->load($tablename, $id);
	
	$machine->setResponseCode(200);
	$machine->setResponseBody(json_encode($data));	
});

$machine->addAction("/api/record/{tablename}/", "POST", function() {
	// add a record
});

$machine->addAction("/api/record/{tablename}/{id}/", "POST", function() {
	// update a record
});

$machine->addAction("/api/record/{tablename}/{id}/{field}/", "POST", function() {
	// update a single field
});

$machine->run();