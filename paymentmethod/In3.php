<?php


namespace Plugin\ws5_mollie\paymentmethod;


use JTL\Checkout\Bestellung;
use Plugin\ws5_mollie\lib\Order\Address;
use Plugin\ws5_mollie\lib\PaymentMethod;

class In3 extends PaymentMethod
{
    public const ALLOW_PAYMENT_BEFORE_ORDER = true;
    public const METHOD = 'in3';

    public function getPaymentOptions(Bestellung $order, $apiType): array
    {
        return [];
    }

    public function isSelectable(): bool
    {
        return parent::isSelectable();
    }

}