<?php
namespace UrlShortener;

interface IShortenerConnector {
	public function __construct($param);
	public function newURL($url);
	public function statisticForUrl($hash);
}