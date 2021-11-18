<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Observer;

use Facebook\BusinessExtension\Helper\FBEHelper;
use Facebook\BusinessExtension\Model\Product\Feed\Method\BatchApi;
use Facebook\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Helper\Context;

class ProcessProductAfterDeleteEventObserver implements ObserverInterface
{
    /**
     * @var FBEHelper
     */
    protected $fbeHelper;
    /**
     * @var BatchApi
     */
    protected $batchApi;
    /**
     * @var SystemConfig
     */
    protected $systemConfig;

    /**
     * Constructor
     * @param FBEHelper $helper
     * @param BatchApi $batchApi
     * @param SystemConfig $systemConfig
     */
    public function __construct(
        FBEHelper $helper,
        BatchApi $batchApi,
        SystemConfig $systemConfig
    ) {
        $this->fbeHelper = $helper;
        $this->batchApi = $batchApi;
        $this->systemConfig = $systemConfig;
    }

    /**
     * Call an API to product delete from facebook catalog
     * after delete product from Magento
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if ($this->systemConfig->isActiveCatalogSync() == false) {
            return;
        }

        $product = $observer->getEvent()->getProduct();

        if ($product->getId()) {

            try {
                $this->fbeHelper->log("deleting product: ". $product->getId());
                $requestData = $this->batchApi->buildProductRequest($product, $method='DELETE');
                $requestParams = [];
                $requestParams[0] = $requestData;
                $this->fbeHelper->log(json_encode($requestParams));
                $response = $this->fbeHelper->makeHttpRequest($requestParams, null);
                $this->fbeHelper->log("deletion responses: ".json_encode($response));
            } catch (\Exception $e) {
                $this->fbeHelper->logException($e);
            }
        }
    }
}
