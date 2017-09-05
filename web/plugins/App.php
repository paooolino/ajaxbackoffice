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
	
	public function renderField($tablename, $fieldname, $fieldvalue)
	{
		$type = $this->_getFieldType($tablename, $fieldname);
		switch ($type) {
			case "image":
				echo '<input type="file" name="' . $fieldname . '">';
				break;
			default:	// default is "text"
				echo '<input ' . (($fieldname == "id") ? "disabled" : "") . ' name="' . $fieldname . '" value="' . $fieldvalue . '">';
				break;			
		}
	}
	
	public function upload($tablename, $fieldname, $filearr)
	{
		$uploaddir = 'uploads/';
		$uploadfile = $uploaddir . basename($filearr['name']);

		if (move_uploaded_file($filearr['tmp_name'], $uploadfile)) {
			//echo "File is valid, and was successfully uploaded.\n";
			return ["result" => "OK", "filename" => $filearr['name']];
		} else {
			//echo "Possible file upload attack!\n";
		}
		return ["result" => "KO", "dump" => $filearr];
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


