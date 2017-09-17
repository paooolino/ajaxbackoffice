<?php

namespace Machine\Tests;

require './web/vendor/autoload.php';
require './src/Backoffice.php';

class BackofficeTest extends \PHPUnit_Framework_TestCase
{
	private $machine;
	private $Backoffice;
	
	private function _requestAndSetup($method, $path)
	{
		$req = [
			"SERVER" => [
				"REQUEST_METHOD" => $method,
				"REQUEST_URI" => $path,
				"HTTP_HOST" => "localhost:8000"
			]
		];
		
		$this->machine = new \Machine\Machine($req);
		$this->machine->addPlugin("Link");
		$this->machine->addPlugin("Database");
		$this->Backoffice = $this->machine->addPlugin("Backoffice");
		$this->machine->plugin("Database")->setupSqlite("./web/sample.db");
	}
	
	public function testRenderTemplate()
	{
		$this->_requestAndSetup("GET", "/backoffice/");
		$this->Backoffice->run("./tests/config-test.json", "/backoffice");
		$response = $this->machine->run(true);
		$this->assertContains('<body class="machine-backoffice-plugin-admin">', $response["body"]);
	}
	
	public function testGetConfig()
	{
		$this->_requestAndSetup("GET", "/backoffice/");
		$this->Backoffice->run("./tests/config-test.json", "/backoffice");
		$response = $this->machine->run(true);
		$config = $this->Backoffice->getConfig();
		$this->assertEquals(json_decode(file_get_contents("./tests/config-test.json"), true), $config);
	}
	
	public function testOriginalTables()
	{
		$this->_requestAndSetup("GET", "/backoffice/");
		$this->Backoffice->run("./tests/config-test.json", "/backoffice");
		
		$tables = $this->machine->plugin("Database")->getTables();
		$filtered_tables = $this->Backoffice->filterTables($tables);
		$this->assertEquals(count($tables), count($filtered_tables));	
		
		$response = $this->machine->run(true);
		$this->assertContains('<li class=""><a class="button" href="//localhost:8000/backoffice/customers/list/1/">customers</a></li>', $response["body"]);
	}
	
	public function testFilterTables()
	{
		$this->_requestAndSetup("GET", "/backoffice/");
		$this->Backoffice->run("./tests/config-test-tables-filtered.json", "/backoffice");
		$tables = $this->machine->plugin("Database")->getTables();
		
		$filtered_tables = $this->Backoffice->filterTables($tables);
		$this->assertEquals(2, count($filtered_tables));		
		$this->assertEquals("Clienti", $filtered_tables["customers"]);		
		$this->assertEquals("Impiegati", $filtered_tables["employees"]);		

		$response = $this->machine->run(true);
		$this->assertContains('<li class=""><a class="button" href="//localhost:8000/backoffice/customers/list/1/">Clienti</a></li>', $response["body"]);
	}
	
	public function testRenderFieldInList()
	{
		$this->_requestAndSetup("GET", "/backoffice/artists/list/1/");
		$this->machine->addPlugin("Image");
		$this->Backoffice->run("./tests/config-test.json", "/backoffice");
		
		$html = $this->Backoffice->renderFieldInList("artists", "name", "AC/DC");
		$this->assertEquals("AC/DC", $html);

		/*
		$html = $this->Backoffice->renderFieldInList("artists", "picture", "/image.jpg");
		$this->assertEquals('<img src="//localhost:8000/uploads/big/thumbs/32xH/image.jpg">', $html);
		*/
		
		$html = $this->Backoffice->renderFieldInList("genres", "description", "A long description");
		$this->assertEquals("A long description", $html);		
		
		$response = $this->machine->run(true);
		$this->assertContains('<td>AC/DC</td>', $response["body"]);
	}
}