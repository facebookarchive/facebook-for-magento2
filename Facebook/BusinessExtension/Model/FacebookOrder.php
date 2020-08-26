<?php

namespace Facebook\BusinessExtension\Model;

use Facebook\BusinessExtension\Api\Data\FacebookOrderInterface;
use Facebook\BusinessExtension\Model\ResourceModel\FacebookOrder as ResourceModel;
use Magento\Framework\Model\AbstractModel;

class FacebookOrder extends AbstractModel implements FacebookOrderInterface
{
    protected function _construct()
    {
        $this->_init(ResourceModel::class);
    }

    public function getMagentoOrderId()
    {
        return $this->getData('magento_order_id');
    }

    public function setMagentoOrderId($orderId)
    {
        $this->setData('magento_order_id', $orderId);
        return $this;
    }

    public function getFacebookOrderId()
    {
        return $this->getData('facebook_order_id');
    }

    public function setFacebookOrderId($orderId)
    {
        $this->setData('facebook_order_id', $orderId);
        return $this;
    }

    public function getSource()
    {
        return $this->getData('source');
    }

    public function setSource($source)
    {
        $this->setData('source', $source);
        return $this;
    }
}
