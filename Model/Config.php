<?php

namespace Aligent\EventBridge\Model;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\CacheInterface;

class Config
{
    const XML_PATH_AWS_ACCESS_KEY = 'aligent_eventbridge/credentials/access_key_id';
    const XML_PATH_AWS_SECRET_ACCESS_KEY = 'aligent_eventbridge/credentials/secret_access_key';
    const XML_PATH_AWS_REGION = 'aligent_eventbridge/options/region';
    const XML_PATH_EVENT_SOURCE = 'aligent_eventbridge/options/source';
    const XML_PATH_EVENT_BUS = 'aligent_eventbridge/options/event_bus';

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * Config constructor.
     *
     * @param StoreManagerInterface $storeManager
     * @param CacheInterface $cache
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        CacheInterface        $cache,
    )
    {
        $this->storeManager = $storeManager;
        $this->cache = $cache;
    }

    /**
     * @inheritdoc
     */
    public function getAWSKeyId()
    {
         return $this->scopeConfig->getValue(self::XML_PATH_AWS_ACCESS_KEY, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * @inheritdoc
     */
    public function getAWSSecretKey()

    {
         return $this->scopeConfig->getValue(self::XML_PATH_AWS_SECRET_ACCESS_KEY, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * @inheritdoc
     */
    public function getAWSRegion()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_AWS_REGION, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * @inheritdoc
     */
    public function getEventBridgeBus()
    {
        $bus = $this->scopeConfig->getValue(self::XML_PATH_EVENT_BUS, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if ($bus == null) {
             return 'default';
        }

        return $bus;
    }

    /**
     * @inheritdoc
     */
    public function getEventBridgeSource()
    {
        $source = $this->scopeConfig->getValue(self::XML_PATH_EVENT_SOURCE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if ($source == null) {
             $url = $this->storeManager->getStore()->getBaseUrl();
             if ($url !== null) {
                  return parse_url($url, PHP_URL_HOST);
             }
        }

        return null;
    }

}
