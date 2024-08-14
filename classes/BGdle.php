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
        $this->COLLECTOR = new Collector($this->DB);
        $this->COMPARER = new Comparer();
    }

    public function setup(): void
    {
        $gameIds = $this->RANKER->rankGames();
        $games = $this->COLLECTOR->populateList($gameIds);
        $this->DB->updateRandomColumn();
        $daily = $this->RANKER->pickDaily($games);
        $this->DB->insertDaily($daily, date("Ymd"));
        $this->setupFreePlay($games);
        $this->postStats(date('Ymd',strtotime("-1 days")));
        $this->DB->deleteRecords();
        $this->DB->deleteTokens();
    }

    public function alter(){
        $this->DB->alterTable();
    }

    public function stats(){
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
        return "On ".$prettyDate.", There were ".$total." games played. ".$loggedTotal." of them were by logged in players. 
        Average guesses(and hints): ".$avgGuess."(".$avgHint.").";
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

    private function saveToken(int $userID): string
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
            $obj->session = session_id();
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

    public function getRecords(string $token, string $userID, bool $all=false, string $date=""): bool|string
    {
        if($all || ($token !== "" && $userID !=="" && $this->DB->checkToken($token, (int) $userID))){
            if($userID ==""){
                $userID =0;
            }
            return json_encode($this->DB->getRecords($userID, $all, $date));
        }
        return false;
    }
}