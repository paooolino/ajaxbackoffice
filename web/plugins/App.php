<?php
namespace Plugin;

class App
{
    private $_machine;
	private $_config;
	
    public function __construct($machine)
    {
        $this->_machine = $machine;
    }

    public function loadConfig($config_file) 
	{
		if (file_exists($config_file)) {
			$this->_config = json_decode(file_get_contents($config_file), true);
		}
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
					$fieldvalue = $extern_name;
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
}


