<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Model\Product\Feed\Builder;

use Facebook\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;

class Inventory
{
    const STATUS_IN_STOCK = 'in stock';

    const STATUS_OUT_OF_STOCK = 'out of stock';

    const UNMANAGED_STOCK_QTY = 9999;

    /**
     * @var StockItemRepositoryInterface
     */
    private $stockItemRepository;

    /**
     * @var StockItemCriteriaInterfaceFactory
     */
    private $stockItemCriteriaInterfaceFactory;

    /**
     * @var StockConfigurationInterface
     */
    private $stockConfigurationInterface;

    /**
     * @var Product
     */
    private $product;

    /**
     * @var SystemConfig
     */
    protected $systemConfig;

    /**
     * @var StockItemInterface
     */
    protected $productStock;

    /**
     * @param StockItemRepositoryInterface $stockItemRepository
     * @param StockItemCriteriaInterfaceFactory $stockItemCriteriaInterfaceFactory
     * @param StockConfigurationInterface $stockConfigurationInterface
     * @param SystemConfig $systemConfig
     */
    public function __construct(
        StockItemRepositoryInterface $stockItemRepository,
        StockItemCriteriaInterfaceFactory $stockItemCriteriaInterfaceFactory,
        StockConfigurationInterface $stockConfigurationInterface,
        SystemConfig $systemConfig
    ) {
        $this->stockItemRepository = $stockItemRepository;
        $this->stockItemCriteriaInterfaceFactory = $stockItemCriteriaInterfaceFactory;
        $this->stockConfigurationInterface = $stockConfigurationInterface;
        $this->systemConfig = $systemConfig;
    }

    /**
     * @param Product $product
     * @return StockItemInterface|null
     */
    public function getStockItem(Product $product)
    {
        $criteria = $this->stockItemCriteriaInterfaceFactory->create();
        $criteria->setProductsFilter($product->getId());
        $stocksItems = $this->stockItemRepository->getList($criteria)->getItems();
        return array_shift($stocksItems);
    }

    /**
     * @param Product $product
     * @return $this
     */
    public function initInventoryForProduct(Product $product)
    {
        $this->product = $product;
        $this->productStock = $this->getStockItem($product);
        return $this;
    }

    /**
     * @return string
     */
    public function getAvailability()
    {
        return $this->productStock && $this->productStock->getIsInStock()
            && ($this->getInventory() - $this->systemConfig->getOutOfStockThreshold() > 0)
            ? self::STATUS_IN_STOCK : self::STATUS_OUT_OF_STOCK;
    }

    /**
     * @return int
     */
    public function getInventory()
    {
        if (!$this->productStock) {
            return 0;
        }

        if (!$this->productStock->getManageStock()) {
            return self::UNMANAGED_STOCK_QTY; // Fake Quantity to make product available if Manage Stock is off.
        }

        return (int)$this->productStock->getQty();
    }
}
