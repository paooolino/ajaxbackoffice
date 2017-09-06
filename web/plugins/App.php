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
				
			default:	// default is "text"
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
				
			default:	// default is "text"
				echo '<input ' . (($fieldname == "id") ? "disabled" : "") . ' name="' . $fieldname . '" value="' . htmlentities($fieldvalue) . '">';
				break;			
		}
	}
	
	public function getConfig()
	{
		return $this->_config;
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


