<?php

namespace Facebook\BusinessExtension\Observer\Order;

use Exception;
use Facebook\BusinessExtension\Api\Data\FacebookOrderInterfaceFactory;
use Facebook\BusinessExtension\Helper\CommerceHelper;
use Facebook\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\CreditmemoItemInterface as CreditmemoItem;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Block\Order\Creditmemo;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Psr\Log\LoggerInterface;

class Refund implements ObserverInterface
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
     * @var FacebookOrderInterfaceFactory
     */
    private $facebookOrderFactory;

    /**
     * @param SystemConfig $systemConfig
     * @param LoggerInterface $logger
     * @param CommerceHelper $commerceHelper
     * @param FacebookOrderInterfaceFactory $facebookOrderFactory
     */
    public function __construct(
        SystemConfig $systemConfig,
        LoggerInterface $logger,
        CommerceHelper $commerceHelper,
        FacebookOrderInterfaceFactory $facebookOrderFactory
    )
    {
        $this->systemConfig = $systemConfig;
        $this->logger = $logger;
        $this->commerceHelper = $commerceHelper;
        $this->facebookOrderFactory = $facebookOrderFactory;
    }

    public function execute(Observer $observer)
    {
        if (!($this->systemConfig->isActiveExtension() && $this->systemConfig->isActiveOrderSync())) {
            return;
        }

        /** @var Payment $payment */
        $payment = $observer->getEvent()->getPayment();
        /** @var Creditmemo $creditmemo */
        $creditmemo = $observer->getEvent()->getCreditmemo();

        // @todo fix magento bug with incorrectly loading order in credit memo resulting in missing extension attributes
        // https://github.com/magento/magento2/issues/23345

        $facebookOrder = $this->facebookOrderFactory->create();
        $facebookOrder->load($payment->getOrder()->getId(), 'magento_order_id');

        if (!$facebookOrder->getFacebookOrderId()) {
            return;
        }

        if ($creditmemo->getAdjustment() > 0) {
            throw new Exception('Cannot refund order on Facebook. Refund with adjustments is not yet supported.');
        }

        $refundItems = [];
        foreach ($creditmemo->getItems() as $item) {
            if ($item->getQty() > 0) {
                $refundItems[] = [
                    'retailer_id' => $item->getSku(),
                    'item_refund_quantity' => $item->getQty(),
                ];
            }
        }

        $shippingRefundAmount = $creditmemo->getBaseShippingAmount();
        $reasonText = $creditmemo->getCustomerNote();

        $this->commerceHelper->refundOrder($facebookOrder->getFacebookOrderId(), $refundItems, $shippingRefundAmount, $reasonText);
        $payment->getOrder()->addCommentToStatusHistory('Refunded order on Facebook');
    }
}
