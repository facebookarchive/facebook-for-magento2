<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */
namespace Facebook\BusinessExtension\Model\Product\Feed\ProductRetriever;

use Facebook\BusinessExtension\Helper\FBEHelper;
use Facebook\BusinessExtension\Model\Product\Feed\ProductRetrieverInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\GroupedProduct\Model\Product\Type\Grouped as GroupedType;
use Magento\Framework\Exception\LocalizedException;

class Grouped implements ProductRetrieverInterface
{
    const LIMIT = 200;

    /**
     * @var FBEHelper
     */
    protected $fbeHelper;

    /**
     * @var CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @param FBEHelper $fbeHelper
     * @param CollectionFactory $productCollectionFactory
     */
    public function __construct(FBEHelper $fbeHelper, CollectionFactory $productCollectionFactory)
    {
        $this->fbeHelper = $fbeHelper;
        $this->productCollectionFactory = $productCollectionFactory;
    }

    /**
     * @inheritDoc
     */
    public function getProductType()
    {
        return GroupedType::TYPE_CODE;
    }

    /**
     * @inheritDoc
     * @throws LocalizedException
     */
    public function retrieve($offset = 1, $limit = self::LIMIT): array
    {
        $groupedCollection = $this->productCollectionFactory->create();
        $groupedCollection->addAttributeToSelect('*')
            ->addAttributeToFilter('status', Status::STATUS_ENABLED)
            ->addAttributeToFilter('visibility', ['neq' => Visibility::VISIBILITY_NOT_VISIBLE])
            ->addAttributeToFilter('type_id', $this->getProductType())
            ->setStoreId($this->fbeHelper->getStore()->getId());

        $groupedCollection->getSelect()->limit($limit, $offset);

        $simpleProducts = [];
        foreach ($groupedCollection as $product) {
            /** @var Product $product */
            /** @var GroupedType $groupedType */
            $groupedType = $product->getTypeInstance();
            $groupedProduct = ['item_group_id' => $product->getId()];
            foreach ($groupedType->getAssociatedProducts($product) as $childProduct) {
                /** @var Product $childProduct */
                $childProduct->setConfigurableSettings($groupedProduct);
                $childProduct->setParentProductUrl($product->getProductUrl());
                $simpleProducts[] = $childProduct;
            }
        }

        return $simpleProducts;
    }

    /**
     * @inheritDoc
     */
    public function getLimit()
    {
        return self::LIMIT;
    }
}
