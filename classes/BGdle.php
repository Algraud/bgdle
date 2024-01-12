<?php

namespace Bgdle;

class BGdle
{
    public Ranker $RANKER;
    public Database $DB;
    public Collector $COLLECTOR;
    public Comparer $COMPARER;
    public function __construct(){
        $this->DB = new Database();
        $this->RANKER = new Ranker();
        $this->COLLECTOR = new Collector($this->DB);
        $this->COMPARER = new Comparer();
    }

    public function setup(): void
    {
        $gameIds = $this->RANKER->rankGames();
        $games = $this->COLLECTOR->populateList($gameIds);
        $daily = $this->RANKER->pickDaily($games);
        $this->DB->insertDaily($daily, date("Ymd"));
        $this->setupFreeplay($games);
    }

    public function updateGames(): void
    {
        $gameIds = $this->RANKER->rankGames();
        foreach ($gameIds as $id){
            $this->COLLECTOR->getGame($id, true);
        }
    }

    public function getGame(int $id): false|Game
    {
        return $this->COLLECTOR->getGame($id);
    }
    public function setupFreeplay(array $gameList):void{
        $max = count($gameList);
        for ($i=0; $i < $max; $i++){
            $daily = $this->RANKER->pickDaily($gameList);
            $this->DB->insertDaily($daily, $i);
        }
    }

    public function attemptGuess(int $id, string $date): false|string{
        $response = new class{};
        $game = $this->COLLECTOR->getGame($id);
        if(!$game){
            $response->error = "Game not found in server and bgg. Game ID tried: " . $id;
            return json_encode($response);
        }
        $daily = $this->DB->getDailyGame($date);
        if(!$daily){
            $response->error = "Answer is missing. Something went wrong... Date tried: " . $date;
            return json_encode($response);
        }
        return json_encode($this->COMPARER->compare($daily, $game, $response));
    }

    public function getGameList(): false|string{
        return json_encode($this->COLLECTOR->getGameList());
    }

    public function getSimilarGames(string $name, string $attr = "", string $value = ""): false|string{
        return json_encode($this->DB->getSimilarName($name, $attr, $value));
    }

    public function getImageLink(string $id):false|string{
        if(!is_numeric($id)){
            return false;
        }
        if (file_exists("img/" . $id . ".jpg")) {
            return "img/" . $id .".jpg";
        }

        if(file_exists("img/" . $id . ".png")) {
            return "img/" . $id .".png";
        }
        return false;
    }

    public function getDailyLetter(int $pos, string $date):string{
        $game = $this->DB->getDailyGame($date);
        return $game->name[$pos];
    }
}