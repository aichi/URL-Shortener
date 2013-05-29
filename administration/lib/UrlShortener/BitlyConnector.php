<?php
namespace UrlShortener;
/**
 * Class BitlyConnector implements IShortenerConnector interface and use Bit.ly v3 API to store hashes and get
 * statistics for them.
 * @package UrlShortener
 */
class BitlyConnector implements IShortenerConnector{
	/**
	 * Login name to Bit.ly API
	 * @var string
	 */
	private $login;

	/**
	 * Password to Bit.ly API
	 * @var string
	 */
	private $apikey;

	/**
	 * Constructor
	 * @param array $param Associative array with 'login' and 'apikey' params.
	 */
	public function __construct($param) {
		$this->login = $param["login"];
		$this->apikey = $param["apikey"];
	}

	/**
	 * Returns shortener hash for given URL.
	 * @param string $url
	 * @return array
	 */
	public function newURL($url) {
		$u = urlencode($url);
		$urlli = "http://api.bit.ly/v3/shorten?login=".$this->login."&apiKey=".$this->apikey."&uri=$u&format=json";

		$response = $this->queryShortener($urlli);

		$data = json_decode($response);

		$resp = array();
		if ($data && $data->status_code == 200) {
			$resp["hash"] = $data->data->hash;
			$resp["status"] = $data->status_code;
			$resp["new"] = $data->data->new_hash;
		} else {
			$resp["error"] = true;
			$resp["errorText"] = $data->status_text;
		}
		return $resp;

	}

	/**
	 * Returns statistics for given hash.
	 * @param string $hash Bit.ly hash.
	 * @return array
	 */
	public function statisticForUrl($hash) {
		$url = "http://api.bit.ly/v3/clicks?login=".$this->login."&apiKey=".$this->apikey."&hash=".$hash."&format=json";
        $response = $this->queryShortener($url);

        $data = json_decode($response);

		$resp = array();
		if ($data && $data->status_code == 200) {
			$resp["status"] = $data->status_code;
			$resp["userClicks"] = $data->data->clicks[0]->user_clicks;
			$resp["globalClicks"] = $data->data->clicks[0]->global_clicks;
			$resp["statUrl"] = 'http://bit.ly/' . $hash . '+';
		} else {
			$resp["error"] = true;
			$resp["errorText"] = $data->status_text;
		}
		return $resp;
	}

	/**
     *  Method queries Bit.ly and returns result.
     *
     * @param string $url
     * @return string JSON string
     */
    protected function queryShortener($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }
}