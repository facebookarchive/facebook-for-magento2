<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Observer;

use Facebook\BusinessExtension\Helper\FBEHelper;
use Facebook\BusinessExtension\Model\Feed\CategoryCollection;
use Facebook\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Helper\Context;

class ProcessCategoryAfterDeleteEventObserver implements ObserverInterface
{
    /**
     * @var FBEHelper
     */
    protected $fbeHelper;
    /**
     * @var SystemConfig
     */
    protected $systemConfig;

    /**
     * Constructor
     * @param FBEHelper $helper
     * @param SystemConfig $systemConfig
     */
    public function __construct(
        FBEHelper $helper,
        SystemConfig $systemConfig
    ) {
        $this->fbeHelper = $helper;
        $this->systemConfig = $systemConfig;
    }

    /**
     * Call an API to category delete from facebook catalog
     * after delete category from Magento
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if ($this->systemConfig->isActiveCatalogSync() == false) {
            return;
        }
        $category = $observer->getEvent()->getCategory();
        $this->fbeHelper->log("delete category: ".$category->getName());
        /** @var CategoryCollection $categoryObj */
        $categoryObj = $this->fbeHelper->getObject(CategoryCollection::class);
        $categoryObj->deleteCategoryAndSubCategoryFromFB($category);
    }
}
