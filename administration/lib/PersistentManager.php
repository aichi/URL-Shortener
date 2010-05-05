<?php
class PersistentManager extends TObjectStatic implements IPersistence {
	protected $conn = null; /*mysql connection*/
	protected static $instance = null;
	protected $table = '';
	
	protected function __construct() {
	}
	
	public static function getInstance($init) {
		if (PersistentManager::$instance == null) {
			$pm = new PersistentManager();
			if (!$pm->init($init)) {
				return false;
			}
			PersistentManager::$instance = $pm;
		}
		return PersistentManager::$instance;
	}
	
	protected function init ($init) {
		$this->conn = @mysql_connect($init['server'], $init['user'], $init['password']);
		$this->table = $init['table'];
		if ($this->conn) {
			mysql_select_db($init['database']);
			mysql_query('SET NAMES utf8', $this->conn);
		} else {
			return false;	
		}
		return true;
	}
	
	public function getUrlList() {
		$arr = array();
		
		$query = "SELECT idUrlShorten, bitlyHash, originalUrl FROM ".$this->table."";
		$r = mysql_query($query, $this->conn);
		while($row = mysql_fetch_assoc($r)) {
			$arr[] = $row;
		}
		return $arr;
	}
	
	
	public function saveLink($url, $bitlyHash, $hash) {
		$url = mysql_escape_string($url);
		$hash = mysql_escape_string($hash);
		
		$query = "INSERT INTO ".$this->table." (idUrlShorten, bitlyHash, originalUrl) VALUES ('$hash','$bitlyHash','$url')";
		$r = mysql_query($query, $this->conn);
	}
	
	
	
}
?>