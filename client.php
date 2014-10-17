<?php

class Client {

    const USER_AGENT = 'WhatSnatch 1.0 PHP github.com/kimoi';
    const RATE_LIMIT_SECONDS = 2;
    const COOKIE_FILE = 'cookies.dat';
    const API_BASE_URL = 'https://what.cd/ajax.php?';
    const LOGIN_URL = 'https://what.cd/login.php';

    // time of last request
    protected $lastRequestTime;

    public function login($username, $password) {
        $post = array(
            'username' => $username,
            'password' => $password
        );

        $result = $this->exec(self::LOGIN_URL, $post);
    }

    public function api($action, $params = null) {
        //build url
        $queryParams = array('action' => $action);

        if(is_array($params)) {
            $queryParams = array_merge($queryParams, $params);
        }

        $url = self::API_BASE_URL.http_build_query($queryParams);

        // grab result and decode json
        $plain = $this->exec($url);
        $json = json_decode($plain);

        if(isset($json->status) && $json->status === 'success') {
            return $json->response;
        }
        else {
            return false;
        }   
    }

    public function exec($url, $postData = null) {
        $this->rateLimit();

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_COOKIEFILE, self::COOKIE_FILE);
        curl_setopt($ch, CURLOPT_COOKIEJAR, self::COOKIE_FILE);
        curl_setopt($ch, CURLOPT_USERAGENT, self::USER_AGENT);

        if(is_array($postData)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        }

        $ret = curl_exec($ch);
        curl_close($ch);

        return $ret;
    }

    /*
        api docs ask to not make more than 5 requests every 10 seconds,
        so we'll rate limit this to a request every 2 seconds
        https://github.com/WhatCD/Gazelle/wiki/JSON-API-Documentation
    */
    protected function rateLimit() {
        $now = time();

        if(!$this->lastRequestTime) {
            // never made a request, good to go
            $this->lastRequestTime = $now;
        }
        else {
            $delta = ($now - $this->lastRequestTime);
            if($delta < self::RATE_LIMIT_SECONDS) {
                // wait until 2 seconds is up
                sleep(self::RATE_LIMIT_SECONDS - $delta);
            }
        }
    }

}

?>