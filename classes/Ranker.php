<?php

namespace Bgdle;

class Ranker
{
    private const _FIRST_PAGE =1;
    private const _LAST_PAGE = 10;
    private const _BGG_GAMELIST_URL = "https://boardgamegeek.com/browse/boardgame/page/";

    private array $rankedList;


    public function __construct(){
        $this->rankedList = [];
    }

    public function rankGames($firstPage = self::_FIRST_PAGE, $lastPage = self::_LAST_PAGE): array
    {
        $pages = $this->getPages($firstPage, $lastPage);
        print_r($pages);
        $this->rankedList = $this->getGameIds($pages);
        return $this->rankedList;
    }

    private function getPages($firstPage, $lastPage): array{
        echo "Getting Pages from ". $firstPage ." to ". $lastPage ."\n";
        $jsonPages = [];
        for ($i = $firstPage; $i <= $lastPage; $i++){
            $curlCon = curl_init();
            curl_setopt($curlCon, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
            curl_setopt($curlCon, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curlCon, CURLOPT_SSL_VERIFYPEER, true); // keep true in production
            curl_setopt($curlCon, CURLOPT_TIMEOUT, 30);
            curl_setopt($curlCon, CURLOPT_URL, self::_BGG_GAMELIST_URL . $i);
            curl_setopt($curlCon, CURLOPT_RETURNTRANSFER, true);

            $apiResponse = curl_exec($curlCon);
            if($apiResponse === false){
                echo 'Curl Error: ' . curl_error($curlCon);
            }
            curl_close($curlCon);
            $jsonPages[$i] = $apiResponse;
        }
        return $jsonPages;
    }
    
    private function getGameIds(array $pages): array
    {
        $gameIds = [];
        foreach ($pages as $page) {
            preg_match_all("/(?<=\bboardgame\/)[0-9]{1,6}\b/", $page,$gIds);
            $keep = true;
            foreach ($gIds[0] as $gId) {
                if($keep){
                    $gameIds[] = $gId;
                }
                $keep = !$keep;
            }
        }
        return $gameIds;
    }

    public function pickDaily(array $gameIds){
        $max = count($gameIds);
        $rnd = random_int(0, $max-1);
        return $gameIds[$rnd];
    }
    public function pickDailyDoku(array $catIds){
        $max = count($catIds);
        $doku = [];
        for ($i=0;$i<7;$i++){
            $rnd = random_int(0, $max-1);
            IF(in_array($catIds[$rnd],$doku, false)){
                $i--;
            } else {
                $doku = [$catIds[$rnd]];
            }
        }
        return $doku;
    }

    public function getRankedListOfIds(): array
    {
        return $this->rankedList;
    }
}