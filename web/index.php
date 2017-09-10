<?php
require("vendor/autoload.php");
require("../src/Backoffice.php");

use Cocur\Slugify\Slugify;

$machine = new \Machine\Machine();
$machine->addPlugin("Backoffice");
$machine->addPlugin("Link");
$machine->addPlugin("Database");
$machine->addPlugin("Upload");
$machine->addPlugin("Image");

$machine->plugin("Database")->setupSqlite("sample.db");
$machine->plugin("Backoffice")->run("config.json", "/backoffice");

$machine->run();