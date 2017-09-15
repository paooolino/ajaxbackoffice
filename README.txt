# Installing

	composer require paooolino/machine-backoffice-plugin:dev-master

# Usage

## index.php

	<?php
		require("vendor/autoload.php");

		$machine = new \Machine\Machine();
		$machine->addPlugin("Backoffice");
		$machine->addPlugin("Link");
		$machine->addPlugin("Database");
		$machine->addPlugin("Upload");
		$machine->addPlugin("Image");

		$machine->plugin("Database")->setupSqlite("sample.db");
		$machine->plugin("Backoffice")->run("config.json", "/backoffice");

		$machine->run();

# For developers
	
	cd web
	php -S localhost:8000 index.php
