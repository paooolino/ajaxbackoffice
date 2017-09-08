<?php
namespace Plugin;

class Backoffice
{
    private $_machine;
	private $_config;
	private $_prefixDir;
	
    public function __construct($machine)
    {
        $this->_machine = $machine;
		$this->_prefixDir = "";
    }

	// example: "/backoffice"
	private function _setPrefixDir($prefixdir)
	{
		$this->_prefixDir = $prefixdir;
	}
	
    private function _loadConfig($config_file) 
	{
		if (file_exists($config_file)) {
			$this->_config = json_decode(file_get_contents($config_file), true);
		}
	}
	
	public function LinkGet($params)
	{
        if (gettype($params) == "string") {
            $params = [$params];
        }
		$path = $params[0];
		return $this->_machine->plugin("Link")->Get([$this->_prefixDir . $path]);
	}
	
	public function renderFieldInList($tablename, $fieldname, $fieldvalue)
	{
		$type = $this->_getFieldType($tablename, $fieldname);
		switch ($type) {
			case "image":
				if ($fieldvalue != "") {
					$image = $this->_machine->plugin("Image")->Get([$fieldvalue, "W", 32]);
					if ($image) {
						echo '<img src="' . $image . '">';
					} else {
						echo $fieldvalue;
					}
				}
				break;
			
			case "textarea":
				echo nl2br($fieldvalue);
				break;

			case "code":
				echo nl2br($fieldvalue);
				break;			
				
			default:	// default is "text". may be a relation.
				$relationlink = "";
				$relparts = explode("_", $fieldname);
				if (count($relparts) == 2) {
					$extern_table = $relparts[0];
					$extern_bean = $this->_machine->plugin("Database")->load($extern_table, $fieldvalue);
					$namefield = $this->_getFirstTextualField($extern_table);
					$extern_name = $extern_bean->{$namefield};
					//$url = $this->_machine->plugin("Link")->Get("/$extern_table/$fieldvalue/");
					//$fieldvalue = '<a href="' . $url . '" class="relationlink">' . $fieldvalue . '</a> ' . $extern_name;
					$fieldvalue = $fieldvalue . " (" . $extern_name . ")";
				}
				echo $fieldvalue;
				break;			
		}
	}
	
	public function renderField($tablename, $fieldname, $fieldvalue)
	{
		$type = $this->_getFieldType($tablename, $fieldname);
		switch ($type) {
			case "image":
				if ($fieldvalue != "") {
					$image = $this->_machine->plugin("Image")->Get([$fieldvalue, "W", 128]);
					echo '<div><img src="' . $image . '"><div>';
				}
				echo '<input type="file" name="' . $fieldname . '">';
				break;
			
			case "textarea":
				echo '<textarea name="' . $fieldname . '">' . $fieldvalue . '</textarea>';
				break;		

			case "code":
				echo '<textarea class="code" name="' . $fieldname . '">' . $fieldvalue . '</textarea>';
				break;			
				
			default:	// default is "text". may be a relation.
				$relparts = explode("_", $fieldname);
				if (count($relparts) == 2) {
					$extern_table = $relparts[0];
					$namefield = $this->_getFirstTextualField($extern_table);
					$options = $this->_machine->plugin("Database")->find($extern_table, "ORDER BY $namefield", []);
					$options_html = '';
					foreach ($options as $option) {
						$options_html .= '<option ' . ($fieldvalue == $option->id ? "selected" : "") . ' value="' . $option->id . '">' . $option->{$namefield} . '</option>';
					}
					echo '<select name="' . $fieldname . '">' . $options_html . '</select>';
					break;
				}
				echo '<input ' . (($fieldname == "id") ? "disabled" : "") . ' name="' . $fieldname . '" value="' . htmlentities($fieldvalue) . '">';
				break;			
		}
	}
	
	public function getConfig()
	{
		return $this->_config;
	}
	
	public function run($config_file, $prefixdir = "")
	{
		$this->_loadConfig($config_file);
		$this->_setPrefixDir($prefixdir);
		$this->_setRoutes();
	}
	
