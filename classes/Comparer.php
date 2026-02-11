<?php

namespace Bgdle;

class Comparer
{
    private Game $daily;
    private Game $guess;

    private array $compareList = [];
    public function __construct(){

    }

    public function compare(Game $dailyGame, Game $guessedGame, $response): object
    {
        if($dailyGame->id === $guessedGame->id){
            $response->win = true;
        }
        $this->daily = $dailyGame;
        $this->guess = $guessedGame;
        $response->year = $this->compareHigherLower('year');
        $response->minplayers = $this->compareHigherLower( 'minplayers');
        $response->maxplayers = $this->compareHigherLower( 'maxplayers');
        $response->minplaytime = $this->compareHigherLower( 'minplaytime');
        $response->maxplaytime = $this->compareHigherLower( 'maxplaytime');
        $response->minage = $this->compareHigherLower( 'minage');
        $response->categories = $this->compareArray('categories');
        $response->categoriesTotal = count($this->daily->categories);
        $response->mechanics = $this->compareArray('mechanics');
        $response->mechanicsTotal = count($this->daily->mechanics);
        $response->designers = $this->compareArray('designers');
        $response->designersTotal = count($this->daily->designers);
        $response->artists = $this->compareArray('artists');
        $response->artistsTotal = count($this->daily->artists);
        $response->publisher = $this->compareBoolean('publisher');
        return $response;

    }

    public function compareDoku(array $dailyCategories, string $pos, Game $guessedGame, $response): object
    {
        $posInt = (int)$pos;
        $posTop = ($posInt % 10)-1; //adjusted for no 0 in pos
        $posSide = ($posInt / 10)+2;
        $catTop = $dailyCategories[$posTop];
        $catSide = $dailyCategories[$posSide];
        $response->topCat = $catTop;
        $response->sideCat = $catSide;
        $mistake = false;
        if(!$this->compareCategory($catTop, $guessedGame)){
            $response->topMistake = true;
            $mistake = true;
        }
        if(!$this->compareCategory($catSide, $guessedGame)){
            $response->sideMistake = true;
            $mistake = true;
        }
        if($mistake){
            $response->result = false;
            return $response;
        }
        $response->result = true;
        return $response;

    }

    private function compareCategory(array $cat, Game $game){
        if($cat['type'] !== "special") {
            if (in_array($cat['value'], $game->{$cat['type']}, false)) {
                return true;
            }
        }
        else{

        }
        return false;
    }

    public function validateCategories(array $topCats, array $sideCats, array $games): array{ //need loops remade, do games once, then categories
        $picks = [];
        $gameReq = 2;
        $checksPassed = 0;
        foreach ($games as $game){
            if($checksPassed >= 9){
                break;
            }
            for($side=0;$side<3;$side++) {
                for($top=0;$top<3;$top++) {
                    $pos = $side * 3 + $top;
                    $topCat = $topCats[$top];
                    $sideCat = $sideCats[$side];
                    if(!isset($picks[$pos])){
                        $picks[$pos] = [];
                    } else if(count($picks[$pos]) >= $gameReq){
                        continue;
                    }
                    if(isset($this->compareList[$topCat['id']])){
                        if(is_array($this->compareList[$topCat['id']])){
                            if(isset($this->compareList[$topCat['id']][$sideCat['id']])){
                                $picks[$pos] = $this->compareList[$topCat['id']][$sideCat['id']];
                                if(count($picks[$pos]) >= $gameReq){
                                    $checksPassed++;
                                }
                            }
                        }
                    }
                    else if(isset($this->compareList[$sideCat['id']])){
                        if(is_array($this->compareList[$sideCat['id']])){
                            if(isset($this->compareList[$sideCat['id']][$topCat['id']])){
                                $picks[$pos] = $this->compareList[$sideCat['id']][$topCat['id']];
                            }
                        }
                    } else {
                        if ($this->compareCategory($topCat, $game) && $this->compareCategory($sideCat, $game)) {
                            $picks[$pos][] = $game;
                            if(count($picks[$pos]) >= $gameReq){
                                $checksPassed++;
                            }
                        }
                    }

                }
            }
        }
        if($checksPassed >= 9){
            echo "\n Compare Successful!!!\n";
            return [
                'result' => true,
                'picks' => $picks,
                'gameReq' => $gameReq
            ];
        }
        $failures = [];
        $failPos = [];
        for ($i=0; $i<9;$i++){
            if(count($picks[$i]) < $gameReq){
                $pos = $i / 3;
                if(in_array($pos, $failPos)){
                    continue;
                }
                $failPos[] = $pos;
                $failures[] = $sideCats[$pos];
            }
        }
        echo "\n Not enough Games !!!!!!!!!!!!!! ";
        return [
            'result' => false,
            'failures' => $failures,
            'pos' => $failPos,
            'gameReq' => $gameReq
        ];
    }

    private function compareHigherLower( string $attribute): int{
        $value = 0;
        if($this->daily->{$attribute} > $this->guess->{$attribute}) {
            $value++;
        } elseif($this->daily->{$attribute} < $this->guess->{$attribute}) {
            $value--;
        }
        return $value;
    }

    private function compareArray(string $attribute): array{
        $list = [];
        foreach ($this->guess->{$attribute} as $single){
            if(in_array($single, $this->daily->{$attribute}, false)){
                $list[] = $single;
            }
        }
        return $list;
    }

    private function compareBoolean(string $attribute): bool{
        return $this->daily->{$attribute} === $this->guess->{$attribute};
    }
}