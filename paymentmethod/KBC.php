<?php

/**
 * @copyright 2021 WebStollen GmbH
 * @link https://www.webstollen.de
 */

namespace Plugin\ws5_mollie\paymentmethod;

use JTL\Checkout\Bestellung;
use Plugin\ws5_mollie\lib\PaymentMethod;

class KBC extends PaymentMethod
{
    public const ALLOW_PAYMENT_BEFORE_ORDER = true;

    public const METHOD = \Mollie\Api\Types\PaymentMethod::KBC;

    public function getPaymentOptions(Bestellung $order, $apiType): array
    {
        // Sets description of KBC payments and overwrites the value that is specified in plugin settings as description for all payments - for "Bezahlung vor Bestellschluss" this contains just the preliminary order number and it is overwritten by updateOrderNumber() later again
        return $apiType === 'payment' ? ['description' => substr($order->cBestellNr, 0, 13)] : [];
    }
}
