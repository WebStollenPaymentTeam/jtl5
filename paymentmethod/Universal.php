<?php
/**
 * @copyright 2020 WebStollen GmbH
 */

namespace Plugin\ws5_mollie\paymentmethod;

use JTL\Checkout\Bestellung;
use Plugin\ws5_mollie\lib\PaymentMethod;

require_once __DIR__ . '/../vendor/autoload.php';

class Universal extends PaymentMethod
{
    public const METHOD = '';

    public function getPaymentOptions(Bestellung $order, $apiType): array
    {
        return [];
    }

}