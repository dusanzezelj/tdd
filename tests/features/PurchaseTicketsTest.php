<?php

use App\Billing\FakePaymentGateway;
use App\Concert;
use Carbon\Carbon;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PurchaseTicketsTest extends TestCase
{
    use DatabaseMigrations;

    private function orderTickets($concert, $params){
        //radi se zbog override requesta
        $savedRequest = $this->app['request'];
        $this->json('POST', "/concerts/{$concert->id}/orders", $params);
        $this->app['request'] = $savedRequest;
    }

    private function assertValidationError($field){
        $this->assertResponseStatus(422);
        $this->assertArrayHasKey($field, $this->decodeResponseJson());
    }

    /** @test */
    function customer_can_purchase_tickets_to_a_published_concert()
    {
        $paymentGateway = new FakePaymentGateway();
        $this->app->instance(\App\Billing\PaymentGateway::class, $paymentGateway);

        //arrange
        $concert = factory(Concert::class)->create(['ticket_price' => 3250, 'published_at' => date("Y-m-d H:i:s")]);
        $concert->addTickets(3);

        //act
        $this->orderTickets($concert, [
            'email' => 'john@example.com',
            'ticket_quantity' => 3,
            'payment_token' => $paymentGateway->getValidTestToken()
        ]);

        //assert
        $this->assertResponseStatus(201);

        $this->seeJsonSubset([
            'email' => 'john@example.com',
            'ticket_quantity' => 3,
            'amount' => 9750
        ]);
        //make sure the customer was charged the correct amount
        $this->assertEquals(9750, $paymentGateway->totalCharges());

        //make sure that an order exists for this customer
        /*$this->assertTrue($concert->orders->contains(function($order){
            return $order->email = 'john@example.com';
        }));*/

        $this->assertTrue($concert->hasOrderFor('john@example.com'));

        $this->assertEquals(3, $concert->ordersFor('john@example.com')->first()->ticketQuantity());
    }

    /** @test */
    function email_is_required_to_purchase_tickets(){
        $concert = factory(Concert::class)->create();

        $paymentGateway = new FakePaymentGateway();
        $this->app->instance(\App\Billing\PaymentGateway::class, $paymentGateway);
        $this->json('POST', "/concerts/{$concert->id}/orders", [
            'ticket_quantity' => 3,
            'payment_token' => $paymentGateway->getValidTestToken()
        ]);

        //$this->assertResponseStatus(422);
       // $this->assertArrayHasKey('email', $this->decodeResponseJson());
        $this->assertValidationError('email');
    }

    /** @test */
    function an_order_is_not_created_if_payment_fails(){
        $concert = factory(Concert::class)->create(['ticket_price' => 3250]);
        $concert->addTickets(3);
        //act
        $this->orderTickets($concert, [
            'email' => 'john@example.com',
            'ticket_quantity' => 3,
            'payment_token' => 'invalid-payment-token'
        ]);

        $this->assertResponseStatus(422);
        $order = $concert->orders->where('email', 'john@example.com')->first();
        $this->assertNull($order);
        $this->assertEquals(3, $concert->ticketsRemaining());
    }

    /** @test */
    function cannot_purchase_tickets_to_an_unpublished_concert()
    {
        $concert = factory(Concert::class)->create(['published_at' => null]);
        $concert->addTickets(3);

        $paymentGateway = new FakePaymentGateway();
        $this->app->instance(\App\Billing\PaymentGateway::class, $paymentGateway);

        $this->orderTickets($concert, [
            'email' => 'john@example.com',
            'ticket_quantity' => 3,
            'payment_token' => $paymentGateway->getValidTestToken()
        ]);

        $this->assertResponseStatus(404);
        $this->assertFalse($concert->hasOrderFor('john@example.com'));
        // nema potrebe za ovom metodom ak ose poziva ova iznad nje. $this->assertEquals(0, $concert->orders()->count());
        $this->assertEquals(0, $paymentGateway->totalCharges());
    }

    /** @test */
    function cannot_purchase_more_tickets_than_remain(){
        $concert = factory(Concert::class)->create([
            'published_at' => Carbon::parse('-1 week')
        ]);

        $concert->addTicket(50);

        $paymentGateway = new FakePaymentGateway();
        $this->app->instance(\App\Billing\PaymentGateway::class, $paymentGateway);
        $this->orderTickets($concert, [
            'email' => 'john@example.com',
            'ticket_quantity' => 51,
            'payment_token' => $paymentGateway->getValidTestToken()
        ]);

        $this->assertResponseStatus(422);
        $order = $concert->orders()->where('email', 'john@example.com')->first();
        $this->assertNull($order);
        $this->assertEquals(0, $paymentGateway->totalCharges());
        $this->assertEquals(50, $concert->ticketsRemaining());
    }

    /** @test */
    function cannot_purchase_tickets_another_customer_is_already_trying_to_purchase(){
        $concert = factory(Concert::class)->create(['ticket_price' => 1200]);
        $concert->addTickets(3);
        $paymentGateway = new FakePaymentGateway();

        $paymentGateway->beforeFirstCharge(function($paymentGateway) use ($concert){
            $this->orderTickets($concert, [
                'email' => 'personB@example.com',
                'ticket_quantity' => 1,
                'payment_token' => $paymentGateway->getValidTestToken()
            ]);

            $this->assertResponseStatus(422);
            $order = $concert->orders()->where('email', 'personB@example.com')->first();
            $this->assertNull($order);
            $this->assertEquals(0, $paymentGateway->totalCharges());
        });
        $this->orderTickets($concert, [
            'email' => 'personA@example.com',
            'ticket_quantity' => 3,
            'payment_token' => $paymentGateway->getValidTestToken()
        ]);

        $this->assertEquals(3600, $paymentGateway->totalCharges());
        $this->assertTrue($concert->hasOrderFor('personA@example.com'));
        $this->assertEquals(3, $concert->ordersFor('personA@example.com')->first()->ticketQuantity());
    }
}