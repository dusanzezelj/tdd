<?php
/**
 * Created by PhpStorm.
 * User: dusan
 * Date: 14.4.17.
 * Time: 19.18
 */

function konvertuj(int $num): void{
    return strval($num);
}

$value = konvertuj(7);
var_dump($value);