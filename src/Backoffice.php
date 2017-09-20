<?php
namespace Machine\Plugin;

class Backoffice
{
    private $_machine;
    private $_config;
    private $_prefixDir;
	private $_cookieName;
	private $_cookieData;
    
    public function __construct($machine)
    {
        $this->_machine = $machine;
        $this->_prefixDir = "";
		$this->_cookieName = "xSaRoJrNsKNyZDOp";
    }

    /**
     * Set the prefix for plugin routes.
     *
     * @param string $prefixdir The directory to prefix, with initial slash and without trailing slash.
     *
     * @return void
     */
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

    public function GetLink($params)
    {
        if (gettype($params) == "string") {
            $params = [$params];
        }
        $slug = $params[0];
        $Link = $this->_machine->plugin("Link");
        return $Link->Get($this->_prefixDir . $slug);
    }
    
    /**
     * Return the absolute url for a plugin asset.
     *
     * @param string|array $params The relative path to the asset file (e.g. css/style.css)
     *
     * @return string The asset path.
     */    
    public function Asset($params)
    {
        if (gettype($params) == "string") {
            $params = [$params];
        }
        $filename = $params[0];
        $Link = $this->_machine->plugin("Link");
        return $Link->Get($this->_prefixDir . "/assets/" . $filename);
    }
    
    /**
     * Echoes a field value in the record list table cell.
     *
     * @param string $tablename
     * @param string $fieldname
     * @param string $fieldvalue
     *
     * @return void
     */    
    public function renderFieldInList($tablename, $fieldname, $fieldvalue)
    {
        $type = $this->_getFieldType($tablename, $fieldname);
        switch ($type) {
        case "image":
            if ($fieldvalue != "") {
                $image = $this->_machine->plugin("Image")->Get([$fieldvalue, "W", 32]);
                if ($image) {
                    return '<img src="' . $image . '">';
                } else {
                    return $fieldvalue;
                }
            }
            break;
            
        case "textarea":
            return nl2br($fieldvalue);
          break;

        case "code":
            return nl2br($fieldvalue);
          break;            
                
        default:    // default is "text". may be a relation.
            $relationlink = "";
            $relparts = explode("_", $fieldname);
            if (count($relparts) == 2) {
                $extern_table = $relparts[0];
                $extern_bean = $this->_machine->plugin("Database")->load($extern_table, $fieldvalue);
                $namefield = $this->_getFirstTextualField($extern_table);
                $extern_name = $extern_bean->{$namefield};
                //$url = $this->_machine->plugin("Link")->Get("/$extern_table/$fieldvalue/");
                //$fieldvalue = '<a href="' . $url . '" class="relationlink">' . $fieldvalue . '</a> ' . $extern_name;
                $fieldvalue = $fieldvalue . " | " . $extern_name;
            }
            return $fieldvalue;
          break;            
        }
    }
    
    /**
     * Echoes a field input in the edit form.
     *
     * @param string $tablename
     * @param string $fieldname
     * @param string $fieldvalue
     *
     * @return void
     */    
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
                
        default:    // default is "text". may be a relation.
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
    
    /**
     * Return the current plugin configuration.
     *
     * @return array
     */    
    public function getConfig()
    {
        return $this->_config;
    }
    
    /**
     * Plugin setup.
     *
     * This function must be called before running the application.
     *
     * @param string $config_file The .json file containing the pluign configuration
     * @param string $prefixdir   The prefix for the plugin routes (e.g. /backoffice)
     *
     * @return void
     */    
    public function run($config_file, $prefixdir = "")
    {
        $this->_loadConfig($config_file);
		$this->_loadCookie();
        $this->_setPrefixDir($prefixdir);
        $this->_setRoutes();
    }
    
    /**
     * Get an array of table names and returns a key-value array with table
     * names and their label
     *
     * @param array $dbtables
     *
     * @return array
     */
    public function filterTables($dbtables)
    {
        $return_tables = [];
        foreach ($dbtables as $d) {
            if (isset($this->_config["nav"])) {
                if (isset($this->_config["nav"][$d])) {
                    $return_tables[$d] = $this->_config["nav"][$d];
                }
            } else {
                $return_tables[$d] = $d;
            }
        }
        return $return_tables;
    }
    
