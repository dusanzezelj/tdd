<?php

use App\Concert;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ViewConcertListingTest extends TestCase
{
    use DatabaseMigrations;
    /**
     * A basic functional test example.
     *
     * @return void
     */
    /** @test  */
    public function user_can_view_a_published_concert_listing()
    {
        //arrange

        /*
         * od verzije 5.3
         * $concert = factory(Concert::class)->states('published')->create([
            'title' => 'The Red Chord',
            'subtitle' => 'with animosity',
            'date' => Carbon::parse('December 12, 2016 8:00pm'),
            'ticket_price' => 3250,
            'venue' => 'the mosh pit',
            'venue_address' => '123 example lane',
            'city' => 'london',
            'state' => 'on',
            'zip' => '17916',
            'additional_information' => 'for tickets, call 111-111'
        ]);*/
        $concert = Concert::create([
            'title' => 'The Red Chord',
            'subtitle' => 'with animosity',
            'date' => Carbon::parse('December 12, 2016 8:00pm'),
            'ticket_price' => 3250,
            'venue' => 'the mosh pit',
            'venue_address' => '123 example lane',
            'city' => 'london',
            'state' => 'on',
            'zip' => '17916',
            'additional_information' => 'for tickets, call 111-111',
            'published_at' => Carbon::parse('-1 week')
        ]);

        //act
        $this->visit('/concerts/'.$concert->id);

        //assert
        $this->see('The Red Chord');
        $this->see('with animosity');
        $this->see('December 12, 2016');
        $this->see('8:00pm');
        $this->see('32.50');
        $this->see('the mosh pit');
        $this->see('123 example lane');
        $this->see('london, on 17916');
        $this->see('for tickets, call 111-111');
    }

    /** @test */
    function user_cannot_view_unpublished_concert_lisitings(){
        $concert = factory(Concert::class)->create([
            'published_at' => null
        ]);

        $this->get('/concerts/'. $concert->id);
        //visit se koristi kada ocekujem osuccess, jer u suprotnom baca exception
        $this->assertResponseStatus(404);
    }
}
