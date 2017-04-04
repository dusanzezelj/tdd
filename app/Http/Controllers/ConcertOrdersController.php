<?php

namespace App\Http\Controllers;

use App\Billing\PaymentFailedException;
use App\Billing\PaymentGateway;
use App\Concert;
use App\Exceptions\NotEnoughTicketsException;
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
        $concert = Concert::published()->findOrFail($concertId);
        $this->validate(request(), [
            'email' => 'required'
        ]);
        try {
            $order = $concert->orderTickets(request('email'), request('ticket_quantity'));
            $ticketQuantity = request('ticket_quantity');
            $amount = $ticketQuantity * $concert->ticket_price;
            $token = request('payment_token');
            $this->paymentGateway->charge($amount, $token);

            //$order = $concert->orders()->create(['email' => request('email')]);

            return response()->json($order, 201);
        } catch (PaymentFailedException $e){
            return response()->json([], 422);
        } catch (NotEnoughTicketsException $e){
            return response()->json([], 422);
        }
    }
}
