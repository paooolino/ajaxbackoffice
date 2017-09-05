<?php
require("vendor/autoload.php");

$machine = new \Machine\Machine();
$machine->addPlugin("App");
$machine->addPlugin("Link");
$machine->addPlugin("Database");

$machine->plugin("Database")->setupSqlite("sample.db");
$machine->plugin("App")->loadConfig("config.json");

$machine->addPage("/", function($machine) {
	$db = $machine->plugin("Database");
	$tables = $db->getTables();
	
    return [
        "template" => "admin.php",
        "data" => [
			"tablename" => "",
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
	$maxp = ceil($count / $n);
    return [
        "template" => "admin.php",
        "data" => [
			"p" => $p,
			"maxp" => $maxp,
			"count" => $count,
			"tablename" => $tablename,
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
			"tablename" => $tablename,
			"id" => $id,
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

$machine->addAction("/api/record/{tablename}/", "POST", function($machine, $tablename) {
	// add a record
	$db = $machine->plugin("Database");
	$db->addItem($tablename, []);
	$count = $db->countRecords($tablename, "");
	$n = 50;
	$maxp = ceil($count / $n);
	
	$machine->redirect("/$tablename/list/$maxp/");
});

$machine->addAction("/api/record/{tablename}/{id}/", "POST", function($machine, $tablename, $id) {
	// update a record
	$db = $machine->plugin("Database");
	$app = $machine->plugin("App");
	$r = $machine->getRequest();
	
	$item = $db->load($tablename, $id);
	$props = $item->getProperties();
	foreach ($r["POST"] as $k => $v) {
		if (array_key_exists($k, $props)) {
			$item->{$k} = $v;
		}
	}
	
	// file upload
	foreach ($r["FILES"] as $k => $v) {
		if (array_key_exists($k, $props)) {
			$result = $app->upload($tablename, $k, $v);
			if ($result["result"] == "OK") {
				$item->{$k} = $result["filename"];
			} else {
				print_r($result["dump"]);
				die();
			}
		}
	}
	
	$db->update($item);
	$machine->redirect("/$tablename/list/1/");
});

$machine->addAction("/api/record/{tablename}/{id}/{field}/", "POST", function() {
	// update a single field
});

$machine->run();