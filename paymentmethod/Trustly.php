<?php


/**
 * @copyright 2021 WebStollen GmbH
 * @link https://www.webstollen.de
 */

namespace Plugin\ws5_mollie\paymentmethod;

use JTL\Checkout\Bestellung;
use Plugin\ws5_mollie\lib\Order\Address;
use Plugin\ws5_mollie\lib\PaymentMethod;

require_once __DIR__ . '/../vendor/autoload.php';

class Trustly extends PaymentMethod
{
    public const ALLOW_PAYMENT_BEFORE_ORDER = true;

    public const METHOD = \Mollie\Api\Types\PaymentMethod::TRUSTLY;

    public function getPaymentOptions(Bestellung $order, $apiType): array
    {
        return [];
    }
}
