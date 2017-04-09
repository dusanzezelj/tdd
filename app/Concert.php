<?php

namespace App;

use App\Exceptions\NotEnoughTicketsException;
use Illuminate\Database\Eloquent\Model;

class Concert extends Model
{
    protected $guarded = [];
    protected $dates = ['date'];

    public function getFormattedDateAttribute()
    {
        return $this->date->format('F j, Y');
    }

    public function getformattedStartTimeAttribute()
    {
        return $this->date->format('g:ia');
    }

    public function getTicketPriceInDollarsAttribute(){
        return number_format($this->ticket_price /100, 2);
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function orderTickets($email, $ticketQuantity)
    {
        $tickets = $this->findTickets($ticketQuantity);

        $order = $this->orderTickets($email, $tickets);
        return $order;
    }

    public function addTickets($quantity){
        foreach (range(1, $quantity) as $i){
            $this->tickets()->create([]);
        }

        return $this;
    }

    public function ticketsRemaining(){
        return $this->tickets()->available()->count();
    }

    public function hasOrderFor($email)
    {
        return $this->orders()->where('email', $email)->count() > 0;
    }

    public function ordersFor($email)
    {
        return $this->orders()->where('email', $email)->get();
    }

    public function createOrder($email, $tickets)
    {
        $order = $this->orders()->create([
            'email' => $email,
            'amount' => $tickets->count() * $this->ticket_price
        ]);

        /*foreach (range(1, $ticketQuantity) as $i){
            $order->tickets()->create([]);
        }*/
        foreach ($tickets as $ticket){
            $order->tickets()->save($ticket);
        }
        return $order;
    }

    public function findTickets($quantity)
    {
        $tickets = $this->tickets()->available()->take($quantity)->get();

        if($tickets->count() < $quantity){
            throw new NotEnoughTicketsException();
        }

        return $tickets;
    }
}
