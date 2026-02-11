<?php

namespace Bgdle;

class BGdle
{
    public Ranker $RANKER;
    public Database $DB;
    public Collector $COLLECTOR;
    public Comparer $COMPARER;

    private array|false $config;

    public function __construct(){

        $this->config = parse_ini_file("config.ini", true);
        $this->DB = new Database($this->config);
        $this->RANKER = new Ranker();
        $this->COLLECTOR = new Collector($this->DB, $this->config);
        $this->COMPARER = new Comparer();
    }

    public function expandGames(bool $useFile, int $startCount = 0, int $maxCount = 50):void{
        $countFile = "csvValue.txt";
        if($useFile){
            if(file_exists($countFile)){
                $content = file_get_contents($countFile);
                if($content !== false){
                    $startCount = (int)$content;
                }
            }
            if ($startCount > 5000){
                return;
            }
        }

        $csv = array();
        $lines = file('boardgames_ranks.csv', FILE_IGNORE_NEW_LINES);
        if($startCount >= count($lines)){
            return;
        }
        $count = 0;
        foreach ($lines as $key => $value)
        {
            if ($key < $startCount){
                continue;
            }
            $csv[$key] = str_getcsv($value);
            echo $count . ": ID-" .$csv[$key][0] . " Rank-".$csv[$key][3]. " " . $csv[$key][1] . "\n";
            //print_r($csv[$key]);
            $this->COLLECTOR->getGame($csv[$key][0],false,false,true);
            $count++;
            if($count >= $maxCount){
                break;
            }
        }
        if($useFile && file_put_contents($countFile, ($startCount+$count)) === false){
            echo "Error saving number to file.";
        }
    }

    public function setup(): void
    {
        $gameIds = $this->RANKER->rankGames();
        $games = $this->COLLECTOR->populateList($gameIds);
        $this->randomizeOrder();
        $dailyID = $this->RANKER->pickDaily($gameIds);
        $daily = $this->getGame($dailyID);
        $this->DB->insertDaily($daily, date("Ymd"));
        $this->setupFreePlay($games);
        $this->postStats(date('Ymd',strtotime("-1 days")));
        $this->DB->deleteRecords();
        $this->DB->deleteTokens();
        $this->setupDoku();

        $this->expandGames(true);

    }

