<?php

namespace Bgdle;

class Collector
{
    private array $GameList;

    private const _BGG_API = "https://boardgamegeek.com/xmlapi2/thing?";
    private Database $DB;

    private int $curlCount = 0;

    private array|false $config;

    public function __construct($db, $config){
        $this->GameList = array();
        $this->DB = $db;
        $this->retrieveList();
        $this->config = $config;
    }

    public function retrieveList(): void
    {
        $dbResponse = $this->DB->getAllGames();
        if($dbResponse){
            $this->GameList = $dbResponse;
        }
    }

    public function populateList(array $gameIds): array
    {

        $i = 1;
        foreach ($gameIds as $gameId){
            echo $i++ .": ";
            $game = $this->findGame($gameId);
            $tries = 1;
            while($tries < 5 && !$game){
                echo ".";
                sleep(3);
                $tries++;
                $game = $this->findGame($gameId);
            }
            if(!$game){
                echo "ohh no, something went wrong, needs an error. \n";

                continue;
            }
            //$this->GameList[] = $game;
            if($this->curlCount >=5){
                sleep(9);
                $this->curlCount = 0;
            }
        }
        return $this->GameList;
    }

    private function getGameCurl(int $id, $echo = true, $force = false): Game|false{
        if($echo) {
            echo "Start: " . $id;
        }
        $curlCon = curl_init();
        $param = "id=". $id ."&type=boardgame";
        $token = $this->config["bgg"]["token"];
        curl_setopt($curlCon, CURLOPT_URL, self::_BGG_API . $param);
        curl_setopt($curlCon, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlCon, CURLOPT_HEADER, array('Authorization: Bearer ' . $token));

        $apiResponse = curl_exec($curlCon);
        if($apiResponse === false){
            echo 'Curl Error: ' . curl_error($curlCon);
        }
        curl_close($curlCon);
        $xml = simplexml_load_string($apiResponse) or die("Error: Cannot create object from xml game ID:" . $id);
        if(!isset($xml->item)){
            return false;
        }
        $this->saveImage($id, $xml->item->image);
        $name = $xml->item->name->attributes()['value'];
        if($echo) {
            echo " " . $name . " ";
        }
        $year = $xml->item->yearpublished->attributes()['value'];
        $minplayers = $xml->item->minplayers->attributes()['value'];
        $maxplayers = $xml->item->maxplayers->attributes()['value'];
        $minplaytime = $xml->item->minplaytime->attributes()['value'];
        $maxplaytime = $xml->item->maxplaytime->attributes()['value'];
        $minage = $xml->item->minage->attributes()['value'];
        $categories = [];
        $mechanics = [];
        $designers = [];
        $artists = [];
        $publisher = "";
        foreach ($xml->item->link as $obj) {
            $type = $obj->attributes()['type'];
            switch ($type){
                case "boardgamecategory":
                    $categories[] = $obj->attributes()['value'];
                    break;
                case "boardgamemechanic":
                    $mechanics[] = $obj->attributes()['value'];
                    break;
                case "boardgamedesigner":
                    $designers[] = $obj->attributes()['value'];
                    break;
                case "boardgameartist":
                    $artists[] = $obj->attributes()['value'];
                    break;
                case "boardgamepublisher":
                    if($publisher === ""){
                        $publisher = $obj->attributes()['value'];
                    }
                    break;
                default:
                    break;
            }
        }
        if($echo) {
            echo " END\n";
        }
        $game = new Game($id, $name, $year, $minplayers, $maxplayers, $minplaytime, $maxplaytime, $minage, $categories,
            $mechanics, $designers, $artists, $publisher);
        $this->DB->insertGame($game, $force);
        return $game;
    }

    private function findGame(int $id, bool $echo = true, $force = false): false|Game
    {
        if($force || !($game = $this->DB->getGame($id))){
            $this->curlCount++;
            $game = $this->getGameCurl($id, $echo, $force);
            $this->GameList[] = $game;
            return $game;
        }
        if($echo) {
            echo $game->name . "\n";
        }
        return $game;
    }

    public function getGame(int $id, $force = false):Game|false{
        if($force){
            return $this->findGame($id, false, $force);
        }
        foreach ($this->GameList as $game){
            if($id === $game->id){
                return $game;
            }
        }
        return $this->findGame($id, false);
    }

    private function saveImage($id, $url): void
    {
        $type = substr($url, strrpos($url, "."));
        if(file_exists('img/' . $id . $type)){
            return;
        }
        $ch = curl_init($url);
        $fp = fopen('img/' . $id . $type, "wb");
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
    }

    public function getGameList(): array
    {
        return $this->GameList;
    }
}