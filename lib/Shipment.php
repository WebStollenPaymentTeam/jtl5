<?php


namespace Plugin\ws5_mollie\lib;


use Exception;
use JTL\Checkout\Lieferschein;
use JTL\Checkout\Lieferscheinpos;
use JTL\Checkout\Versand;
use JTL\Model\DataModel;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\Exceptions\IncompatiblePlatform;
use Mollie\Api\Resources\OrderLine;
use Mollie\Api\Types\OrderStatus;
use Plugin\ws5_mollie\lib\Checkout\OrderCheckout;
use Plugin\ws5_mollie\lib\Model\ShipmentsModel;
use Plugin\ws5_mollie\lib\Traits\Plugin;
use Plugin\ws5_mollie\lib\Traits\RequestData;
use Shop;

class Shipment
{

    /**
     * @var int|null
     */
    protected $kLieferschein;

    use Plugin;

    use RequestData;

    /**
     * @var ShipmentsModel
     */
    protected $model;
    /**
     * @var OrderCheckout
     */
    protected $checkout;
    /**
     * @var \Mollie\Api\Resources\Shipment
     */
    protected $shipment;
    /**
     * @var Lieferschein
     */
    protected $oLieferschein;

    public function __construct(int $kLieferschein, OrderCheckout $checkout = null)
    {
        $this->kLieferschein = $kLieferschein;
        if ($checkout) {
            $this->checkout = $checkout;
        }

        if (!$this->getLieferschein() || !$this->getLieferschein()->getLieferschein()) {
            throw new \Plugin\ws5_mollie\lib\Exception\APIException('Lieferschein konnte nicht geladen werden');
        }

        if (!count($this->getLieferschein()->oVersand_arr)) {
            throw new \Plugin\ws5_mollie\lib\Exception\APIException('Kein Versand gefunden!');
        }


    }

    public function getLieferschein(): ?Lieferschein
    {
        if (!$this->oLieferschein && $this->kLieferschein) {
            $this->oLieferschein = new Lieferschein($this->kLieferschein);
        }
        return $this->oLieferschein;
    }

    /**
     * @param int $kBestellung
     * @return array
     * @throws ApiException
     * @throws IncompatiblePlatform
     * @throws \JTL\Exceptions\CircularReferenceException
     * @throws \JTL\Exceptions\ServiceNotFoundException
     */
    public static function syncBestellung(OrderCheckout $checkout): array
    {

        $shipments = [];
        if ($checkout->getBestellung()->kBestellung) {

            $oKunde = $checkout->getBestellung()->oKunde ?? new \JTL\Customer\Customer($checkout->getBestellung()->kKunde);

            /** @var Lieferschein $oLieferschein */
            foreach ($checkout->getBestellung()->oLieferschein_arr as $oLieferschein) {

                try {

                    $shipment = new Shipment($oLieferschein->getLieferschein(), $checkout);

                    $mode = self::Plugin()->getConfig()->getValue('shippingMode');
                    switch ($mode) {
                        case 'A':
                            // ship directly
                            if (!$shipment->send() && !$shipment->getShipment()) {
                                throw new \Plugin\ws5_mollie\lib\Exception\APIException('Shipment konnte nicht gespeichert werden.');
                            }
                            $shipments[] = $shipment->getShipment();
                            break;

                        case 'B':
                            // only ship if complete shipping
                            if ($oKunde->nRegistriert || (int)$checkout->getBestellung()->cStatus === BESTELLUNG_STATUS_VERSANDT) {
                                if (!$shipment->send() && !$shipment->getShipment()) {
                                    throw new \Plugin\ws5_mollie\lib\Exception\APIException('Shipment konnte nicht gespeichert werden.');
                                }
                                $shipments[] = $shipment->getShipment();
                                break;
                            }
                            throw new \Plugin\ws5_mollie\lib\Exception\APIException('Gastbestellung noch nicht komplett versendet!');
                    }
                } catch (\Plugin\ws5_mollie\lib\Exception\APIException $e) {
                    $shipments[] = $e->getMessage();
                } catch (Exception $e) {
                    $shipments[] = $e->getMessage();
                    Shop::Container()->getLogService()->addError("mollie: Shipment::syncBestellung (BestellNr. {$checkout->getBestellung()->cBestellNr}, Lieferschein: {$shipment->getLieferschein()->getLieferscheinNr()}) - " . $e->getMessage());
                }
            }
        }
        return $shipments;
    }

