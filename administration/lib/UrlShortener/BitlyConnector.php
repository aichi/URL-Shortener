<?php
namespace UrlShortener;

class BitlyConnector implements IShortenerConnector{
	private $login;
	private $apikey;

	public function __construct($param) {
		$this->login = $param["login"];
		$this->apikey = $param["apikey"];
	}

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
     *  query bitly and return result
     *
     * @param string $url
     * @return string
     */
//    protected function queryShortener($url) {
//        $ch = curl_init();
//        curl_setopt($ch, CURLOPT_HEADER, 0);
//        curl_setopt($ch, CURLOPT_URL, $url);
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//        $response = curl_exec($ch);
//        curl_close($ch);
//
//        return $response;
//    }
	protected function queryShortener($url) {
		if (strstr($url, "click")) {
			$resp = '{"status_code": 200,';
			$resp .= '"data": {';
			$resp .= '"clicks": [';
			$resp .= '{';
			$resp .= '"user_clicks": 22,';
			$resp .= '"global_clicks": 150';
			$resp .= '}';
			$resp .= ']';
			$resp .= '}';
			$resp .= '}';
		} else {
			$resp = '{"status_code": 200,';
			$resp .= '"data": {';
			$resp .= '"hash": "'. substr(md5(time()), 0, 8) .'",';
			$resp .= '"new_hash": 1';
			$resp .= '}}';
		}

		return $resp;
	}
}