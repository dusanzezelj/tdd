<?php

namespace App\Http\Controllers;

use App\Billing\PaymentFailedException;
use App\Billing\PaymentGateway;
use App\Concert;
use App\Exceptions\NotEnoughTicketsException;
use App\Reservation;
use Illuminate\Http\Request;

use App\Http\Requests;

class ConcertOrdersController extends Controller
{
    private $paymentGateway;

    public function __construct(PaymentGateway $paymentGateway)
    {
        $this->paymentGateway = $paymentGateway;
    }

    public function store($concertId)
    {
        var_dump($concertId);
        $concert = Concert::published()->findOrFail($concertId);
        $this->validate(request(), [
            'email' => 'required'
        ]);
        try {
            $tickets = $concert->reserveTickets(request('ticket_quantity'));

            $reservation = new Reservation($tickets);
            $ticketQuantity = request('ticket_quantity');
            $amount = $tickets->sum('price');
            $token = request('payment_token');
            $this->paymentGateway->charge($reservation->totalCost(), $token);

            $order = $concert->createOrder(request('email'), $tickets);
            var_dump($order);
            //$order = $concert->orderTickets(request('email'), request('ticket_quantity'));


            //$order = $concert->orders()->create(['email' => request('email')]);

            return response()->json($order, 201);
        } catch (PaymentFailedException $e){
            $reservation->cancel();
            return response()->json([], 422);
        } catch (NotEnoughTicketsException $e){
            return response()->json([], 422);
        }
    }
}
