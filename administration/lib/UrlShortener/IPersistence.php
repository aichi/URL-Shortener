<?php
namespace UrlShortener;

interface IPersistence {
	public function __construct();
	public function init($init);
	public function getUrlList();
	public function checkUniqueHash($hash);
	public function saveUrl($url, $bitlyHash, $hash);
	public function deleteLink($hash);
	public function getUrlByHash($hash);
	public function getUrlByShortenerHash($hash);
}