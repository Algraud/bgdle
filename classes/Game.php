<?php

namespace Bgdle;

use SQLite3;

class Game
{
    public int $id;
    public string $name;
    public int $year;
    public int $minplayers;
    public int $maxplayers;
    public int $minplaytime;
    public int $maxplaytime;
    public int $minage;
    public array $mechanics;
    public array $categories;
    public array $designers;
    public array $artists;
    public array $family;
    public string $publisher;
    public string $img;


    public function __construct($id, $name, $year, $minplayers, $maxplayers, $minplaytime, $maxplaytime, $minage,
                                $categories = [], $mechanics = [], $designers = [], $artists = [], $publisher, $family = [], $img = ""){
        $this->id = $id;
        $this->name = $name;
        $this->year = (int) $year;
        $this->minplayers = (int) $minplayers;
        $this->maxplayers = (int) $maxplayers;
        $this->minplaytime = (int) $minplaytime;
        $this->maxplaytime = (int) $maxplaytime;
        $this->minage = (int) $minage;
        $this->categories = $categories;
        $this->mechanics = $mechanics;
        $this->artists = $artists;
        $this->publisher = (string) $publisher;
        if(!is_array($family)){
            $family = [];
        }
        $this->family = $family;
        $this->designers = $designers;
        if($img == null){
            $img = "";
        }
        $this->img = $img;
    }

    public function getInsertableGame(string $patern): array|string
    {
        $search = array("id,", "name,", "year,", "minplayers,", "maxplayers,", "minplaytime,", "maxplaytime,",
                            "minage,", "categories,", "mechanics,", "designers,", "artists,", "publisher,","families,", "img", "description");
        $replace = array("'" . $this->id . "',", "'" . SQLite3::escapeString($this->name) . "',", "'" . $this->year . "',",
                            "'" . $this->minplayers . "',", "'" . $this->maxplayers . "',", "'" . $this->minplaytime . "',",
                            "'" . $this->maxplaytime . "',", "'" . $this->minage . "',",
                            "'" . SQLite3::escapeString(implode(';',$this->categories)) . "',",
                            "'" . SQLite3::escapeString(implode(';',$this->mechanics)) . "',",
                            "'" . SQLite3::escapeString(implode(';',$this->designers)) . "',",
                            "'" . SQLite3::escapeString(implode(';',$this->artists)) . "',",
                            "'" . SQLite3::escapeString($this->publisher) . "',",
                            "'" . SQLite3::escapeString(implode(';',$this->family)) . "',",
                            "'" . SQLite3::escapeString($this->img) . "'");
        //echo $replace;
        return str_replace($search, $replace, $patern);
    }


}
