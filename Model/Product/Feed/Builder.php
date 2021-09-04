<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Model\Product\Feed;

use Facebook\BusinessExtension\Helper\FBEHelper;
use Facebook\BusinessExtension\Model\Feed\EnhancedCatalogHelper;
use Facebook\BusinessExtension\Model\Product\Feed\Builder\Inventory;
use Facebook\BusinessExtension\Model\Product\Feed\Builder\Tools as BuilderTools;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\LocalizedException;
use Magento\GroupedProduct\Model\Product\Type\Grouped;

class Builder
{
    const ATTR_RETAILER_ID          = 'id';
    const ATTR_ITEM_GROUP_ID        = 'item_group_id';
    const ATTR_DESCRIPTION          = 'description';
    const ATTR_RICH_DESCRIPTION     = 'rich_text_description';
    const ATTR_URL                  = 'link';
    const ATTR_IMAGE_URL            = 'image_link';
    const ATTR_ADDITIONAL_IMAGE_URL = 'additional_image_link';
    const ATTR_BRAND                = 'brand';
    const ATTR_SIZE                 = 'size';
    const ATTR_COLOR                = 'color';
    const ATTR_CONDITION            = 'condition';
    const ATTR_AVAILABILITY         = 'availability';
    const ATTR_INVENTORY            = 'inventory';
    const ATTR_PRICE                = 'price';
    const ATTR_SALE_PRICE           = 'sale_price';
    const ATTR_NAME                 = 'title';
    const ATTR_PRODUCT_TYPE         = 'product_type';
    const ATTR_PRODUCT_CATEGORY     = 'google_product_category';

    /**
     * @var FBEHelper
     */
    protected $fbeHelper;

    /**
     * @var string
     */
    protected $defaultBrand;

    /**
     * @var CategoryCollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @var BuilderTools
     */
    protected $builderTools;

    /**
     * @var Inventory
     */
    protected $inventory;

    /**
     * @var EnhancedCatalogHelper
     */
    protected $enhancedCatalogHelper;

    /**
     * @param FBEHelper $fbeHelper
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param BuilderTools $builderTools
     * @param Inventory $inventory
     * @param EnhancedCatalogHelper $enhancedCatalogHelper
     */
    public function __construct(
        FBEHelper $fbeHelper,
        CategoryCollectionFactory $categoryCollectionFactory,
        BuilderTools $builderTools,
        Inventory $inventory,
        EnhancedCatalogHelper $enhancedCatalogHelper
    ) {
        $this->fbeHelper = $fbeHelper;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->builderTools = $builderTools;
        $this->inventory = $inventory;
        $this->enhancedCatalogHelper = $enhancedCatalogHelper;
    }

    /**
     * @return string
     */
    protected function getDefaultBrand()
    {
        if (!$this->defaultBrand) {
            $this->defaultBrand = $this->trimAttribute(self::ATTR_BRAND, $this->fbeHelper->getStoreName());
        }
        return $this->defaultBrand;
    }

    /**
     * @param Product $product
     * @return string
     */
    protected function getProductUrl(Product $product)
    {
        $parentUrl = $product->getParentProductUrl();
        // use parent product URL if a simple product has a parent and is not visible individually
        $url = (!$product->isVisibleInSiteVisibility() && $parentUrl) ? $parentUrl : $product->getProductUrl();
        return $this->builderTools->replaceLocalUrlWithDummyUrl($url);
    }

    /**
     * @param Product $product
     * @return array
     */
    protected function getProductImages(Product $product)
    {
        $mainImage = $product->getImage();

        $additionalImages = [];
        if (!empty($product->getMediaGalleryImages())) {
            foreach ($product->getMediaGalleryImages() as $img) {
                if ($img['file'] === $mainImage) {
                    continue;
                }
                $additionalImages[] = $this->builderTools->replaceLocalUrlWithDummyUrl($img['url']);
            }
        }

        return [
            'main_image' => $this->builderTools->replaceLocalUrlWithDummyUrl(
                $this->fbeHelper->getBaseUrlMedia() . 'catalog/product' . $mainImage
            ),
            'additional_images' => array_slice($additionalImages, 0, 10),
        ];
    }

