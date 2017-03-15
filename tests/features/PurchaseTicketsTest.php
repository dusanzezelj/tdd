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
        $this->json('POST', "/concerts/{$concert->id}/orders", $params);
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
        $concert = factory(Concert::class)->create(['ticket_price' => 3250]);

        //act
        $this->orderTickets($concert, [
            'email' => 'john@example.com',
            'ticket_quantity' => 3,
            'payment_token' => $paymentGateway->getValidTestToken()
        ]);

        //assert
        $this->assertResponseStatus(201);
        //make sure the customer was charged the correct amount
        $this->assertEquals(9750, $paymentGateway->totalCharges());

        //make sure that an order exists for this customer
        /*$this->assertTrue($concert->orders->contains(function($order){
            return $order->email = 'john@example.com';
        }));*/

        $order = $concert->orders->where('email', 'john@example.com')->first();
        $this->assertNotNull($order);
        $this->assertEquals(3, $order->tickets()->count());
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

        //act
        $this->orderTickets($concert, [
            'email' => 'john@example.com',
            'ticket_quantity' => 3,
            'payment_token' => 'invalid-payment-token'
        ]);

        $this->assertResponseStatus(422);
        $order = $concert->orders->where('email', 'john@example.com')->first();
        $this->assertNull($order);
    }

    /** @test */
    function cannot_purchase_tickets_to_an_unpublished_concert()
    {
        $concert = factory(Concert::class)->create(['published_at' => null]);
        $paymentGateway = new FakePaymentGateway();
        $this->app->instance(\App\Billing\PaymentGateway::class, $paymentGateway);

        $this->orderTickets($concert, [
            'email' => 'john@example.com',
            'ticket_quantity' => 3,
            'payment_token' => $paymentGateway->getValidTestToken()
        ]);

        $this->assertResponseStatus(404);
        $this->assertEquals(0, $concert->orders()->count());
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
}