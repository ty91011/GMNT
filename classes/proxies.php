<?php

class Proxies
{
    const API_KEY = "5b74c5bed80211534379454";
    
    public static function getProxyURL()
    {
	$proxyPrefix = "https://ghostproxies.com/proxies/api.json?key=";
	return $proxyPrefix . self::API_KEY;
    }

    static function getProxyList()
    {
	$proxyJSON = file_get_contents(self::getProxyURL());
	return json_decode($proxyJSON, true);
    }
    
    static function getRandomProxy()
    {
	$proxyList = self::getProxyList();
	return array_rand($proxyList['data']);
    }
}