    /**
     * @return bool
     * @throws ApiException
     * @throws IncompatiblePlatform
     * @throws Exception
     */
    public function send(): bool
    {

        if ($this->getShipment()) {
            throw new \Plugin\ws5_mollie\lib\Exception\APIException('Lieferschien bereits an Mollie übertragen: ' . $this->getShipment()->id);
        }

        if ($this->getCheckout()->getMollie(true)->status === OrderStatus::STATUS_COMPLETED) {
            throw new \Plugin\ws5_mollie\lib\Exception\APIException('Bestellung bei Mollie bereits abgeschlossen!');
        }

        $api = $this->getCheckout()->getAPI()->getClient();

        $this->shipment = $api->shipments->createForId($this->checkout->getModel()->getOrderId(), $this->loadRequest()->getRequestData());

        return $this->updateModel()->saveModel();

    }

    public function getShipment($force = false): ?\Mollie\Api\Resources\Shipment
    {
        if (($force || !$this->shipment) && $this->getModel() && $this->getModel()->getShipmentId()) {
            $this->shipment = $this->getCheckout()->getAPI()->getClient()->shipments->getForId($this->getModel()->getOrderId(), $this->getModel()->getShipmentId());
        }
        return $this->shipment;
    }

    /**
     * @return ShipmentsModel
     * @throws Exception
     */
    public function getModel(): ShipmentsModel
    {
        if (!$this->model && $this->kLieferschein) {
            $this->model = ShipmentsModel::loadByAttributes(
                ['lieferschein' => $this->kLieferschein], Shop::Container()->getDB(),
                DataModel::ON_NOTEXISTS_NEW
            );
            if (!$this->model->getCreated()) {
                $this->getModel()->setCreated(date('Y-m-d H:i:s'));
            }
            $this->updateModel();
        }
        return $this->model;
    }

    public function updateModel(): self
    {
        $this->getModel()->setLieferschein($this->kLieferschein);
        if ($this->getCheckout()) {
            $this->getModel()->setOrderId($this->getCheckout()->getModel()->getOrderId());
            $this->getModel()->setBestellung($this->getCheckout()->getModel()->getBestellung());
        }
        if ($this->getShipment()) {
            $this->getModel()->setShipmentId($this->getShipment()->id);
            $this->getModel()->setUrl($this->getShipment()->getTrackingUrl() ?? '');
        }
        if ($this->getRequestData()) {
            if ($tracking = $this->RequestData('tracking')) {
                $this->getModel()->setCarrier($this->RequestData('tracking')['carrier'] ?? '');
                $this->getModel()->setCode($this->RequestData('tracking')['code'] ?? '');
            }
        }
        return $this;
    }

    public function getCheckout(): OrderCheckout
    {
        if (!$this->checkout) {
            //TODO evtl. load by lieferschien
            throw new Exception('Should not happen, but it did!');
        }
        return $this->checkout;
    }


    public function loadRequest(): self
    {

        /** @var Versand $oVersand */
        $oVersand = $this->getLieferschein()->oVersand_arr[0];
        if ($oVersand->getIdentCode() && $oVersand->getLogistik()) {
            $tracking = [
                'carrier' => $oVersand->getLogistik(),
                'code' => $oVersand->getIdentCode(),
            ];
            if ($oVersand->getLogistikVarUrl()) {
                $tracking['url'] = $oVersand->getLogistikURL();
            }
            $this->setRequestData('tracking', $tracking);
        }

        // TODO: Wenn alle Lieferschiene in der WAWI erstellt wurden, aber nicht im Shop, kommt status 4.
        if ((int)$this->getCheckout()->getBestellung()->cStatus === BESTELLUNG_STATUS_VERSANDT) {
            $this->setRequestData('lines', []);
        } else {
            $this->setRequestData('lines', $this->getOrderLines());
        }
        return $this;
    }

    protected function getOrderLines(): array
    {
        $lines = [];

        if (!count($this->getLieferschein()->oLieferscheinPos_arr)) {
            return $lines;
        }

        // Bei Stücklisten, sonst gibt es mehrere OrderLines für die selbe ID
        $shippedOrderLines = [];

        /** @var Lieferscheinpos $oLieferschienPos */
        foreach ($this->getLieferschein()->oLieferscheinPos_arr as $oLieferschienPos) {

            $wkpos = Shop::Container()->getDB()->executeQueryPrepared('SELECT * FROM twarenkorbpos WHERE kBestellpos = :kBestellpos', [
                ':kBestellpos' => $oLieferschienPos->getBestellPos()
            ], 1);

            /** @var OrderLine $orderLine */
            foreach ($this->getCheckout()->getMollie()->lines as $orderLine) {

                if ($orderLine->sku === $wkpos->cArtNr && !in_array($orderLine->id, $shippedOrderLines, true)) {

                    if ($quantity = min($oLieferschienPos->getAnzahl(), $orderLine->shippableQuantity)) {
                        $lines[] = [
                            'id' => $orderLine->id,
                            'quantity' => $quantity
                        ];
                    }
                    $shippedOrderLines[] = $orderLine->id;
                    break;
                }

            }
        }


        return $lines;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function saveModel(): bool
    {
        return $this->getModel()->save();
    }

}