<?php

namespace Machine\Tests;

require './web/vendor/autoload.php';
require './src/Backoffice.php';

class BackofficeTest extends \PHPUnit_Framework_TestCase
{
	private $machine;
	private $Backoffice;
	private $Link;
	
	private function _requestAndSetup($method, $path, $mergeReq=[])
	{
		$req = array_merge_recursive([
			"SERVER" => [
				"REQUEST_METHOD" => $method,
				"REQUEST_URI" => $path,
				"HTTP_HOST" => "localhost:8000"
			]
		], $mergeReq);
		
		$this->machine = new \Machine\Machine($req);
		$this->Link = $this->machine->addPlugin("Link");
		$this->machine->addPlugin("Database");
		$this->Backoffice = $this->machine->addPlugin("Backoffice");
		$this->machine->plugin("Database")->setupSqlite("./web/sample.db");
	}
		
	public function testFilterCookieNewContains()
	{
		$this->_requestAndSetup("POST", "/backoffice/tracks/name/updatefilter/", [
			"POST" => [
				"search" => "Rock"
			]
		]);
		$this->Backoffice->run("./tests/config-test.json", "/backoffice");
		$response = $this->machine->run(true);
		
		$expected = [
			"tracks" => [
				["name", "contains", "Rock"]
			]
		];
		$cookie = json_decode($response["cookies"][0][1], true);
		$this->assertEquals("l5vX0SeUND31c6hl", $response["cookies"][0][0]);
		$this->assertEquals($expected, $cookie);
	}
	
	public function testFilterCookieNewEquals()
	{
		$this->_requestAndSetup("POST", "/backoffice/tracks/genres_id/updatefilter/", [
			"POST" => [
				"filter" => "1"
			]
		]);
		$this->Backoffice->run("./tests/config-test.json", "/backoffice");
		$response = $this->machine->run(true);
		
		$expected = [
			"tracks" => [
				["genres_id", "equals", "1"]
			]
		];
		$cookie = json_decode($response["cookies"][0][1], true);
		$this->assertEquals("l5vX0SeUND31c6hl", $response["cookies"][0][0]);
		$this->assertEquals($expected, $cookie);
	}
	
	public function testFilterCookieRemove()
	{
		$this->_requestAndSetup("POST", "/backoffice/tracks/genres_id/updatefilter/", [
			"COOKIE" => [
				"l5vX0SeUND31c6hl" => json_encode([
					"tracks" => [
						["name", "contains", "rock"],
						["genres_id", "equals", "1"]
					]
				])
			],
			"POST" => [
				"filter" => "0"
			]
		]);
		$this->Backoffice->run("./tests/config-test.json", "/backoffice");
		$response = $this->machine->run(true);
		
		$expected = [
			"tracks" => [
				["name", "contains", "rock"]
			]
		];
		$cookie = json_decode($response["cookies"][0][1], true);
		$this->assertEquals("l5vX0SeUND31c6hl", $response["cookies"][0][0]);
		$this->assertEquals($expected, $cookie);
	}
	
	public function testFilterCookieOverwrite()
	{
		$this->_requestAndSetup("POST", "/backoffice/tracks/name/updatefilter/", [
			"COOKIE" => [
				"l5vX0SeUND31c6hl" => json_encode([
					"tracks" => [
						["name", "contains", "rock"],
						["genres_id", "equals", "1"]
					]
				])
			],
			"POST" => [
				"search" => "King"
			]
		]);
		$this->Backoffice->run("./tests/config-test.json", "/backoffice");
		$response = $this->machine->run(true);
		
		$expected = [
			"tracks" => [
				["name", "contains", "King"],
				["genres_id", "equals", "1"]
			]
		];
		$cookie = json_decode($response["cookies"][0][1], true);
		$this->assertEquals("l5vX0SeUND31c6hl", $response["cookies"][0][0]);
		$this->assertEquals($expected, $cookie);
	}
	
	public function testOrderCookieNew()
	{
		$this->_requestAndSetup("GET", "/backoffice/tracks/name/updateorder/");
		$this->Backoffice->run("./tests/config-test.json", "/backoffice");
		$response = $this->machine->run(true);
		
		$expected = [
			"tracks" => [
				["name", "asc"]
			]
		];
		$cookie = json_decode($response["cookies"][0][1], true);
		$this->assertEquals($expected, $cookie);
	}
	
	public function testOrderCookieAscToDesc()
	{
		$addReq = [
			"COOKIE" => [
				"xSaRoJrNsKNyZDOp" => json_encode([
					"tracks" => [
						["composer", "asc"],
						["name", "asc"]
					]
				])
			]
		];
		$this->_requestAndSetup("GET", "/backoffice/tracks/name/updateorder/", $addReq);
		$this->Backoffice->run("./tests/config-test.json", "/backoffice");
		$response = $this->machine->run(true);

		$expected = [
			"tracks" => [
				["composer", "asc"],
				["name", "desc"]
			]
		];
		$cookie = json_decode($response["cookies"][0][1], true);
		$this->assertEquals($expected, $cookie);
	}
	
	public function testOrderCookieDescToEmpty()
	{
		$addReq = [
			"COOKIE" => [
				"xSaRoJrNsKNyZDOp" => json_encode([
					"genres" => [
						["id", "asc"]
					],
					"tracks" => [
						["composer", "desc"],
						["name", "asc"]
					]
				])
			]
		];
		$this->_requestAndSetup("GET", "/backoffice/tracks/composer/updateorder/", $addReq);
		$this->Backoffice->run("./tests/config-test.json", "/backoffice");
		$response = $this->machine->run(true);

		$expected = [
			"genres" => [
				["id", "asc"]
			],
			"tracks" => [
				["name", "asc"]
			]
		];
		$cookie = json_decode($response["cookies"][0][1], true);
		$this->assertEquals($expected, $cookie);
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
	
	public function testGetFilterControl()
	{
		$this->_requestAndSetup("GET", "/backoffice/");
		$this->Backoffice->run("./tests/config-test.json", "/backoffice");
		
		$html = $this->Backoffice->getFilterControl("artists", "name");
		$this->assertEquals('<input name="search" />', $html);
		
		$html = $this->Backoffice->getFilterControl("tracks", "mediatypes_id");
		$this->assertContains('<select name="filter"', $html);
		$this->assertContains('<option value="5">AAC audio file</option>', $html);
	}
	
	public function testRenderOrderedFilteredList()
	{
		$adds = [
			"COOKIE" => [
				// order
				"xSaRoJrNsKNyZDOp" => json_encode([
					"tracks" => [
						["id", "desc"]
					]
				]),
				// filter
				"l5vX0SeUND31c6hl" => json_encode([
					"tracks" => [
						["albums_id", "equals", "4"]
					]
				])
			],
		];
		$this->_requestAndSetup("GET", "/backoffice/tracks/list/1/", $adds);
		$this->Backoffice->run("./tests/config-test.json", "/backoffice");
		$response = $this->machine->run(true);
		$this->assertContains('<tr data-count="1" id="row22">', $response["body"]);
	}
}