    /**
     * @param Product $product
     * @return string
     */
    protected function getProductPrice(Product $product)
    {
        return $this->builderTools->formatPrice($product->getPrice());
    }

    /**
     * @param Product $product
     * @return string
     */
    protected function getProductSalePrice(Product $product)
    {
        if ($product->getRegularPrice() > $product->getFinalPrice()) {
            return $this->builderTools->formatPrice($product->getFinalPrice());
        }

        return '';
    }

    /**
     * @param Product $product
     * @return string
     * @throws LocalizedException
     */
    protected function getCategoryPath(Product $product)
    {
        $categoryIds = $product->getCategoryIds();
        if (empty($categoryIds)) {
            return '';
        }

        $categoryNames = [];
        $categories = $this->categoryCollectionFactory->create()
            ->addAttributeToSelect('name')
            ->addAttributeToFilter('entity_id', $categoryIds)
            ->setOrder('position', 'ASC');
        /** @var CategoryInterface $category */
        foreach ($categories as $category) {
            $categoryNames[] = $category->getName();
        }
        return implode(' > ', $categoryNames);
    }

    /**
     * @param $attrName
     * @param $attrValue
     * @return string
     */
    protected function trimAttribute($attrName, $attrValue)
    {
        $attrValue = trim($attrValue);
        // Facebook Product attributes
        // ref: https://developers.facebook.com/docs/commerce-platform/catalog/fields
        switch ($attrName) {
            case self::ATTR_RETAILER_ID:
            case self::ATTR_URL:
            case self::ATTR_IMAGE_URL:
            case self::ATTR_CONDITION:
            case self::ATTR_AVAILABILITY:
            case self::ATTR_INVENTORY:
            case self::ATTR_PRICE:
            case self::ATTR_SIZE:
            case self::ATTR_COLOR:
                if ($attrValue) {
                    return $attrValue;
                }
                break;
            case self::ATTR_BRAND:
                if ($attrValue) {
                    // brand max size: 70
                    return mb_strlen($attrValue) > 70 ? mb_substr($attrValue, 0, 70) : $attrValue;
                }
                break;
            case self::ATTR_NAME:
                if ($attrValue) {
                    // title max size: 100
                    return mb_strlen($attrValue) > 100 ? mb_substr($attrValue, 0, 100) : $attrValue;
                }
                break;
            case self::ATTR_DESCRIPTION:
                if ($attrValue) {
                    // description max size: 5000
                    return mb_strlen($attrValue) > 5000 ? mb_substr($attrValue, 0, 5000) : $attrValue;
                }
                break;
            case self::ATTR_PRODUCT_TYPE:
                // product_type max size: 750
                if ($attrValue) {
                    return mb_strlen($attrValue) > 750 ?
                        mb_substr($attrValue, mb_strlen($attrValue) - 750, 750) : $attrValue;
                }
                break;
        }
        return '';
    }

    /**
     * @param Product $product
     * @return string
     */
    protected function getDescription(Product $product)
    {
        // 'Description' is required by default but can be made
        // optional through the magento admin panel.
        // Try using the short description and title if it doesn't exist.
        $description = $this->trimAttribute(
            self::ATTR_DESCRIPTION,
            $product->getDescription()
        );
        if (!$description) {
            $description = $this->trimAttribute(
                self::ATTR_DESCRIPTION,
                $product->getShortDescription()
            );
        }

        $title = $product->getName();
        $productTitle = $this->trimAttribute(self::ATTR_NAME, $title);

        $description = $description ?: $productTitle;
        // description can't be all uppercase
        $description = $this->builderTools->htmlDecode($description);
        $description = addslashes($this->builderTools->lowercaseIfAllCaps($description));
        return $description;
    }