    public function setupDoku($games = array(), $predefinedCats=[], $insert = true, $echo = true):void
    {
        echo "Starting Doku...\n";
        if(count($games)<1) {
            $games = $this->DB->getAllGames();
        }
        $preTopCats = $predefinedCats;
        $preSideCats = [];
        if(count($predefinedCats) > 3){
            $preSideCats = array_slice($predefinedCats, 3);
            $preTopCats = array_slice($predefinedCats, 0, 3);
        }
        $used = [];
        $dailyDokuRow = $this->DB->getCategories(3,[],$preTopCats);
        $used = array_merge($used, $dailyDokuRow);
        $dailyDokuCol = $this->DB->getCategories(3, $used, $preSideCats);
        $used = array_merge($used, $dailyDokuCol);
        $i=0;
        echo "\n Top Categories: 0. {$dailyDokuRow[0]['value']} 1. {$dailyDokuRow[1]['value']} 2. {$dailyDokuRow[2]['value']} \n";
        echo "\n Side Categories: 0. {$dailyDokuCol[0]['value']} 1. {$dailyDokuCol[1]['value']} 2. {$dailyDokuCol[2]['value']} \n";
        $result =  $this->COMPARER->validateCategories($dailyDokuRow, $dailyDokuCol, $games);
        while(!($result['result']) ){
            if(count($preSideCats) > $result['pos']){
                echo "Provided Cats impossible";
                return;
            }
            $i++;
            echo $i . "\n";
            $newCat = $this->DB->getCategories(count($result['pos']),$used);
            $used = array_merge($used, $newCat);
            $newAr = [];
            for($i=0; $i < count($result['pos']);$i++){
                $newAr[$result['pos'][$i]] = $newCat[$i];
            }
            $dailyDokuCol = array_replace($dailyDokuCol, $newAr);
            if($dailyDokuCol === false||count($dailyDokuCol) < 3){
                echo "\nREDO!!!!!!!!!!!\ns";
                $used = [];
                $dailyDokuRow = $this->DB->getCategories(3, [], $preTopCats);
                $used = array_merge($used, $dailyDokuRow);
                $dailyDokuCol = $this->DB->getCategories(3, $dailyDokuRow, $preSideCats);
                $used = array_merge($used, $dailyDokuCol);
            }
            //$used = array_merge($used, $dailyDokuCol);

            echo "\n Top Categories: 0. {$dailyDokuRow[0]['value']} 1. {$dailyDokuRow[1]['value']} 2. {$dailyDokuRow[2]['value']} \n";
            echo "\n Side Categories: 0. {$dailyDokuCol[0]['value']} 1. {$dailyDokuCol[1]['value']} 2. {$dailyDokuCol[2]['value']} \n";
            $result = $this->COMPARER->validateCategories($dailyDokuRow, $dailyDokuCol, $games);
        }
        echo "\nResult \n\n";
        //print_r($result);
        if($echo){
            for ($y=0; $y<3;$y++){
                for ($x=0; $x<3;$x++){
                    echo "{$dailyDokuRow[$x]['value']} - {$dailyDokuCol[$y]['value']}\n";
                    $key = $y*3+$x;
                    foreach ($result['picks'][$key] as $pick) {
                        echo " - {$pick->id} : {$pick->name}\n";
                    }
                }
            }
        }
        if($insert && $this->DB->insertDailyDoku(array_merge($dailyDokuRow, $dailyDokuCol), date("Ymd"))) {
            for ($i = 0; $i < $result['gameReq']; $i++) {
                $step = $result['gameReq'];
                $grid = [];
                for ($gridNumber = 0; $gridNumber < 9; $gridNumber++) {
                    $grid[] = $result['picks'][$gridNumber][$i];
                }
                echo "adding system" . $i . " to record\n";
                //print_r($grid);
                $this->addDokuRecord(date("Ymd"), $grid, "system" . $i);
            }
        }
    }

    public function dailyTest($count): void
    {
        if(is_null($count)){
            $count=10;
        }
        $gameIds = $this->RANKER->rankGames();
        $games = $this->COLLECTOR->populateList($gameIds);
        echo "\n Ids " . count($gameIds) . " \n \n";
        echo "\n Games " . count($games) . " \n \n";
        for ($i = 0; $i < $count; $i++){
            $gameID = $this->RANKER->pickDaily($gameIds);
            $game = $this->getGame($gameID);
            $key = array_search($game->id, $gameIds);
            echo $i . ":  " . $game->id . " " . $game->name . " Rank:" . $key . "\n";
            if($key > 1000){
                echo "\n  over 1000 PROBLEM!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! \n \n";
                exit;
            }
            if($key === FALSE){
                echo "\n no key PROBLEM!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! \n \n";
                exit;
            }
        }
    }

    public function alter(): void
    {
        $this->DB->alterTable();
    }

    public function randomizeOrder(): void
    {
        $this->DB->updateRandomColumn();
    }

    public function stats(): void
    {
        $this->postStats("");
    }

    private function postStats($date): void
    {
        $body = array(
            'username' => $this->config["discord"]["username"]
        );
        $body['content'] = $this->gatherStats($date);
        $ch = curl_init($this->config["discord"]["url"]);
        curl_setopt_array($ch, array(
            CURLOPT_POST => TRUE,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
            CURLOPT_POSTFIELDS => json_encode($body)
        ));
        $response = curl_exec($ch);

        if($response === FALSE){
            die(curl_error($ch));
        }
    }

