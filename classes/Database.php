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
        //echo "Create Tables...";
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
            "randomorder" REAL,
            "img" VARCHAR,
            "families" VARCHAR                                
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
        $this->DB->query('CREATE TABLE IF NOT EXISTS "recordsDoku" (
            "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            "userID" INTEGER,
            "date" VARCHAR,
            "doku11" INTEGER,
            "doku12" INTEGER,
            "doku13" INTEGER,
            "doku21" INTEGER,
            "doku22" INTEGER,
            "doku23" INTEGER,
            "doku31" INTEGER,
            "doku32" INTEGER,
            "doku33" INTEGER,
            "ip" VARCHAR
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
        $this->DB->query('CREATE TABLE IF NOT EXISTS "categories" (
            "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            "type" VARCHAR,
            "value" VARCHAR,
            "weight" INTEGER,
            "description" VARCHAR
        )');
        $this->DB->query('CREATE TABLE IF NOT EXISTS "dailyDoku" (
            "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            "date" VARCHAR,
            "cat1" VARCHAR,
            "cat2" VARCHAR,
            "cat3" VARCHAR,
            "cat4" VARCHAR,
            "cat5" VARCHAR,
            "cat6" VARCHAR
        )');
        //echo "Done\n";
    }

    public function alterTable(){

        $this->DB->query('ALTER TABLE games
            ADD families VARCHAR,
            ADD img VARCHAR;
            ');

    }

    public function insertGame(Game $game, bool $force = false): void
    {
        $result = $this->DB->query('SELECT * FROM games WHERE id="'.$game->id.'"');
        if($force || !$result->fetchArray()){ //if no row to fetch
            $pattern = "(id, name, year, minplayers, maxplayers, minplaytime, maxplaytime, minage, categories, mechanics, designers, artists, families, publisher, img)";
            $values = $game->getInsertableGame($pattern);
            //echo $values . "\n";
            $sql = 'INSERT OR REPLACE INTO games '.$pattern.' VALUES '.$values;
            //echo $sql . "\n";
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
    public function getCategory($id): array|false{
        $result = $this->DB->query('SELECT * FROM categories WHERE id="'.$id.'"');
        if(!($catSql = $result->fetchArray())) { //if no row to fetch
            return false;
        }
        return ["type" => $catSql['type'], "value" => $catSql['value'], "description" => $catSql['description']];
    }
    public function getCategories($count, $except = [], $pre = []):array|false{
        if($count < 1){
            return false;
        }
        $doku = [];
        if(count($pre)>0){
            $count -= count($pre);
            $where = implode(',', $pre);
            $result = $this->DB->query('SELECT *, abs(random()) * weight AS weighted_random FROM categories WHERE id in ('.$where.') ORDER BY weighted_random ASC');
            if (!($catSql = $result->fetchArray())) { //if no row to fetch
                return false;
            }
            $doku[] = $catSql;
            for ($i = 1; $i < count($pre); $i++) {
                $doku[] = $result->fetchArray();
            }
        }
        $where = "";
        if(count($except) > 0){
            $exceptIDs = [];
            foreach ($except as $cat){
                $exceptIDs[] = $cat['id'];
            }
            $where = " WHERE id NOT IN (".SQLite3::escapeString(implode(',',$exceptIDs)).")";
        }
        if($count > 0) {
            $result = $this->DB->query('SELECT *, abs(random()) * weight AS weighted_random FROM categories' . $where . ' ORDER BY weighted_random ASC LIMIT ' . $count);
            if (!($catSql = $result->fetchArray())) { //if no row to fetch
                return false;
            }
            $doku[] = $catSql;
            for ($i = 1; $i < $count; $i++) {
                $doku[] = $result->fetchArray();
            }
        }
        return $doku;
    }

    public function getAllGames($random = false): array|false{
        $sql = 'SELECT * FROM games';
        if($random){
            $sql .= ' ORDER BY RANDOM()';
        }
        $result = $this->DB->query($sql);
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
            $row['publisher'], explode(";", $row['families']), $row['img']);
    }

    public function getDailyGame($date):Game|false{
        $result = $this->DB->query('SELECT * FROM daily WHERE date="'.$date.'"');
        if(!($dailySql = $result->fetchArray())) { //if no row to fetch
            return false;
        }
        return $this->getGame($dailySql['gameID']);
    }
    public function getDailyDoku($date):array|false{
        $result = $this->DB->query('SELECT * FROM dailyDoku WHERE date="'.$date.'"');
        if(!($dailySql = $result->fetchArray())) { //if no row to fetch
            return false;
        }
        $doku = array();
        for ( $i=1; $i < 7; $i++){
            $doku[] = $this->getCategory($dailySql["cat" . $i]);
        }
        return $doku;
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
    public function insertDailyDoku(array $cats, $date):bool{
        //print_r($cats);
        $result = $this->DB->query('SELECT * FROM dailyDoku WHERE date="'.$date.'"');
        if(!$result->fetchArray()){ //if no row to fetch
            $pattern = "(date, cat1, cat2, cat3, cat4, cat5, cat6)";
            $values = "(". $date . ", " . $cats[0]['id'] . ", " . $cats[1]['id'] . ", " . $cats[2]['id'] . ", " . $cats[3]['id'] . ", " . $cats[4]['id'] . ", " . $cats[5]['id'] . ")";
            $sql = 'INSERT INTO dailyDoku '.$pattern.' VALUES '.$values;
            //echo $sql;
            $this->DB->query($sql);

            $this->increaseWeightDoku($cats);
            return true;
        }
        return false;
    }
    public function increaseWeightDoku(array $categories){
        $sql = "UPDATE categories SET weight = (weight + 1) WHERE id IN (";
        $first = true;
        foreach ($categories as $category){
            if(!$first){
                $sql .= ", ";
            }
            $sql .= $category['id'];
            $first = false;
        }
        $sql .= ")";
        $this->DB->query($sql);
    }
    public function insertCategory(string $type, string $value, int $weight, string $description =""):void{
        $value = SQLite3::escapeString($value);
        $description = SQLite3::escapeString($description);
        $result = $this->DB->query('SELECT * FROM categories WHERE type="'.$type.'" AND value="'.$value.'"');
        if(!$result->fetchArray()) { //if no row to fetch
            $pattern = "(type, value, weight, description)";
            $values = "('" . $type . "', '" . $value . "', '" . $weight . "', '".$description."')";
            $sql = 'INSERT INTO categories ' . $pattern . ' VALUES ' . $values;
            echo $sql;
            $this->DB->query($sql);
        }
    }

    public function getSimilarName(string $name, bool $sort, string $attr, string $value): array{
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
        if($sort){
            $sql .= " ORDER BY";
            $sql .= " Case";
            $sql .= " WHEN name = '" . $name . "' THEN 1";
            $sql .= " WHEN name LIKE '" . $name . "%' THEN 2";
            $sql .= " WHEN name LIKE '%" . $name . "%' THEN 3";
            $sql .= " ELSE 4 END";
        }
        else {
            $sql .= " ORDER BY randomOrder";
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
        if($id === "0") {
            return "";
        }
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

    public function getDokuRecords($date, $onlySystem = false):array|bool{
        $sql = "SELECT * FROM recordsDoku WHERE date ='".$date."'";
        if($onlySystem){
            $sql .= " AND ip='system'";
        } else {
            $sql .= " AND ip<>'system'";
        }
        try{
            $result = $this->DB->query($sql);
        } catch (\Exception ){
            //echo "error";
            return false;
        }
        $records = [];
        while($recordSql = $result->fetchArray()){
            for ($y = 1; $y < 4; $y++){
                for ($x = 1; $x < 4; $x++){
                    $recordSql["doku".$y.$x] = $this->getGame($recordSql["doku".$y.$x]);
                }
            }
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

    public function insertRecordDoku( string $date, $grid, string $ip): bool|int
    {
        $result = $this->DB->query('SELECT * FROM recordsDoku WHERE ip="'.$ip.'" AND date="'.$date.'"');

        if(!$result->fetchArray()){ //if no row to fetch
            $pattern = "(userID, date";
            $values = "('', '" . $date . "'";
            $count = 0;
            for ($y = 1; $y < 4; $y++){
                for($x=1; $x<4; $x++){
                    $value = $grid[$count];
                    if($value instanceof Game){
                        $value = $value->id;
                    }
                    //echo $value;
                    $count++;
                    $pattern .= ", doku" . $y . $x;
                    $values .= ", '" . $value . "'";
                }
            }
            $pattern .= ", ip)";
            $values .= ", '".$ip."')";
            $sql = 'INSERT INTO recordsDoku '.$pattern.' VALUES '.$values;
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