<?php

/**
 * @copyright 2021 WebStollen GmbH
 * @link https://www.webstollen.de
 */

namespace Plugin\ws5_mollie\paymentmethod;

use JTL\Checkout\Bestellung;
use JTL\Session\Frontend;
use Plugin\ws5_mollie\lib\PaymentMethod;

class PaysafeCard extends PaymentMethod
{
    public const ALLOW_PAYMENT_BEFORE_ORDER = true;

    public const METHOD = \Mollie\Api\Types\PaymentMethod::PAYSAFECARD;

    public function getPaymentOptions(Bestellung $order, $apiType): array
    {
        if ( $apiType === 'payment' && Frontend::getCustomer()->getID() > 0) {
            // Set Method-specific parameters
            return  ['customerReference' => strval(Frontend::getCustomer()->getID())];
        } else {
            return [];
        }
    }
}
