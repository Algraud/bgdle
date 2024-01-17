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

    public function rankGames(): array
    {
        $pages = $this->getPages();
        $this->rankedList = $this->getGameIds($pages);
        return $this->rankedList;
    }

    private function getPages($firstPage = self::_FIRST_PAGE, $lastPage = self::_LAST_PAGE): array{
        $jsonPages = [];
        for ($i = $firstPage; $i <= $lastPage; $i++){
            $curlCon = curl_init();
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

    public function pickDaily(array $gameList): Game{
        $max = count($gameList);
        $rnd = random_int(0, $max-1);
        return $gameList[$rnd];
    }
}