    private function gatherStats($date): string{
        $records = json_decode($this->getRecords("", "", true, $date), true);
        $dokuRecords = json_decode($this->getDokuStats($date, ""));
        $total = 0;
        $loggedTotal = 0;
        $avgGuess = 0;
        $avgHint = 0;
        if($records !== null) {
            foreach ($records as $record) {
                $total++;
                if ($record['userID'] !== 0) {
                    $loggedTotal++;
                }
                $avgGuess += $record['guesses'];
                $avgHint += $record['hints'];
            }
        }
        if($total !== 0) {
            $avgHint = round(($avgHint / $total), 2);
            $avgGuess = round(($avgGuess / $total), 2);
        }
        if($date === ""){
            $prettyDate = "{Forever}";
        } else {
            $prettyDate = date("Y-m-d", strtotime($date));
        }
        $accuracy = 0;
        if($dokuRecords->total > 0) {
            $totalDokuGuesses = 0;
            foreach ($dokuRecords->totals as $totalGuess){
                $totalDokuGuesses += $totalGuess;
            }
            $accuracy = $totalDokuGuesses / $dokuRecords->total / 0.09;
        }
        $text = "On ".$prettyDate.", There were ".$total." games played. ".$loggedTotal." of them were by logged in players.";
        $text .= "\nAverage guesses(and hints): ".$avgGuess."(".$avgHint.").";
        $text .= "\n____________________";
        $text .= "\nRegarding Doku, There were ".$dokuRecords->total." games played. Accuracy was ". round($accuracy,2) ."%.";
        return $text;
    }

    public function updateGames(): void
    {
        $gameIds = $this->RANKER->rankGames();
        foreach ($gameIds as $id){
            $this->COLLECTOR->getGame($id, true, false, true);
        }
    }

    public function updateDBGames(): void
    {
        $games = $this->DB->getAllGames();
        foreach ($games as $game){
            $this->COLLECTOR->getGame($game->id, true, true, true);
        }
    }

