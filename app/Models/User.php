<?php

namespace App\Models;


class User
{
    public String $id;
    public String $name;
    public String $memberNumber;
    public Array $committees;

    public function __construct(String $id, String $name, String $memberNumer, Array $committees) {
        $this->id = $id;
        $this->name = $name;
        $this->memberNumber = $memberNumer;
        $this->committees = $committees;
    }
    
}