    /**
     * Return the HTML filter control for the provided field.
     *
	 * A filter control may be:
	 *	- a simple text input
	 *  - a select for joined fields
	 *
     * @param string $tablename
     * @param string $fieldname
     *
     * @return string
     */ 
    public function getFilterControl($tablename, $fieldname)
    {
        // if a relation field, return a select.
        $relparts = explode("_", $fieldname);
        if (count($relparts) == 2) {
            return $this->getSelectHtml($fieldname, "", "filter[" . $fieldname . "]");
        } else {
            // else, return an input search field.
            return '<input name="search[' . $fieldname . ']" />';
        }
    }
    
    /**
     * Return the HTML Select control for a joined field.
     *
     * @param string $fieldname
     * @param string $fieldvalue The preselected value
     * @param string $emptylabel The label for the empty value
     *
     * @return string
     */ 
    public function getSelectHtml($fieldname, $fieldvalue, $variablename=NULL, $emptylabel="- Select -")
    {
        $Database = $this->_machine->plugin("Database");
        
        $relparts = explode("_", $fieldname);
        $extern_table = $relparts[0];
        $namefield = $this->_getFirstTextualField($extern_table);
        $options = $Database->find($extern_table, "ORDER BY $namefield", []);
        $options_html = '<option value="0">' . $emptylabel . '</option>';
        foreach ($options as $option) {
            $options_html .= '<option' . ($fieldvalue == $option->id ? " selected " : " ") . 'value="' . $option->id . '">' . $option->{$namefield} . '</option>';
        }
		$variablename = $variablename ? $variablename : $fieldname;
        return '<select name="' . $variablename . '">' . $options_html . '</select>';
    }
    
	public function setCookieData($data)
	{
		$this->_cookieData = $data;
		$this->_machine->setCookie(
			$this->_cookieName,
			json_encode($this->_cookieData),
			time() + 60*60*24*30,
			"/"
		);
	}
	
	private function _loadCookie()
	{
		$r = $this->_machine->getRequest();
		if (isset($r["COOKIE"][$this->_cookieName])) {
			$this->_cookieData = json_decode($r["COOKIE"][$this->_cookieName], true);
		}
	}
	
