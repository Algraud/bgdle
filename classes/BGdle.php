<?php

namespace Bgdle;

class BGdle
{
    public Ranker $RANKER;
    public Database $DB;
    public Collector $COLLECTOR;
    public Comparer $COMPARER;

    public string $sessionUsername = "";
    public int $userID = 0;
    public function __construct(){
        $this->DB = new Database();
        $this->RANKER = new Ranker();
        $this->COLLECTOR = new Collector($this->DB);
        $this->COMPARER = new Comparer();
        if(isset($_SESSION['username'])){
            $this->sessionUsername = $_SESSION['username'];
        }
        if(isset($_SESSION['userID'])){
            $this->userID = $_SESSION['userID'];
        }
    }

    public function setup(): void
    {
        $gameIds = $this->RANKER->rankGames();
        $games = $this->COLLECTOR->populateList($gameIds);
        $daily = $this->RANKER->pickDaily($games);
        $this->DB->insertDaily($daily, date("Ymd"));
        $this->setupFreePlay($games);
        $this->postStats(date('Ymd',strtotime("-1 days")));
        $this->DB->deleteRecords();
        $this->DB->deleteTokens();
    }

    public function stats(){
        $this->postStats("");
    }

    private function postStats($date): void
    {
        $body = array(
            'username' => 'BGdle'
        );
        $body['content'] = $this->gatherStats($date);
        $ch = curl_init('https://discord.com/api/webhooks/1196946643296206998/yAVi-vRTilB4ML29ziAi-JMgBrS6-W3blnvg4GGCbCLu2wFNeXyrN42DOlccu9HC4qSX');
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
        $records = json_decode($this->getRecords(session_id(), true, $date), true);
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
        if($isSignup === "true"){
            $password = password_hash($password, PASSWORD_DEFAULT);
            if($this->DB->insertUser($username, $password, $email)){
                session_start();
                $_SESSION['username'] = $username;
                $_SESSION['userID'] = $this->DB->loggedInUser;
                $obj->token = $this->saveToken($this->DB->loggedInUser);
                $obj->session = $this->saveToken($this->DB->loggedInUser);
            }
        }
        if($this->DB->checkLogins($username, $password)){
            session_start();
            $_SESSION['username'] = $username;
            $_SESSION['userID'] = $this->DB->loggedInUser;
            $this->saveToken($this->DB->loggedInUser);
        }
        return json_encode($obj);
    }

    private function saveToken(int $userID): string
    {
        $token = bin2hex(random_bytes(8).$userID.random_bytes(8));
        $this->DB->insertToken($token, $userID);
        return $token;
    }

    public function getUsername(string $session): string
    {
        $this->changeSession($session);
        return $this->sessionUsername;
    }

    public function checkToken(string $token, string $id): string
    {
        $obj = new \stdClass();
        if($this->DB->checkToken($token, (int) $id)){
            $obj->status = true;
            $obj->session = session_id();
            $obj->username = $this->DB->getUsername($id);
        }
        return $this->sessionUsername;
    }

    public function addRecord(int $updateID, string $session, string $date, int $guesses, int $hints): bool|int
    {
        if($session !== ""){
            $this->changeSession($session);
        }
        if($updateID !== 0){
            return $this->DB->updateRecord($updateID, $this->userID, $date);
        }
        $this->DB->loggedInUser = $this->userID;
        return $this->DB->insertRecord($date, $guesses, $hints, $_SERVER['REMOTE_ADDR'], $this->userID);
    }

    public function getRecords(string $session, bool $all=false, string $date=""): bool|string
    {
        if($all || $session !== ""){
            $this->changeSession($session);
            return json_encode($this->DB->getRecords($this->userID, $all, $date));
        }
        return false;
    }

    private function changeSession(string $session): void
    {
        if($session !== session_id()){
            session_unset();
            session_destroy();
            session_id($session);
            session_start();
            $this->sessionUsername = $_SESSION['username'];
            $this->userID = $_SESSION['userID'];
        }
    }
}