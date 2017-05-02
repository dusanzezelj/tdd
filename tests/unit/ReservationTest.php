<?php

use App\Concert;
use App\Exceptions\NotEnoughTicketsException;
use App\Order;
use App\Reservation;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ReservationTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    function calculating_the_total_cost()
    {
        $tickets = collect([
            (object)['price' => 1200],
            (object)['price' => 1200],
            (object)['price' => 1200]
        ]);
        //iznad je refaktorisan kod, isklkjucuje se koriscenje baze
        /*$concert = factory(Concert::class)->create(['ticket_price' => 1200])->addTickets(3);
        $tickets = $concert->findTickets(3);*/

        $reservation = new Reservation($tickets);
        $this->assertEquals(3600, $reservation->totalCost());
    }

    /** @test */
    function reserved_ticekts_are_released_when_a_reservation_is_cancelled(){
        //jedan nacin
        /*$ticket1 = Mockery::mock(\App\Ticket::class);
        $ticket1->shouldReceive('release')->once();

        $ticket2 = Mockery::mock(\App\Ticket::class);
        $ticket2->shouldReceive('release')->once();

        $ticket3 = Mockery::mock(\App\Ticket::class);
        $ticket3->shouldReceive('release')->once();*/

        $tickets = collect([
            Mockery::mock(\App\Ticket::class)->shouldReceive('release')->once()->getMock(),
            Mockery::mock(\App\Ticket::class)->shouldReceive('release')->once()->getMock(),
            Mockery::mock(\App\Ticket::class)->shouldReceive('release')->once()->getMock()
            ]);

        $reservation = new Reservation($tickets);

        $reservation->cancel();
    }

    //sa kirscenjem spy umesto mockery
    /** @test */
    function reserved_ticekts_are_released_when_a_reservation_is_cancelled_spies(){
        $tickets = collect([
            Mockery::spy(\App\Ticket::class),
            Mockery::spy(\App\Ticket::class),
            Mockery::spy(\App\Ticket::class)
        ]);

        $reservation = new Reservation($tickets);

        $reservation->cancel();

        foreach ($tickets as $ticket){
            $ticket->shouldHaveReceived('release');
        }
    }


}