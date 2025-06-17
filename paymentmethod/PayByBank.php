<?php

namespace Plugin\ws5_mollie\paymentmethod;

use JTL\Checkout\Bestellung;
use Plugin\ws5_mollie\lib\PaymentMethod;

require_once __DIR__ . '/../vendor/autoload.php';


class PayByBank extends PaymentMethod
{
    public const ALLOW_PAYMENT_BEFORE_ORDER = false;

    public const METHOD = \Mollie\Api\Types\PaymentMethod::PAYBYBANK;

    public function getPaymentOptions(Bestellung $order, $apiType): array
    {
        return [];
    }
}