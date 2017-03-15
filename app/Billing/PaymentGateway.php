<?php
/**
 * Created by PhpStorm.
 * User: dusan
 * Date: 19.2.17.
 * Time: 14.08
 */

namespace App\Billing;

interface PaymentGateway
{
    public function charge($amount, $token);
}