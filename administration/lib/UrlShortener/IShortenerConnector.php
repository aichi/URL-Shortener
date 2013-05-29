<?php
namespace UrlShortener;

/**
 * Interface IShortenerConnector must be fulfilled by class specified in configuration file as a external shortener
 * connector.
 * @package UrlShortener
 */
interface IShortenerConnector {
	/**
	 * Constructor which should initialize all necessary connections.
	 * @param array $param Associative array from configuration file under 'shortenerConnectorConfig' key.
	 */
	public function __construct($param);

	/**
	 * Method returns associative array with information about shortened URL.
	 * @param string $url Fully classified URL which should be shortened.
	 * @return array Associative array with keys:
	 * 					'hash': external shortener hash for given URL
	 * 					'status': HTTP status code of service (200 for OK)
	 *					'new': flag if given URL is new or was shortened before
	 *				If error occurs than array contains these keys:
	 * 					'error': true
	 * 					'errorText': Some meaningful description of error.
	 */
	public function newURL($url);

	/**
	 * Method returns click statistics from external shortener for given external shortener hash.
	 * @param string $hash
	 * @return array Associative array with keys:
	 * 					'status': HTTP status code of service (200 for OK)
	 *					'userClicks': number of clicks from your service
	 * 					'globalClicks': number of clicks globally (same URL should be shortened by multiple services)
	 * 					'statUrl': URL to detailed page with statistics
	 *				If error occurs than array contains these keys:
	 * 					'error': true
	 * 					'errorText': Some meaningful description of error.
	 */
	public function statisticForUrl($hash);
}