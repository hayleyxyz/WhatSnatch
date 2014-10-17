<?php

require 'Client.php';
require 'Log.php';

// load config
$config = json_decode(file_get_contents('config.json'));

class Scrape {

    protected $config;
    protected $client;
    protected $latestTorrentId;
    protected $authKey;
    protected $passKey;

    public function __construct($config) {
        $this->config = $config;

        $this->client = new Client();
    }

    public function start() {
        Log::debug('Logging in');

        $this->client->login($this->config->user->username, $this->config->user->password);

        // get creds
        $index = $this->client->api('index');
        $this->authKey = $index->authkey;
        $this->passKey = $index->passkey;

        $this->getLatestTorrent();
    }

    protected function getLatestTorrent() {
        Log::debug('Finding latest torrent');

        $browse = $this->client->api('browse');
        if(!$browse) {
            Log::error('Failed to load browse page');
            return;
        }

        $group = current($browse->results);

        // get latest torrent in group
        $this->latestTorrentId = 0;
        foreach($group->torrents as $torrent) {
            if($torrent->torrentId > $this->latestTorrentId) {
                $this->latestTorrentId = $torrent->torrentId;
            }
        }

        $torrent = $this->client->api('torrent', array('id' => $this->latestTorrentId));
        $this->processTorrent($torrent);

        $this->watch();
    }

    protected function processTorrent($torrent) {
        if($this->applyRules($torrent)) {
            Log::debug('Downloading torrent: %s (#%d)', $torrent->group->name, $torrent->torrent->id);

            $url = $this->getTorrentUrl($torrent);
            $torrentFile = sprintf('%s/%d.torrent', $this->config->torrentsDir, $torrent->torrent->id);
            copy($url, $torrentFile);
        }
        else {
            //Log::debug('Torrent not eligible: %s (#%d)', $torrent->group->name, $torrent->torrent->id);
        }
    }

    protected function getTorrentUrl($torrent) {
        $params = array(
            'action' => 'download',
            'id' => $torrent->torrent->id,
            'authkey' => $this->authKey,
            'torrent_pass' => $this->passKey
        );

        $url = 'https://what.cd/torrents.php?'.http_build_query($params);
        return $url;
    }

    protected function applyRules($torrent) {
        return true;

        if($torrent->group->categoryName !== 'Music') {
            return false;
        }

        if($torrent->group->year !== 2014) {
            return false;
        }

        if($torrent->torrent->encoding !== 'V0 (VBR)') {
            return false;
        }

        return true;
    }

    protected function watch() {
        while(true) {
            $torrentId = ($this->latestTorrentId + 1);
            $torrent = $this->client->api('torrent', array('id' => $torrentId));
            
            if($torrent) {
                $this->processTorrent($torrent);
                $this->latestTorrentId = $torrentId;
            }
        }
    }

}

$scrape = new Scrape($config);
$scrape->start();

?>
