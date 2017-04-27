<?php
use App\Concert;
use App\Exceptions\NotEnoughTicketsException;
use App\Order;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class OrderTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    function creating_an_order_from_tickets_email_and_amount()
    {
        $concert = factory(Concert::class)->create();
        $concert->addTickets(5);

        $order = Order::forTickets($concert->findTickets(3), 'john@example.com', 3600);

        $this->assertEquals('john@example.com', $order->email);
        $this->assertEquals(3, $order->ticketQuantity());
        $this->assertEquals(3600, $order->amount);
        $this->assertEquals(2, $concert->ticketsRemaining());
    }

    /** @test */
    function tickets_are_released_when_an_order_is_cancelled()
    {
        $concert = factory(Concert::class)->create();
        $concert->addTickets(10);
        $order = $concert->orderTickets('jane@example.com', 5);
        //pre conditions in arrange step, t obe sure that everything is fine
        $this->assertEquals(5, $concert->ticketsRemaining());

        $order->cancel();

        $this->assertEquals(10, $concert->ticketsRemaining());
        $this->assertNull(Order::find($order->id));
    }

    /** @test */
    function converting_to_an_array()
    {
        $concert = factory(Concert::class)->create();
        $concert->addTickets(5);
        $order = $concert->orderTickets('jane@example.com', 5);

        $result = $order->toArray();

        $this->assertEquals([
            'email' => 'jane@example.com',
            'ticket_quantity' => 5,
            'amount' => 10000
        ], $result);
    }
}