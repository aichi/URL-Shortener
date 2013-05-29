<?php
namespace UrlShortener;

/**
 * Class PersistentManager implements IPersistence interface and store data into MySQL
 * using mysqli PHP interface.
 * @package UrlShortener
 */
class PersistentManager implements IPersistence {
	/**
	 * connection object
	 * @var \mysqli
	 */
	protected $conn = null;

	/**
	 * table where Shortener data are stored
	 * @var string
	 */
	protected $table = '';

	/**
	 * Constructor
	 */
	public function __construct() {
	}

	/**
	 * This function is delayed constructor. This is because constructor is called during
	 * application initialization when configuration file is red.
	 * @param array $init
	 * @return bool
	 */
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

	/**
	 * Method returns all shortened URLs from table
	 * @return array
	 */
	public function getUrlList() {
		$arr = array();

		if ($stmt = $this->conn->prepare("SELECT idUrlShorten, shortenerHash, originalUrl FROM ".$this->table)) {
			$stmt->execute();

			$stmt->bind_result($id, $shorten, $url);
			while($stmt->fetch()) {
				$arr[] = array("hash" => $id, "shortenHash" => $shorten, "url" => $url);
			}

			$stmt->close();
		}
		return $arr;
	}

	/**
	 * Method checks if given string is unique compared to stored hashes.
	 * @param string $hash
	 * @return bool
	 */
	public function checkUniqueHash($hash) {
		$ret = false;
		$hash = $this->conn->real_escape_string($hash);
		if ($stmt = $this->conn->prepare("SELECT idUrlShorten FROM ".$this->table." WHERE idUrlShorten = ?")) {
			$stmt->bind_param("s", $hash);
			$stmt->execute();

			// Is there better way to get information if there is result?
			$stmt->bind_result($id);
			$ret = !$stmt->fetch();

			$stmt->close();
		}
		return $ret;
	}

	/**
	 * Saves given URL and hashes into database. If $hash is ommited than new with 8 characters would be generated.
	 * @param string $url
	 * @param string $shortenerHash
	 * @param string $hash
	 * @return array|bool
	 */
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

		// Tests that information were stored, because there is no row ID yet.
		if ($stmt = $this->conn->prepare("SELECT idUrlShorten AS hash, originalUrl AS url FROM ".$this->table." WHERE idUrlShorten = ?")){
			$stmt->bind_param("s", $hash);
			$stmt->execute();

			$stmt->bind_result($hash, $url);
			if($stmt->fetch()) {
				$ret = array("hash" => $hash, "url" => $url);
			}
			$stmt->close();
		}

		return $ret;
	}

	/**
	 * Returns URL information for given shortener hash.
	 * @param string $shortenerHash
	 * @return array|bool
	 */
	public function getUrlByShortenerHash($shortenerHash) {
		$ret = false;
		$shortenerHash = $this->conn->real_escape_string($shortenerHash);
		if ($stmt = $this->conn->prepare("SELECT idUrlShorten AS hash, originalUrl AS url FROM ".$this->table." WHERE shortenerHash = ?")){
			$stmt->bind_param("s", $shortenerHash);
			$stmt->execute();
			$stmt->bind_result($hash, $url);
			if ($stmt->fetch()) {
				$ret = array("hash" => $hash, "url" => $url);
			}

			$stmt->close();
		}

		return $ret;
	}

	/**
	 * Returns URL information for given local hash.
	 * @param string $hash
	 * @return array|bool
	 */
	public function getUrlByHash($hash) {
		$ret = false;
		$hash = $this->conn->real_escape_string($hash);
		if ($stmt = $this->conn->prepare("SELECT idUrlShorten AS hash, originalUrl AS url, shortenerHash FROM ".$this->table." WHERE idUrlShorten = ?")){
			$stmt->bind_param("s", $hash);
			$stmt->execute();
			$stmt->bind_result($hash, $url, $shortenerHash);
			if($stmt->fetch()) {
				$ret = array("hash" => $hash, "url" => $url, "shortenerHash" => $shortenerHash);
			}

			$stmt->close();
		}

		return $ret;
	}

	/**
	 * Deletes URL information from database.
	 * @param string $hash
	 * @return bool
	 */
	public function deleteLink($hash) {
		$ret = false;
		$hash = $this->conn->real_escape_string($hash);
		if ($stmt = $this->conn->prepare("DELETE FROM ".$this->table." WHERE idUrlShorten = ? LIMIT 1")){
			$stmt->bind_param("s", $hash);
			$stmt->execute();
			$ret = ($stmt->affected_rows == 1);

			$stmt->close();
		}

		return $ret;
    }
}