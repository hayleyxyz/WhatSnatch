<?php

require 'Client.php';
require 'Log.php';

// load config
$config = json_decode(file_get_contents('config.json'));

class Scrape {

    protected $config;
    protected $client;
    protected $latestTorrentId;

    public function __construct($config) {
        $this->config = $config;

        $this->client = new Client();
    }

    public function start() {
        Log::debug('Logging in');

        $this->client->login($this->config->user->username, $this->config->user->password);

        $this->getLatestTorrent();
    }

    protected function getLatestTorrent() {
        Log::debug('Finding latest torrent');

        $index = $this->client->api('browse');
        $group = current($index->results);

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
        }
        else {
            Log::debug('Torrent not eligible: %s (#%d)', $torrent->group->name, $torrent->torrent->id);
        }
    }

    protected function applyRules($torrent) {
        if($torrent->group->year !== 2014) {
            return false;
        }

        if($torrent->torrent->format !== 'MP3' || $torrent->torrent->format !== 'V0 (VBR)') {
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
