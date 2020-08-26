<?php

namespace Facebook\BusinessExtension\Observer\Order;

use Exception;
use Facebook\BusinessExtension\Helper\CommerceHelper;

use Facebook\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order\Shipment\Item;
use Magento\Shipping\Model\Order\Track;
use Psr\Log\LoggerInterface;

class MarkAsShipped implements ObserverInterface
{
    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CommerceHelper
     */
    private $commerceHelper;

    /**
     * @param SystemConfig $systemConfig
     * @param LoggerInterface $logger
     * @param CommerceHelper $commerceHelper
     */
    public function __construct(SystemConfig $systemConfig, LoggerInterface $logger, CommerceHelper $commerceHelper)
    {
        $this->systemConfig = $systemConfig;
        $this->logger = $logger;
        $this->commerceHelper = $commerceHelper;
    }

    public function execute(Observer $observer)
    {
        if (!($this->systemConfig->isActiveExtension() && $this->systemConfig->isActiveOrderSync())) {
            return;
        }

        /** @var Shipment $shipment */
        $shipment = $observer->getEvent()->getShipment();

        if (!$shipment->getOrder()->getExtensionAttributes()->getFacebookOrderId()) {
            return;
        }

        $itemsToShip = [];
        /** @var Item $shipmentItem */
        foreach ($shipment->getAllItems() as $shipmentItem) {
            $orderItem = $shipmentItem->getOrderItem();
            $itemsToShip[] = ['retailer_id' => $orderItem->getSku(), 'quantity' => (int)$shipmentItem->getQty()];
        }

        $fbOrderId = $shipment->getOrder()->getExtensionAttributes()->getFacebookOrderId();

        $tracks = $shipment->getAllTracks();
        if (count($tracks) == 0) {
            throw new LocalizedException(__('Please provide a tracking number.'));
        }
        if (count($tracks) > 1) {
            throw new LocalizedException(__('Please provide only one tracking number per shipment.'));
        }

        // @todo Implement dynamic carrier
        $carrierCodesMap = [
            'fedex' => \Magento\Fedex\Model\Carrier::CODE,
            'usp' => \Magento\Ups\Model\Carrier::CODE,
            'usps' => \Magento\Usps\Model\Carrier::CODE,
            'dhl' => \Magento\Dhl\Model\Carrier::CODE,
        ];

        /** @var Track $track */
        $track = $tracks[0];
        if (!array_key_exists($track->getCarrierCode(), $carrierCodesMap)) {
            throw new LocalizedException(__('Carrier not supported by Facebook.'));
        }

        $trackingInfo = [
            'tracking_number' => $track->getNumber(),
            'shipping_method_name' => $track->getTitle(),
            'carrier' => $carrierCodesMap[$track->getCarrierCode()],
        ];

        $this->commerceHelper->markOrderAsShipped($fbOrderId, $itemsToShip, $trackingInfo);
        $shipment->getOrder()->addCommentToStatusHistory("Marked order as shipped on Facebook with {$track->getTitle()}. Tracking #: {$track->getNumber()}.");
    }
}
