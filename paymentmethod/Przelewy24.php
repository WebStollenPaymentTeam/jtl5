<?php

/**
 * @copyright 2021 WebStollen GmbH
 * @link https://www.webstollen.de
 */

namespace Plugin\ws5_mollie\paymentmethod;

use JTL\Checkout\Bestellung;
use Plugin\ws5_mollie\lib\PaymentMethod;
use stdClass;

require_once __DIR__ . '/../vendor/autoload.php';

class Przelewy24 extends PaymentMethod
{
    public const ALLOW_PAYMENT_BEFORE_ORDER = true;

    public const METHOD = \Mollie\Api\Types\PaymentMethod::PRZELEWY24;

    public function getPaymentOptions(Bestellung $order, $apiType): array
    {
        $billingAddress = new stdClass();
        $billingAddress->email = $order->oRechnungsadresse->cMail;
        return $apiType === 'payment' ? ['billingAddress' => $billingAddress] : [];
    }
}
