<?php
use App\Concert;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Billing\PaymentFailedException;

class FakePaymentGatewayTest extends TestCase
{
    /**  @test */
    function charge_with_a_valid_payment_token_are_successful()
    {
        $paymentGateway = new \App\Billing\FakePaymentGateway();

        $paymentGateway->charge(2500, $paymentGateway->getValidTestToken());

        $this->assertEquals(2500, $paymentGateway->totalCharges());
    }

    /** @test
     *
     */
    function charge_with_an_invalid_payment_token_fail(){
        try{
            $paymentGateway = new \App\Billing\FakePaymentGateway();
            $paymentGateway->charge(2500, 'invalid-token');
        } catch (PaymentFailedException $e){
            return;
        }

        $this->fail();

    }

    /** @test */
    function running_a_hook_before_the_first_charge(){
        $paymentGateway = new \App\Billing\FakePaymentGateway();
        $timesCallbackRan = 0;

        $paymentGateway->beforeFirstCharge(function ($paymentGateway) use (&$timesCallbackRan){
            $timesCallbackRan++;
            $this->assertEquals(0, $paymentGateway->totalCharges());
        });

        $paymentGateway->charge(2500, $paymentGateway->getValidTestToken());
        $this->assertEquals(1, $timesCallbackRan);
        $this->assertEquals(2500, $paymentGateway->totalCharges());
    }
}