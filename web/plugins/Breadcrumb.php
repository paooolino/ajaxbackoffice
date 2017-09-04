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

/**
 * Breadcrumb class
 *
 * A Breadcrumb menu management for the Machine
 *
 * @category Plugin
 * @package  Machine
 * @author   Paolo Savoldi <paooolino@gmail.com>
 * @license  https://github.com/paooolino/Machine/blob/master/LICENSE 
 *           (Apache License 2.0)
 * @link     https://github.com/paooolino/Machine
 */
class Breadcrumb
{
    
    private $_machine;
    private $breadcrumb_template = '<span><a href="{{HREF}}">{{LABEL}}</a></span>';
    private $breadcrumb_separator = ' | ';
    
    private $breadcrumbs;
    private $label;
    
    function __construct($machine) 
    {
        $this->_machine = $machine;
        $this->breadcrumbs = [];
        $this->label = "";
    }
    public function add($label, $href) 
    {
        $this->breadcrumbs[] = [
        "label" => $label,
        "href" => $href
        ];
    }
    
    public function setLabel($label) 
    {
        $this->label = $label;
    }
    
    // tags
    
    public function Render($params) 
    {
        $html = [];
        
        foreach($this->breadcrumbs as $breadcrumb) {
            $html[] = $this->_machine->populateTemplate(
                $this->breadcrumb_template, [
                "LABEL" => $breadcrumb["label"],
                "HREF" => $breadcrumb["href"]
                ]
            );
        }
        
        if ($this->label != "") {
            $html[] = $this->label;
        }
        
        return implode($this->breadcrumb_separator, $html);
    }
}
