<?php

namespace Facebook\BusinessExtension\Model\System;

use Magento\Config\Model\ResourceModel\Config as ResourceConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Config
{
    const MODULE_NAME = 'Facebook_BusinessExtension';

    const XML_PATH_FACEBOOK_BUSINESS_EXTENSION_ACTIVE = 'facebook/business_extension/active';

    const XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PAGE_ID = 'facebook/business_extension/page_id';
    const XML_PATH_FACEBOOK_BUSINESS_EXTENSION_CATALOG_ID = 'facebook/business_extension/catalog_id';
    const XML_PATH_FACEBOOK_BUSINESS_EXTENSION_COMMERCE_ACCOUNT_ID = 'facebook/business_extension/commerce_account_id';
    const XML_PATH_FACEBOOK_SHIPPING_METHODS_STANDARD = 'facebook/shipping_methods/standard';
    const XML_PATH_FACEBOOK_SHIPPING_METHODS_EXPEDITED = 'facebook/shipping_methods/expedited';
    const XML_PATH_FACEBOOK_SHIPPING_METHODS_RUSH = 'facebook/shipping_methods/rush';

    const XML_PATH_FACEBOOK_BUSINESS_EXTENSION_ACCESS_TOKEN = 'facebook/business_extension/access_token';

    const XML_PATH_FACEBOOK_BUSINESS_EXTENSION_INCREMENTAL_PRODUCT_UPDATES = 'facebook/catalog_management/incremental_product_updates';

    const XML_PATH_FACEBOOK_ORDERS_SYNC_ACTIVE = 'facebook/orders_sync/active';

    const XML_PATH_FACEBOOK_BUSINESS_EXTENSION_DEBUG_LOG_API_CALLS = 'facebook/debug/log_api_calls';

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ResourceConfig
     */
    private $resourceConfig;

    /**
     * @var ModuleListInterface
     */
    private $moduleList;

    /**
     * @method __construct
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param  ModuleListInterface $moduleList
     * @param ResourceConfig $resourceConfig
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        ResourceConfig $resourceConfig,
        ModuleListInterface $moduleList
    )
    {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->resourceConfig = $resourceConfig;
        $this->moduleList = $moduleList;
    }

    public function getModuleVersion()
    {
        return $this->moduleList->getOne(self::MODULE_NAME)['setup_version'];
    }

    public function getCommerceManagerUrl()
    {
        return sprintf('https://www.facebook.com/commerce_manager/%s', $this->getCommerceAccountId());
    }

    public function getCatalogManagerUrl()
    {
        return sprintf('https://www.facebook.com/products/catalogs/%s/products', $this->getCatalogId());
    }

    public function getSupportUrl()
    {
        return sprintf('https://www.facebook.com/commerce_manager/%s/support/', $this->getCommerceAccountId());
    }

    /**
     * @method isSingleStoreMode
     * @return bool
     */
    public function isSingleStoreMode()
    {
        return $this->storeManager->isSingleStoreMode();
    }

    /**
     * @param null $scopeId
     * @param null $scope
     * @return bool
     */
    public function isActiveExtension($scopeId = null, $scope = null)
    {
        return (bool)$this->getConfig(self::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_ACTIVE, $scopeId, $scope);
    }

    /**
     * @param null $scopeId
     * @param null $scope
     * @return bool
     */
    public function isActiveIncrementalProductUpdates($scopeId = null, $scope = null)
    {
        return (bool)$this->getConfig(self::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_INCREMENTAL_PRODUCT_UPDATES, $scopeId, $scope);
    }

    /**
     * @param null $scopeId
     * @param null $scope
     * @return bool
     */
    public function isActiveOrderSync($scopeId = null, $scope = null)
    {
        return (bool)$this->getConfig(self::XML_PATH_FACEBOOK_ORDERS_SYNC_ACTIVE, $scopeId, $scope);
    }

    /**
     * @param $configPath
     * @param null $scopeId
     * @param null $scope
     * @return mixed
     */
    public function getConfig($configPath, $scopeId = null, $scope = null)
    {
        if (!$scope && $this->isSingleStoreMode()) {
            return $this->scopeConfig->getValue($configPath);
        }
        try {
            $value = $this->scopeConfig->getValue($configPath, $scope ?: ScopeInterface::SCOPE_STORE, is_null($scopeId)
                ? $this->storeManager->getStore()->getId() : $scopeId);
        } catch (NoSuchEntityException $e) {
            return null;
        }
        return $value;
    }

    /**
     * @param null $scopeId
     * @param null $scope
     * @return mixed
     */
    public function getAccessToken($scopeId = null, $scope = null)
    {
        return $this->getConfig(self::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_ACCESS_TOKEN, $scopeId, $scope);
    }

    /**
     * @param null $scopeId
     * @param null $scope
     * @return mixed
     */
    public function getPageId($scopeId = null, $scope = null)
    {
        return $this->getConfig(self::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_PAGE_ID, $scopeId, $scope);
    }

    /**
     * @param null $scopeId
     * @param null $scope
     * @return mixed
     */
    public function getCatalogId($scopeId = null, $scope = null)
    {
        return $this->getConfig(self::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_CATALOG_ID, $scopeId, $scope);
    }

    /**
     * @param null $scopeId
     * @param null $scope
     * @return mixed
     */
    public function getCommerceAccountId($scopeId = null, $scope = null)
    {
        return $this->getConfig(self::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_COMMERCE_ACCOUNT_ID, $scopeId, $scope);
    }

    /**
     * @param null $scopeId
     * @param null $scope
     * @return bool
     */
    public function shouldLogApiCalls($scopeId = null, $scope = null)
    {
        return (bool)$this->getConfig(self::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_DEBUG_LOG_API_CALLS, $scopeId, $scope);
    }

    /**
     * @return array
     */
    public function getShippingMethodsMap()
    {
        return [
            'standard' => $this->getConfig(self::XML_PATH_FACEBOOK_SHIPPING_METHODS_STANDARD),
            'expedited' => $this->getConfig(self::XML_PATH_FACEBOOK_SHIPPING_METHODS_EXPEDITED),
            'rush' => $this->getConfig(self::XML_PATH_FACEBOOK_SHIPPING_METHODS_RUSH),
        ];
    }
}