	private function _getFirstTextualField($table)
	{
		$db = $this->_machine->plugin("Database");
		$fields = $db->getFields($table);
		foreach ($fields as $f => $t) {
			if (strpos($t, "VARCHAR") !== false) {
				return $f;
			}
		}
		return "id";
	}
	
	private function _getFieldType($tablename, $fieldname)
	{
		if (
			isset($this->_config[$tablename])
			&& isset($this->_config[$tablename][$fieldname])
			&& isset($this->_config[$tablename][$fieldname]["type"])
		) {
			return $this->_config[$tablename][$fieldname]["type"];
		}
		return "text";
	}
	
	private function _setRoutes()
	{
		$machine = $this->_machine;
		$prefixdir = $this->_prefixDir;
		
		$machine->addPage($prefixdir . "/", function($machine) {
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

		$machine->addPage($prefixdir . "/{tablename}/list/{p}/", function($machine, $tablename, $p) {
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

		$machine->addPage($prefixdir . "/{tablename}/{id}/", function($machine, $tablename, $id) {
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

		$machine->addPage($prefixdir . "/error/{errtype}/", function($machine, $errtype) {
			$r = $machine->getRequest();
			return [
				"template" => "adminerror.php",
				"data" => [
					"errtype" => $errtype
				]
			];
		});

		$machine->addAction($prefixdir . "/api/tables/", "GET", function($machine) {
			// list tables
			$db = $machine->plugin("Database");
			$data = $db->getTables();
			
			$machine->setResponseCode(200);
			$machine->setResponseBody(json_encode($data));
		});

		$machine->addAction($prefixdir . "/api/{tablename}/list/{p}/{n}/", "GET", function($machine, $tablename, $p, $n) {
			// list records of a table
			$db = $machine->plugin("Database");
			$data = [];
			$data["records"] = $db->find($tablename, "LIMIT ?	OFFSET ?", [$n, ($p - 1) * $n]);
			$data["count"] = $db->countRecords($tablename, "");
			
			$machine->setResponseCode(200);
			$machine->setResponseBody(json_encode($data));	
		});

		$machine->addAction($prefixdir . "/api/{tablename}/{id}/", "GET", function($machine, $tablename, $id) {
			// list fields of a record
			$db = $machine->plugin("Database");
			$data = $db->load($tablename, $id);
			
			$machine->setResponseCode(200);
			$machine->setResponseBody(json_encode($data));	
		});

		$machine->addAction($prefixdir . "/api/record/{tablename}/", "POST", function($machine, $tablename) {
			// add a record
			$db = $machine->plugin("Database");
			$db->addItem($tablename, []);
			$count = $db->countRecords($tablename, "");
			$n = 50;
			$maxp = ceil($count / $n);
			
			$machine->redirect("/$tablename/list/$maxp/");
		});

		$machine->addAction($prefixdir . "/api/record/{tablename}/{id}/", "POST", function($machine, $tablename, $id) {
			// update a record
			$db = $machine->plugin("Database");
			$app = $machine->plugin("App");
			$up = $machine->plugin("Upload");
			
			if ($up->detectPostMaxSizeExceeded()) {
				$machine->redirect("/error/too-big/");
				return;
			}
			
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
				if ($v["error"] != 4) {
					if (array_key_exists($k, $props)) {
						$result = $up->upload($v);
						if ($result["result"] == "OK") {
							$item->{$k} = $result["filename"];
						} else {
							$slugify = new Slugify();
							$errname = $slugify->slugify($result["errname"]);
							$machine->redirect("/error/" . $errname . "/");
							return;
						}
					}
				}
			}
			
			$db->update($item);
			
			$rowindex = $db->countRecords($tablename, "WHERE id < ?", [$id]) + 1;
			$page = ceil(($rowindex) / 50);
			$machine->redirect("/$tablename/list/$page/#row$id");
		});

		$machine->addAction($prefixdir . "/api/record/{tablename}/{id}/{field}/", "POST", function() {
			// update a single field
		});
	}
}


