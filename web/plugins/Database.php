<?php
/**
 * The Machine
 *
 * PHP version 5
 *
 * @category  Plugin
 * @package   Machine
 * @author    Paolo Savoldi <paooolino@gmail.com>
 * @copyright 2017 Paolo Savoldi
 * @license   https://github.com/paooolino/Machine/blob/master/LICENSE 
 *            (Apache License 2.0)
 * @link      https://github.com/paooolino/Machine
 */
namespace Plugin;

// dependency check
/*
if (!class_exists("\RedBeanPHP\R")) 
{
	die("Error: \RedBeanPHP\R class not defined. Please run<br><pre>composer require gabordemooij/redbean</pre><br>to add it to your project.");
}
*/
    
use \RedBeanPHP\R;

/**
 * Database class
 *
 * A database interface for the Machine, using RedbeanPHP lightweight ORM
 *
 * @category Plugin
 * @package  Machine
 * @author   Paolo Savoldi <paooolino@gmail.com>
 * @license  https://github.com/paooolino/Machine/blob/master/LICENSE 
 *           (Apache License 2.0)
 * @link     https://github.com/paooolino/Machine
 */
class Database
{
    private $_machine;
    
    /**
     * Database plugin constructor.
     *
     * The user should not use it directly, as this is called by the Machine.
     *
     * @param Machine $machine the Machine instance.
     */
    public function __construct($machine)
    {
        $this->_machine = $machine;
    }
    
    /**
     * Setup a sqlite connection.
     *
     * @param string $db_file The database sqlite file to use or create.
     *
     * @return void
     */
    public function setupSqlite($db_file)
    {
		if (!R::hasDatabase("default")) {
			R::setup('sqlite:' . $db_file);
		}
    }
    
    /**
     * Setup a MySQL/MariaDB connection.
     *
     * @param string $db_host The MySQL database host.
     * @param string $db_user The MySQL database user.
     * @param string $db_pass The MySQL database password.
     * @param string $db_name The MySQL database name.
     *
     * @return void
     */
    public function setupMysql($db_host, $db_user, $db_pass, $db_name) 
    {
        R::setup('mysql:host=' . $db_host . ';dbname=' . $db_name, $db_user, $db_pass);
    }
	
	public function close()
	{
		R::close();
	}
    
    // ========================================================================
    //	Create
    // ========================================================================
    
    /**
     * Add a record
     *
     * @param string $table The table name.
     * @param array  $data  The data to save.
     *
     * @return int the inserted id
     */
    public function addItem($table, $data) 
    {
        $item = R::dispense($table);
        foreach ($data as $k => $v) {
            $item->{$k} = $v;
        }
        $id = R::store($item);
        return $id;
    }
    
    // ========================================================================
    //	Read
    // ========================================================================
    
    /**
     * Find an item by id
     *
     * @param string $table The table name.
     * @param int    $id    The record id.
     *
     * @return object The requested record.
     */
    public function load($table, $id) 
    {
        return R::load($table, $id);
    }
    
    /**
     * Retrieve all the records from a table
     *
     * @param string $table The table name.
     *
     * @return array An array of all records contained in the table.
     */    
    public function findAll($table) 
    {
        return R::findAll($table);
    }
	
	/**
	 * Find an item by a unique field
	 */
	public function findField($table, $field, $value) {
		return R::findOne($table, " $field = ? ", [$value]);
	}
    
    /**
     * Find with sql condition
     *
     * @param string $table        The table name.
     * @param string $sqlCondition The condition to append after "WHERE".
     * @param array  $boundData    The data to bound in the query.
     *
     * @return array An array of records matching the conditions
     */
    public function find($table, $sqlCondition, $boundData = []) 
    {
        return R::find($table, " " . $sqlCondition . " ", $boundData);
    }

    // ========================================================================
    //	Update
    // ========================================================================	
    
    /**
     * Updates a record
     *
     * @return int The updated record id.
     */
    public function update($bean) 
    {
        return R::store($bean);
    }
    

    // ========================================================================
    //	Others
    // ========================================================================	
    
    /**
     * Get all the complete number of records for a collection
     *
	 * @param string $tablename the collection name.
     * @param string $sqlCondition The condition to append after "WHERE".
     * @param array  $boundData    The data to bound in the query.
	 *
     * @return integer The number of records.
     */
    public function countRecords($tablename, $sqlCondition="", $boundData=[])
    {
        return R::count($tablename, $sqlCondition, $boundData);
    }
	
    /**
     * Get all the tables in the database
     *
     * @return array The tables list.
     */
    public function getTables()
    {
        return R::inspect();
    }
    
    /**
     * Get all the fields from a table
     *
     * @return array The fields list.
     */
    public function getFields($tablename)
    {
        return R::inspect($tablename);
    }
    
    /**
     * Destroy all tables in the database
     *
     * @return void
     */
    public function nuke() 
    {
        R::nuke();
    }
}
