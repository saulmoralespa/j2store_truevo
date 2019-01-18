<?php

use PHPUnit\Framework\TestCase;
use Truevo\Truevo;

/**
*  Corresponding Class to test YourClass class
*
*  For each class in your library, there should be a corresponding Unit-Test for it
*  Unit-Tests should be as much as possible independent from other test going on.
*
*  @author yourname
*/
class TruevoTest extends TestCase
{
    public function testPayment()
    {
        $user_login = '8ac9a4cc6831c6f80168324b028f05a3';
        $user_password = 'AHeDRJS8MT';
        $entity_id = '8ac9a4cc6831c6f80168324becb805ac';
        $truevo = new Truevo($user_login, $user_password, $entity_id);
        $truevo->sandbox_mode(true);

        $params = array(
            'amount' => '92.00',
            'currency' => 'EUR',
            'paymentBrand' => 'VISA',
            'paymentType' => 'DB',
            'card.number' => '4200000000000000',
            'card.holder' => 'Jane Jones',
            'card.expiryMonth' => '05',
            'card.expiryYear' => '2020',
            'card.cvv' => '123'
        );

        $data = $truevo->payment($params);

        $this->assertObjectHasAttribute('result', $data);

    }
}
