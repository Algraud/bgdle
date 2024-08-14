<?php

namespace Bgdle;

use SQLite3;
use SQLiteException;

class Database
{
    public SQLite3 $DB;
    public int $loggedInUser = 0;

    private array|false $config;
    public function __construct($config){
        $this->config = $config;
        $this->DB = new SQLite3($this->config['database']['filename']);
        $this->DB->busyTimeout(1000);
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
            "description" VARCHAR,
            "randomorder" REAL                                
        )');
        $this->DB->query('CREATE TABLE IF NOT EXISTS "daily" (
            "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            "date" VARCHAR,
            "gameID" INTEGER
        )');
        $this->DB->query('CREATE TABLE IF NOT EXISTS "users" (
            "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            "username" VARCHAR,
            "password" VARCHAR,
            "email" VARCHAR,
            "banned" INTEGER
        )');
        $this->DB->query('CREATE TABLE IF NOT EXISTS "records" (
            "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            "userID" INTEGER,
            "date" VARCHAR,
            "guesses" INTEGER,
            "hints" INTEGER,
            "ip" VARCHAR
        )');
        $this->DB->query('CREATE TABLE IF NOT EXISTS "tokens" (
            "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            "userID" INTEGER,
            "token" VARCHAR,
            "date" INTEGER
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
            $row['publisher']);
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
        $sql .= " ORDER BY randomOrder LIMIT 10";
        $result = $this->DB->query($sql);
        $games = [];
        while($gameSql = $result->fetchArray()){
            $games[] = $this->createGameFromRow($gameSql);
        }
        //print_r($games);
        return $games;
    }

    public function insertUser(string $username, string $password, string $email):bool{
        $result = $this->DB->query('SELECT * FROM users WHERE username="'.$username.'"');
        if(!$result->fetchArray()){ //if no row to fetch
            $pattern = "(username, password, email)";
            $values = "('". $username . "', '" . $password . "', '" . $email . "')";
            $sql = 'INSERT INTO users '.$pattern.' VALUES '.$values;
            //echo $sql;
            try {
                $this->DB->query($sql);

            } catch (SQLiteException){
                //echo "error";
                return false;
            }
            $this->loggedInUser = $this->DB->lastInsertRowID();
            return true;
        }
        return false;
    }

    public function checkLogins(string $username, string $password): bool
    {
        $result = $this->DB->query('SELECT * FROM users WHERE username="'.$username.'"');
        $user = $result->fetchArray();
        if(!$user) {
            return false;
        }
        if(password_verify($password, $user['password'])){
            $this->loggedInUser = $user['id'];
            return true;
        }
        return false;
    }
    public function getUsername(string $id): string
    {
        $result = $this->DB->query('SELECT * FROM users WHERE id="'.$id.'"');
        $user = $result->fetchArray();
        if(!$user) {
            return "";
        }
        return $user['username'];
    }

    public function insertToken(string $token, int $userID){
        $pattern = "(userID, token, date)";
        $values = "('". $userID . "', '" . $token . "', '" . time() . "')";
        $sql = 'INSERT INTO tokens '.$pattern.' VALUES '.$values;
        //echo $sql;
        try {
            $this->DB->query($sql);

        } catch (SQLiteException){
            //echo "error";
            return false;
        }
        return true;
    }

    public function checkToken(string $token, int $id): bool
    {
        $result = $this->DB->query('SELECT * FROM tokens WHERE token="'.$token.'" AND userID="'.$id.'"');
        $user = $result->fetchArray();
        if(!$user) {
            return false;
        }
        $this->loggedInUser = $id;
        return true;
    }

    public function insertRecord( string $date, int $guesses, int $hints, string $ip, int $user): bool|int
    {
        if($this->loggedInUser !== 0){
            $result = $this->DB->query('SELECT * FROM records WHERE userID="'.$user.'" AND date="'.$date.'"');
        }
        else {
            $result = $this->DB->query('SELECT * FROM records WHERE ip="'.$ip.'" AND date="'.$date.'"');
        }
        if(!$result->fetchArray()){ //if no row to fetch
            $pattern = "(userID, date, guesses, hints, ip)";
            $values = "('". $user . "', '" . $date . "', '" . $guesses . "', '" . $hints . "', '" . $ip ."')";
            $sql = 'INSERT INTO records '.$pattern.' VALUES '.$values;
            //echo $sql;
            try {
                $this->DB->query($sql);

            } catch (\Exception ){
                //echo "error";
                return false;
            }
            return $this->DB->lastInsertRowID();
        }
        return false;
    }

    public function updateRecord(int $id, int $user, string $date): bool
    {
        $result = $this->DB->query('SELECT * FROM records WHERE userID="'.$user.'" AND date="'.$date.'"');
        if($result->fetchArray()){
            $this->deleteRecord($id);
            return false;
        }
        $sql = "UPDATE records SET userID='".$user."' WHERE id='".$id."'";
        try {
            $this->DB->query($sql);

        } catch (\Exception ){
            //echo "error";
            return false;
        }
        return true;
    }

    private function deleteRecord(int $id){
        $sql = "DELETE FROM records WHERE id='".$id."'";
        try {
            $this->DB->query($sql);

        } catch (\Exception ){
            //echo "error";
            return false;
        }
        return true;
    }

    public function getRecords(int $user, $all, $date): bool|array
    {
        $sql = "SELECT * FROM records";
        if(!$all){
            $sql .= " WHERE userID='".$user."'";
            if($date !== ""){
                $sql .= " AND date='".$date."'";
            }
        }
        else if($date !== ""){
            $sql .= " WHERE date='".$date."'";
        }
        $sql .= " ORDER BY date DESC";
        try {
            $result = $this->DB->query($sql);

        } catch (\Exception ){
            //echo "error";
            return false;
        }
        $records = [];
        while($recordSql = $result->fetchArray()){
            $records[] = $recordSql;
        }
        return $records;
    }

    public function deleteRecords(bool $allRecords=false): bool
    {
        $sql = "DELETE FROM records";
        if(!$allRecords){
            $sql .= " WHERE userID = '0'";
        }
        try {
            $this->DB->query($sql);

        } catch (\Exception ){
            //echo "error";
            return false;
        }
        return true;
    }
    public function deleteTokens(bool $allTokens=false): bool
    {
        $sql = "DELETE FROM tokens";
        if(!$allTokens){
            $cutOffDate = time() - (60*60*24*30);
            $sql .= " WHERE date < '".$cutOffDate."'";
        }
        try {
            $this->DB->query($sql);

        } catch (\Exception ){
            //echo "error";
            return false;
        }
        return true;
    }

    public function updateRandomColumn(): bool
    {
        $sql = "UPDATE games SET randomorder=Random()";
        try {
            $this->DB->query($sql);

        } catch (\Exception ){
            //echo "error";
            return false;
        }
        return true;

    }

}