<?php
use App\Concert;
use App\Exceptions\NotEnoughTicketsException;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ConcertTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    function can_get_formatted_date()
    {
        $concert = factory(Concert::class)->create([
            'date' => Carbon::parse('2016-12-01 8:00pm')
        ]);

        $this->assertEquals('December 1, 2016', $concert->formatted_date);
    }

    /** @test */
    function can_get_formatted_start_time(){
        $concert = factory(Concert::class)->create([
            'date' => Carbon::parse('2016-12-01 17:00:00')
        ]);
        //factory make se korisit ako necemo da sacuvamo u bazu
        $this->assertEquals('5:00pm', $concert->formatted_start_time);
    }

    /** @test */
    function can_get_ticket_price_in_dollars(){
        $concert = factory(Concert::class)->create([
            'ticket_price' => 6750
        ]);

        $this->assertEquals('67.50', $concert->ticket_price_in_dollars);
    }

    /** @test */
    function concerts_with_a_published_at_date_are_published(){
        $concertA = factory(Concert::class)->create([
            'published_at' => Carbon::parse('-1 week')
        ]);
        $concertB = factory(Concert::class)->create([
            'published_at' => Carbon::parse('-1 week')
        ]);
        $concertC = factory(Concert::class)->create([
            'published_at' => null
        ]);

        $published = Concert::published()->get();

        $this->assertTrue($published->contains($concertA));
        $this->assertTrue($published->contains($concertB));
        $this->assertFalse($published->contains($concertC));
    }

    /** @test */
    function can_order_concert_tickets()
    {
        $concert = factory(Concert::class)->create();

        $order = $concert->orderTickets('jane@example.com', 3);

        $this->assertEquals('jane@example.com', $order->email);
        $this->assertEquals(3, $order->tickets()->count());
    }

    /** @test */
    function can_add_tickets(){
        $concert = factory(Concert::class)->create();

        $concert->addTickets(50);

        $this->assertEquals(50, $concert->ticketsRemaining());
    }

    /** @test  */
    function tickets_remaining_does_not_include_tickets_associated_with_an_order(){
        $concert = factory(Concert::class)->create();

        $concert->addTickets(50);

        $concert->orderTickets('jane@example.com', 30);

        $this->assertEquals(20, $concert->ticketsRemaining());
    }

    /** @test  */
    function trying_to_purchase_more_tickets_than_remain_throws_an_exception(){
        $concert = factory(Concert::class)->create();
        $concert->addTickets(10);

        try{
            $concert->orderTickets('jane@example.com', 30);
        } catch (NotEnoughTicketsException $e) {
            $this->assertFalse($concert->hasOrderFor('jane@example.com'));
            /*$order = $concert->orders()->where('email', 'jane@example.com')->first();
            $this->assertNull($order);*/
            $this->assertEquals(10, $concert->ticketsRemaining());
            return;
        }

        $this->fail();
    }

    /** @test */
    function cannot_order_tickets_that_have_already_been_purchased()
    {
        $concert = factory(Concert::class)->create();
        $concert->addTickets(10);
        $concert->orderTickets('jane@example.com', 8);

        //act
        try {
            $concert->orderTickets('john@example.com', 3);
        } catch (NotEnoughTicketsException $e) {
            $order = $concert->orders()->where('email', 'john@example.com')->first();
            $this->assertNull($order);
            $this->assertEquals(2, $concert->ticketsRemaining());
            return;
        }
        $this->fail();
    }

    /** @test */
    function can_reserve_available_tickets()
    {
        $concert = factory(Concert::class)->create();
        $concert->addTickets(3);
        $this->assertEquals(3, $concert->ticketsRemaining());

        $reservedTickets = $concert->reserveTickets(2);

        $this->assertCount(2, $reservedTickets);
        $this->assertEquals(1, $concert->ticketsRemaining());
    }

    /** @test */
    function cannot_reserve_tickets_that_have_already_been_purchased(){
        $concert = factory(Concert::class)->create();
        $concert->addTickets(3);
        $concert->orderTickets('jane@example.com', 2);

        try{
            $concert->reserveTickets(2);
        } catch (NotEnoughTicketsException $e){
            $this->assertEquals(1, $concert->ticketsRemaining());
            return;
        }

        $this->fail("Error");//treba pametnija poruka
    }

    /** @test */
    function cannot_reserve_tickets_that_have_already_been_reserved(){
        $concert = factory(Concert::class)->create();
        $concert->addTickets(3);
        $concert->reserveTickets('jane@example.com', 2);

        try{
            $concert->reserveTickets(2);
        } catch (NotEnoughTicketsException $e){
            $this->assertEquals(1, $concert->ticketsRemaining());
            return;
        }

        $this->fail("Error");//treba pametnija poruka
    }

}