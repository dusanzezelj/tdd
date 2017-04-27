<?php
/**
 * Created by PhpStorm.
 * User: dusan
 * Date: 28.4.17.
 * Time: 00.01
 */

namespace App;


class Reservation
{
    public function __construct($tickets)
    {
        $this->tickets = $tickets;
    }

    public function totalCost()
    {
        return $this->tickets->sum('price');
    }
}