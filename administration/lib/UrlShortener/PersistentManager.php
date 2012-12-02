<?php
namespace UrlShortener;

class PersistentManager implements IPersistence {
	/**
	 * @var mysqli
	 */
	protected $conn = null; /*mysql connection*/
	protected $table = '';
	
	public function __construct() {
	}

	
	public function init($init) {
		$this->conn = @new \mysqli($init['server'], $init['user'], $init['password'],$init['database']);
		$this->table = $init['table'];
		//no error
		if ($this->conn->connect_errno === 0) {
			$this->conn->set_charset('utf8');
		} else {
			return false;	
		}
		return true;
	}
	
	public function getUrlList() {
		$arr = array();

		if ($stmt = $this->conn->prepare("SELECT idUrlShorten, shortenerHash, originalUrl FROM ".$this->table)) {
			$stmt->execute();
			$result = $stmt->get_result();

			while($row = $result->fetch_assoc()) {
				$arr[] = $row;
			}
			$result->free();
			$stmt->close();
		}
		return $arr;
	}
	
	public function checkUniqueHash($hash) {
		$ret = false;
		$hash = $this->conn->real_escape_string($hash);
		if ($stmt = $this->conn->prepare("SELECT idUrlShorten FROM $this->table WHERE idUrlShorten = ?")) {
			$stmt->bind_param("s", $hash);
			$stmt->execute();
			$result = $stmt->get_result();

			/*@var $result mysqli_stmt*/
			$ret = ($result->num_rows == 0);

			$result->free();
			$stmt->close();
		}
		return $ret;
	}
	
	public function saveUrl($url, $shortenerHash, $hash) {
		$ret = false;
		$url = $this->conn->real_escape_string($url);//PDO::quote($url);
		$hash = $this->conn->real_escape_string($hash);
		$shortenerHash = $this->conn->real_escape_string($shortenerHash);

        if (!$hash) {
            $hash = substr(md5(time()), 0, 8);
        }

		if ($stmt = $this->conn->prepare("INSERT INTO ".$this->table." (idUrlShorten, shortenerHash, originalUrl) VALUES (?,?,?)")) {
			$stmt->bind_param("sss", $hash, $shortenerHash, $url);
			$stmt->execute();
			$stmt->free_result();
		}

		if ($stmt = $this->conn->prepare("SELECT idUrlShorten AS hash, originalUrl AS url FROM ".$this->table." WHERE idUrlShorten = ?")){
			$stmt->bind_param("s", $hash);
			$stmt->execute();
			$result = $stmt->get_result();
			if( $result->num_rows == 1) {
				$ret = $result->fetch_assoc();
			}
			$result->free();
			$stmt->close();
		}

		return $ret;
	}
	
	public function getUrlByShortenerHash($hash) {
		$ret = false;
		$hash = $this->conn->real_escape_string($hash);
		if ($stmt = $this->conn->prepare("SELECT idUrlShorten AS hash, originalUrl AS url FROM $this->table WHERE shortenerHash = ?")){
			$stmt->bind_param("s", $hash);
			$stmt->execute();
			$result = $stmt->get_result();
			if( $result->num_rows == 1) {
				$ret = $result->fetch_assoc();
			}
			$result->free();  //??possible
			$stmt->close();
		}

		return $ret;
	}

    public function getUrlByHash($hash) {
		$ret = false;
		$hash = $this->conn->real_escape_string($hash);
		if ($stmt = $this->conn->prepare("SELECT idUrlShorten AS hash, originalUrl AS url, shortenerHash FROM $this->table WHERE idUrlShorten = ?")){
			$stmt->bind_param("s", $hash);
			$stmt->execute();
			$result = $stmt->get_result();
			if( $result->num_rows == 1) {
				$ret = $result->fetch_assoc();
			}
			$result->free();  //??possible
			$stmt->close();
		}

		return $ret;
	}
	
	public function deleteLink($hash) {
		$ret = false;
		$hash = $this->conn->real_escape_string($hash);
		if ($stmt = $this->conn->prepare("DELETE FROM $this->table WHERE idUrlShorten = ? LIMIT 1")){
			$stmt->bind_param("s", $hash);
			$stmt->execute();
			$ret = ($stmt->affected_rows == 1);

			$stmt->close();
		}

		return $ret;
    }
}