    public function getGame(int $id): false|Game
    {
        return $this->COLLECTOR->getGame($id);
    }
    public function setupFreePlay(array $gameList):void{
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
    public function attemptDokuGuess(int $id, string $date, int $pos): false|string{
        $response = new class{};
        $game = $this->COLLECTOR->getGame($id);
        if(!$game){
            $response->error = "Game not found in server and bgg. Game ID tried: " . $id;
            return json_encode($response);
        }
        $daily = $this->DB->getDailyDoku($date);
        if(!$daily){
            $response->error = "Answer is missing. Something went wrong... Date tried: " . $date;
            return json_encode($response);
        }
        return json_encode($this->COMPARER->compareDoku($daily, $pos, $game, $response));
    }

    public function getDokuCategories(string $date){
        return json_encode($this->DB->getDailyDoku($date));
    }

    public function getGameList(): false|string{
        return json_encode($this->COLLECTOR->getGameList());
    }

    public function getSimilarGames(string $name, bool $sort = false, string $attr = "", string $value = ""): false|string{
        return json_encode($this->DB->getSimilarName($name, $sort, $attr, $value));
    }

    public function getImageLink(string $id):false|string{
        if(!is_numeric($id)){
            return false;
        }
        $game = $this->getGame($id);
        if($game->img != ""){
            return $game->img;
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

    public function attemptLogin(string $isSignup, string $username, string $password, string $email): false|string
    {
        $obj = new \stdClass();
        $obj->token = "";
        if($isSignup === "true"){
            $password = password_hash($password, PASSWORD_DEFAULT);
            if($this->DB->insertUser($username, $password, $email)){
                session_start();
                $obj->username = $username;
                $obj->userID = $this->DB->loggedInUser;
                $obj->token = $this->saveToken($this->DB->loggedInUser);

                $obj->session = session_id();
            }
        }
        if($this->DB->checkLogins($username, $password)){
            session_start();
            $obj->username = $username;
            $obj->userID = $this->DB->loggedInUser;
            $obj->token = $this->saveToken($this->DB->loggedInUser);

            $obj->session = session_id();
        }
        return json_encode($obj);
    }

    public function saveToken(int $userID): string
    {
        $token = bin2hex(random_bytes(8).$userID.random_bytes(8));
        $this->DB->insertToken($token, $userID);
        return $token;
    }

    public function checkToken(string $token, string $id): bool|string
    {
        $obj = new \stdClass();
        if($this->DB->checkToken($token, (int) $id)){
            $obj->status = true;
            $obj->username = $this->DB->getUsername($id);
        }
        return json_encode($obj);
    }

    public function addRecord(int $updateID, string $token, string $userID, string $date, int $guesses, int $hints): bool|int
    {
        if($token !== "" && $userID !== "" && $this->DB->checkToken($token, (int) $userID)){
            $this->DB->loggedInUser = $userID;
        }
        if($updateID !== 0){
            return $this->DB->updateRecord($updateID, $userID, $date);
        }
        return $this->DB->insertRecord($date, $guesses, $hints, $token, $userID);
    }

    public function addDokuRecord(string $date, $grid, $token): bool|string{
        $response =  new \stdClass();
        $response->added = false;

        $response->date = $date;
        $response->grid = $grid;
        $response->token = $token;
        $response->success = $this->DB->insertRecordDoku($date, $grid, $token);
        return json_encode($response);
    }

    public function getRecords(string $token, string $userID, bool $all=false, string $date=""): bool|string
    {
        if($all || ($token !== "" && $userID !=="" && $this->DB->checkToken($token, (int) $userID))){
            if($userID ==""){
                $user = 0;
            } else {
                $user = (int) $userID;
            }
            return json_encode($this->DB->getRecords($user, $all, $date));
        }
        return false;
    }

    public function getDokuStats($date, $token){
        $response =  new \stdClass();
        $response->date = $date;
        $response->token = $token;
        $records = $this->DB->getDokuRecords($date);
        $total = count($records);
        $totals = [];
        $answers = [];
        foreach ($records as $record){
            for ($y = 1; $y <4; $y++){
                for ($x = 1; $x < 4; $x++){
                    $key = "doku".$y.$x;
                    if($record[$key] !== false){
                        $fullGame = $record[$key];
                        if(isset($answers[$key]) && isset($answers[$key][$fullGame->id])){
                            $answers[$key][$fullGame->id]->count += 1 ;
                        } else {
                            $smallGame = new \stdClass();
                            $smallGame->id = $fullGame->id;
                            $smallGame->name = $fullGame->name;
                            $smallGame->year = $fullGame->year;
                            $smallGame->publisher = $fullGame->publisher;
                            $smallGame->count = 1;
                            if(substr($record['ip'],0,-1) === "system"){
                                $smallGame->count = 0;
                            }
                            $smallGame->my = false;
                            $answers[$key][$fullGame->id] = $smallGame;
                        }
                        if($record['ip'] === $token){
                            $answers[$key][$fullGame->id]->my = true;
                        }
                        if(substr($record['ip'],0,-1) !== "system") {
                            if(!isset($totals[$key])){
                                $totals[$key] = 0;
                            }
                            $totals[$key] += 1;
                        }
                    }
                }
            }
            if(substr($record['ip'],0,-1) === "system"){
                $total--;
            }
        }
        $response->total = $total;
        $response->totals = $totals;
        $response->answers = $answers;



        //$response->records = $records;
        return json_encode($response);
    }

    public function addCategory(string $type, string $value, string $description){
        if(in_array($type, ["special", "categories", "mechanics", "designers", "artists", "families"]) && $value !== ""){
            $this->DB->insertCategory($type, $value, 1, $description);
        }
    }

    public function testCategories($args){
        $ids = [];
        foreach ($args as $arg){
            if(is_numeric($arg)){
                $ids[] =$arg;
            }
        }
        if(count($ids) > 6 || count($ids) < 1){
            echo "There must be between 1 and 6 numeric arguements";
        }
        $this->setupDoku([],$ids, false);
    }
}