    /**
     * @param Product $product
     * @return string
     */
    protected function getCondition(Product $product)
    {
        $condition = null;
        if ($product->getData('condition')) {
            $condition = $this->trimAttribute(self::ATTR_CONDITION, $product->getAttributeText('condition'));
        }
        return ($condition && in_array($condition, ['new', 'refurbished', 'used'])) ? $condition : 'new';
    }

    /**
     * @param Product $product
     * @param $attribute
     * @return string|false
     */
    private function getCorrectText(Product $product, $attribute)
    {
        if ($product->getData($attribute)) {
            $text = $product->getAttributeText($attribute);
            if (!$text) {
                $text = $product->getData($attribute);
            }
            return $text;
        }
        return false;
    }

    /**
     * @param Product $product
     * @return string|null
     */
    protected function getBrand(Product $product)
    {
        $brand = $this->getCorrectText($product, 'brand');
        if (!$brand) {
            $brand = $this->getCorrectText($product, 'manufacturer');
        }
        if (!$brand) {
            $brand = $this->getDefaultBrand();
        }
        return $this->trimAttribute(self::ATTR_BRAND, $brand);
    }

    /**
     * @param Product $product
     * @return string
     */
    protected function getItemGroupId(Product $product)
    {
        $configurableSettings = $product->getConfigurableSettings() ?: [];
        return array_key_exists('item_group_id', $configurableSettings) ? $configurableSettings['item_group_id'] : '';
    }

    /**
     * @param Product $product
     * @return string
     */
    protected function getColor(Product $product)
    {
        $configurableSettings = $product->getConfigurableSettings() ?: [];
        return array_key_exists('color', $configurableSettings) ? $configurableSettings['color'] : '';
    }

    /**
     * @param $product
     * @return string
     */
    protected function getSize($product)
    {
        $configurableSettings = $product->getConfigurableSettings() ?: [];
        return array_key_exists('size', $configurableSettings) ? $configurableSettings['size'] : '';
    }

    /**
     * @param Product $product
     * @return array
     * @throws LocalizedException
     */
    public function buildProductEntry(Product $product)
    {
        $this->inventory->initInventoryForProduct($product);

        $productType = $this->trimAttribute(self::ATTR_PRODUCT_TYPE, $this->getCategoryPath($product));

        $title = $product->getName();
        $productTitle = $this->trimAttribute(self::ATTR_NAME, $title);

        $images = $this->getProductImages($product);
        $imageUrl = $this->trimAttribute(self::ATTR_IMAGE_URL, $images['main_image']);

        $entry = [
            self::ATTR_RETAILER_ID          => $this->trimAttribute(self::ATTR_RETAILER_ID, $product->getId()),
            self::ATTR_ITEM_GROUP_ID        => $this->getItemGroupId($product),
            self::ATTR_NAME                 => $productTitle,
            self::ATTR_DESCRIPTION          => $this->getDescription($product),
            self::ATTR_AVAILABILITY         => $this->inventory->getAvailability(),
            self::ATTR_INVENTORY            => $this->inventory->getInventory(),
            self::ATTR_BRAND                => $this->getBrand($product),
            self::ATTR_PRODUCT_CATEGORY     => $product->getGoogleProductCategory() ?? '',
            self::ATTR_PRODUCT_TYPE         => $productType,
            self::ATTR_CONDITION            => $this->getCondition($product),
            self::ATTR_PRICE                => $this->getProductPrice($product),
            self::ATTR_SALE_PRICE           => $this->getProductSalePrice($product),
            self::ATTR_COLOR                => $this->getColor($product),
            self::ATTR_SIZE                 => $this->getSize($product),
            self::ATTR_URL                  => $this->getProductUrl($product),
            self::ATTR_IMAGE_URL            => $imageUrl,
            self::ATTR_ADDITIONAL_IMAGE_URL => $images['additional_images'],
        ];

        $this->enhancedCatalogHelper->assignECAttribute($product, $entry);

        return $entry;
    }
}
