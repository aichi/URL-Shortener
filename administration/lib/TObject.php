<?php

/**
 * basic functionallity
 *
 */
class TObject {
	public function __construct() {	}
	
	
	/**
	 * magic set method if no setter
	 *
	 * @param string $name
	 * @param mixed $val
	 */
	public function __set($name, $val) {
    	$n = strtolower($name[0]).substr($name, 1);
    	$this->$n = $val;
    }
    
    /**
     * magic get method if no getter
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
    	$n = strtolower($name[0]).substr($name, 1);
    	$r = $this->$n;
    	return $r;
    }
}
?>