<?php

namespace Bgdle;

use SQLite3;

class Database
{
    private string $FILENAME = "gameData.sqlite";
    public SQLite3 $DB;
    public function __construct(){
        $this->DB = new SQLite3($this->FILENAME);
        $this->DB->enableExceptions(true);
        $this->createTables();
    }

    private function createTables(): void
    {

        $this->DB->query('CREATE TABLE IF NOT EXISTS "games" (
            "id" INTEGER PRIMARY KEY NOT NULL,
            "name" VARCHAR,
            "year" INTEGER,
            "minplayers" INTEGER,
            "maxplayers" INTEGER,
            "minplaytime" INTEGER,
            "maxplaytime" INTEGER,
            "minage" INTEGER,
            "categories" VARCHAR,
            "mechanics" VARCHAR,
            "designers" VARCHAR,
            "artists" VARCHAR,
            "publisher" VARCHAR,
            "description" VARCHAR
        )');
        $this->DB->query('CREATE TABLE IF NOT EXISTS "daily" (
            "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            "date" VARCHAR,
            "gameID" INTEGER
        )');
    }

    public function insertGame(Game $game, bool $force = false): void
    {
        $result = $this->DB->query('SELECT * FROM games WHERE id="'.$game->id.'"');
        if($force || !$result->fetchArray()){ //if no row to fetch
            $pattern = "(id, name, year, minplayers, maxplayers, minplaytime, maxplaytime, minage, categories, mechanics, designers, artists, publisher)";
            $values = $game->getInsertableGame($pattern);
            $sql = 'INSERT OR REPLACE INTO games '.$pattern.' VALUES '.$values;
            //echo $sql;
            $this->DB->query($sql);

        }
    }
    public function getGame($id): Game|false{
        $result = $this->DB->query('SELECT * FROM games WHERE id="'.$id.'"');
        if(!($gameSql = $result->fetchArray())) { //if no row to fetch
            return false;
        }
        return $this->createGameFromRow($gameSql);
    }

    public function getAllGames(): array|false{
        $result = $this->DB->query('SELECT * FROM games');
        $games = [];
        while($gameSql = $result->fetchArray()){
            $games[] = $this->createGameFromRow($gameSql);
        }
        //print_r($games);
        return $games;
    }

    private function createGameFromRow($row):Game{
        return new Game($row['id'],$row['name'],$row['year'],$row['minplayers'],$row['maxplayers'],
            $row['minplaytime'],$row['maxplaytime'],$row['minage'],
            explode(";", $row['categories']),explode(";",$row['mechanics']),
            explode(";",$row['designers']), explode(";", $row['artists']),
            $row['publisher'], $row['description']);
    }

    public function getDailyGame($date):Game|false{
        $result = $this->DB->query('SELECT * FROM daily WHERE date="'.$date.'"');
        if(!($dailySql = $result->fetchArray())) { //if no row to fetch
            return false;
        }
        return $this->getGame($dailySql['gameID']);
    }
    public function insertDaily(Game $game, $date):void{
        $result = $this->DB->query('SELECT * FROM daily WHERE date="'.$date.'"');
        if(!$result->fetchArray()){ //if no row to fetch
            $pattern = "(date, gameID)";
            $values = "(". $date . ", " . $game->id . ")";
            $sql = 'INSERT INTO daily '.$pattern.' VALUES '.$values;
            //echo $sql;
            $this->DB->query($sql);

        }
    }

    public function getSimilarName(string $name, string $attr, string $value): array{
        $name = SQLite3::escapeString($name);
        $sql = "SELECT * FROM games WHERE name LIKE '%" . $name . "%'";
        if($attr !== ""){
            $value = SQLite3::escapeString($value);
            if($attr === "categories" || $attr === "mechanics" || $attr === "designers" || $attr === "artists"){
                $sql .= "AND " . $attr . " LIKE '%" . $value . "%'";
            }
            else {
                $sql .= "AND " . $attr . "='" . $value . "'";
            }
        }
        $sql .= " LIMIT 10";
        $result = $this->DB->query($sql);
        $games = [];
        while($gameSql = $result->fetchArray()){
            $games[] = $this->createGameFromRow($gameSql);
        }
        //print_r($games);
        return $games;
    }
}