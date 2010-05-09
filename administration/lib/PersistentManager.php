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
		
		mysql_free_result($r);
		return $arr;
	}
	
	public function checkUniqueHash($hash) {
		$hash = mysql_escape_string($hash);
		$query = "SELECT idUrlShorten FROM ".$this->table." WHERE idUrlShorten = '".$hash."'";
	
		$r = mysql_query($query, $this->conn);

		$num = mysql_num_rows($r);
		mysql_free_result($r);
		
		return ($num == 0);
	}
	
	public function saveUrl($url, $bitlyHash, $hash) {
		$url = mysql_escape_string($url);
		$hash = mysql_escape_string($hash);
		$bitlyHash = mysql_escape_string($bitlyHash);
		
		$hash = substr(md5(mktime()), 0, 8);
		
		$query = "INSERT INTO ".$this->table." (idUrlShorten, bitlyHash, originalUrl) VALUES ('$hash','$bitlyHash','$url')";
		$r = mysql_query($query, $this->conn);
		mysql_free_result($r);
		
		$query = "SELECT idUrlShorten AS hash, originalUrl AS url FROM ".$this->table." WHERE idUrlShorten = '".$hash."'";
		$r = mysql_query($query, $this->conn);
		$ret = false;
		if( mysql_num_rows($r) == 1) {
			$ret = true;
			$result = mysql_fetch_assoc($r);
		}
		mysql_free_result($r); 
		
		if ($ret == true) {
			return $result;
		} else {
			return false;
		}
	}
	
	public function getUrlByBitly($bitlyHash) {
		$bitlyHash = mysql_escape_string($bitlyHash);
		$query = "SELECT idUrlShorten AS hash, originalUrl AS url FROM ".$this->table." WHERE bitlyHash = '".$bitlyHash."'";
		
		$ret = false;
		$r = mysql_query($query, $this->conn);
		if( mysql_num_rows($r) == 1) {
			$ret = true;
			$result = mysql_fetch_assoc($r);
		} 
		mysql_free_result($r);
		
		
		if ($ret == true) {
			return $result;
		} else {
			return false;
		}
	}
	
	
	
}
?>