    /**
     * Gets the first textual field in a table.
     *
     * This is used to populate the select linkiung a joined table.
     *
     * @param string $table
     *
     * @return void
     */    
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
        if (isset($this->_config["fields"])
            && isset($this->_config["fields"][$tablename])
            && isset($this->_config["fields"][$tablename][$fieldname])
            && isset($this->_config["fields"][$tablename][$fieldname]["type"])
        ) {
            return $this->_config["fields"][$tablename][$fieldname]["type"];
        }
        return "text";
    }
    
    private function _setRoutes()
    {
        $machine = $this->_machine;
        $prefixdir = $this->_prefixDir;
        
		$Link = $machine->plugin("Link");
		$Link->setRoute("BACKOFFICE_HOME", $prefixdir . "/");
		$Link->setRoute("BACKOFFICE_ASSETS", $prefixdir . "/assets/{filename:.+}");
		$Link->setRoute("BACKOFFICE_LISTPAGE", $prefixdir . "/{tablename}/list/{p}/");
		$Link->setRoute("BACKOFFICE_UPDATEORDER", $prefixdir . "/{tablename}/{fieldname}/updateorder/");
		$Link->setRoute("BACKOFFICE_UPDATEFILTER", $prefixdir . "/{tablename}/{fieldname}/updatefilter/");
		
        $machine->addPage($Link->getRoute("BACKOFFICE_HOME"), function ($machine) {
			$db = $machine->plugin("Database");
			$tables = $this->filterTables($db->getTables());
		
			return [
				"template" => __DIR__ . "/template/admin.php",
				"data" => [
					"tablename" => "",
					"tables" => $tables
				]
			];
        });

        $machine->addAction($Link->getRoute("BACKOFFICE_ASSETS"), "GET", function ($machine, $filename) {
			$serverpath = __DIR__ . "/template/" . $filename;
			$machine->serve($serverpath);
        });
        
        $machine->addPage($Link->getRoute("BACKOFFICE_LISTPAGE"), function ($machine, $tablename, $p) {
			$db = $machine->plugin("Database");
			$tables = $this->filterTables($db->getTables());
		
			$n = 50;
			$records = $db->find($tablename, "LIMIT ? OFFSET ?", [$n, ($p - 1) * $n]);
			$count = $db->countRecords($tablename, "");
			$maxp = ceil($count / $n);
			return [
				"template" => __DIR__ . "/template/admin.php",
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
		
		$machine->addAction($Link->getRoute("BACKOFFICE_UPDATEORDER"), "GET", function($machine, $tablename, $fieldname) {
			// update cookie here
			$data = $this->_cookieData;

			if (!isset($data[$tablename][$fieldname]["ORDER"])) {
				$direction = "asc";
			} else {
				switch ($data[$tablename][$fieldname]["ORDER"]) {
					case "asc":
						$direction = "desc";
						break;
					case "desc":
						$direction = "";
						break;
				}
			}
			
			if ($direction == "") {
				unset($data[$tablename][$fieldname]["ORDER"]);
			} else {
				if (!isset($data[$tablename]))
					$data[$tablename] = [];
				
				if (!isset($data[$tablename][$fieldname]))
					$data[$tablename][$fieldname] = [];
				
				$data[$tablename][$fieldname]["ORDER"] = $direction;
			}
			
			$machine->plugin("Backoffice")->setCookieData($data);
			$machine->back();
		});
		
		$machine->addAction($Link->getRoute("BACKOFFICE_UPDATEORDER"), "POST", function($machine, $tablename, $fieldname) {
			// update cookie here
		});

        $machine->addPage(
            $prefixdir . "/error/{errtype}/", function ($machine, $errtype) {
                $r = $machine->getRequest();
                return [
                "template" => __DIR__ . "/template/adminerror.php",
                "data" => [
                "errtype" => $errtype
                ]
                ];
            }
        );

        $machine->addAction(
            $prefixdir . "/api/tables/", "GET", function ($machine) {
                // list tables
                $db = $machine->plugin("Database");
                $tables = $this->filterTables($db->getTables());
            
                $machine->setResponseCode(200);
                $machine->setResponseBody(json_encode($data));
            }
        );
        
        $machine->addPage(
            $prefixdir . "/{tablename}/{id}/", function ($machine, $tablename, $id) {
                $db = $machine->plugin("Database");
                $tables = $this->filterTables($db->getTables());
                $record = $db->load($tablename, $id);
            
                return [
                "template" => __DIR__ . "/template/admin.php",
                "data" => [
                "tablename" => $tablename,
                "id" => $id,
                "tables" => $tables,
                "record" => $record
                ]
                ];
            }
        );

        $machine->addAction(
            $prefixdir . "/api/{tablename}/list/{p}/{n}/", "GET", function ($machine, $tablename, $p, $n) {
                // list records of a table
                $db = $machine->plugin("Database");
                $data = [];
                $data["records"] = $db->find($tablename, "LIMIT ?   OFFSET ?", [$n, ($p - 1) * $n]);
                $data["count"] = $db->countRecords($tablename, "");
            
                $machine->setResponseCode(200);
                $machine->setResponseBody(json_encode($data));    
            }
        );

        $machine->addAction(
            $prefixdir . "/api/{tablename}/{id}/", "GET", function ($machine, $tablename, $id) {
                // list fields of a record
                $db = $machine->plugin("Database");
                $data = $db->load($tablename, $id);
            
                $machine->setResponseCode(200);
                $machine->setResponseBody(json_encode($data));    
            }
        );

        $machine->addAction(
            $prefixdir . "/api/record/{tablename}/", "POST", function ($machine, $tablename) {
                // add a record
                $db = $machine->plugin("Database");
                $db->addItem($tablename, []);
                $count = $db->countRecords($tablename, "");
                $n = 50;
                $maxp = ceil($count / $n);
            
                $machine->redirect("/$tablename/list/$maxp/");
            }
        );

        $machine->addAction(
            $prefixdir . "/api/record/{tablename}/{id}/", "POST", function ($machine, $tablename, $id) {
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
            }
        );

        $machine->addAction(
            $prefixdir . "/api/record/{tablename}/{id}/{field}/", "POST", function () {
                // update a single field
            }
        );
    }
}


