<?php
foreach (glob("classes/*.php") as $filename){
    include $filename;
}
$main = new \Bgdle\BGdle();
if(isset($_SERVER['argv'][1])){
    if($_SERVER['argv'][1]==="setup") {
        $main->setup();
    }
    if($_SERVER['argv'][1]==="alter") {
        $main->alter();
    }
    if(isset($_SERVER['argv'][2]) && $_SERVER['argv'][1] === "getGame") {
        print_r($main->getGame($_SERVER['argv'][2]));
    }
    if($_SERVER['argv'][1]==="update") {
        $main->updateGames();
    }
    if($_SERVER['argv'][1]==="stats") {
        $main->stats();
    }
    if($_SERVER['argv'][1]==="randomize") {
        $main->randomizeOrder();
    }
    if($_SERVER['argv'][1]==="dailyTest") {
        $main->dailyTest($_SERVER['argv'][2]);
    }
}
if(isset($_GET['request'])){
    echo $main->attemptGuess($_GET['game'], $_GET['date']);
}
if(isset($_GET['gamelist'])){
    echo $main->getGameList();
}
if(isset($_GET['search'])){
    echo $main->getSimilarGames($_GET['search']);
}
if(isset($_GET['searchadv'])){
    echo $main->getSimilarGames($_GET['searchadv'], $_GET['attr'], $_GET['value']);
}
if(isset($_GET['imglink'])){
    echo $main->getImageLink($_GET['imglink']);
}
if(isset($_GET['unveilTitle'])){
    echo $main->getDailyLetter($_GET["unveilTitle"], $_GET['date']);
}
if(isset($_GET['login'])){
    echo $main->attemptLogin($_GET['login'], $_GET['username'],
                            $_GET['password'], $_GET['email']);
}
if(isset($_GET['loginToken'])){
    echo $main->checkToken($_GET['loginToken'], $_GET['id']);
}
if(isset($_GET['record'])){
    echo $main->addRecord((int)$_GET['record'],$_GET['token'],$_GET['userID'],$_GET['date'],(int)$_GET['guesses'],(int)$_GET['hints']);
}
if(isset($_GET['records'])){
    echo $main->getRecords($_GET['records'], $_GET['userID']);
}