<?php

namespace Bgdle;

class Comparer
{
    private Game $daily;
    private Game $guess;
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