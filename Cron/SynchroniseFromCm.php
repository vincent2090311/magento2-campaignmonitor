<?php

namespace Luma\Campaignmonitor\Cron;

class SynchroniseFromCm 
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezoneInterface;

    /**
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    protected $newsletterFactory;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Luma\Campaignmonitor\Model\ApiFactory
     */
    protected $apiFactory;

    /**
     * @var \Luma\Campaignmonitor\Helper\Data
     */
    protected $helperData;

    public function __construct(
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface,
        \Magento\Newsletter\Model\SubscriberFactory $newsletterFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Luma\Campaignmonitor\Model\ApiFactory $apiFactory,
        \Luma\Campaignmonitor\Helper\Data $helperData
    ) {
        $this->timezoneInterface = $timezoneInterface;
        $this->newsletterFactory = $newsletterFactory;
        $this->customerFactory = $customerFactory;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->apiFactory = $apiFactory;
        $this->helperData = $helperData;
    }

    /**
     * Execute the cron
     *
     * @return void
     **/

    public function execute() 
    {
        if($this->helperData->isCronSynchroniseEnable()){
            $stores = $this->storeManager->getStores(true);
            foreach ($stores as $storeId => $store) {
                // there is no need to sync admin store
                if ($storeId !== 0) {
                    $this->_synchroniseFromCm($storeId);
                }
            }
        }
    }

    protected function _synchroniseFromCm($storeId)
    {
        $timezone = $this->scopeConfig->getValue($this->timezoneInterface->getDefaultTimezonePath(),\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $date = new \DateTime('now', new \DateTimeZone($timezone));
        $date->sub(new \DateInterval('P1D'));

        /** @var Luma\Campaignmonitor\Model\ApiFactory $apiFactory */
        $apiFactory = $this->apiFactory->create();

        $listId = $this->helperData->getListId($storeId);
        $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();

        $activeSubscriber = $apiFactory->getCampaignmonitorSubscribers($date, $storeId);
        if ($activeSubscriber['success'] !== false) {
            foreach ($activeSubscriber['data']['Results'] as $subscriber) {
                $email = $subscriber['EmailAddress'];

                /** @var \Magento\Newsletter\Model\Subscriber $mageNewsletter */
                $mageNewsletter = $this->newsletterFactory->create();
                $mageNewsletter->loadByEmail($email);

                if (!$mageNewsletter->isSubscribed() || $mageNewsletter->getId() === null) {
                    $mageNewsletter->setSubscriberEmail($email)
                                    ->setStatus(\Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED)
                                    ->setStoreId($storeId)
                                    ->setCmListid($listId);

                    /* @var Mage_Customer_Model_Customer $customer */
                    $customer = $this->customerFactory->create()->setWebsiteId($websiteId)->loadByEmail($email);
                    if($customerId = $customer->getId()){
                        $mageNewsletter->setCustomerId($customerId);
                    }
                    $mageNewsletter->save();
                }
            }
        } else {
            $this->helperData->log(sprintf(self::ERR_API_ERROR, $activeSubscriber['data']['Message']));
        }

        $inactiveSubscriber = $apiFactory->getCampaignmonitorUnsubscribedSubscribers($date, $storeId);
        if ($inactiveSubscriber['success'] !== false) {
            foreach ($inactiveSubscriber['data']['Results'] as $subscriber) {
                $email = $subscriber['EmailAddress'];

                /** @var \Magento\Newsletter\Model\Subscriber $mageNewsletter */
                $mageNewsletter = $this->newsletterFactory->create();
                $mageNewsletter->loadByEmail($email);
                if ($mageNewsletter->getId()) {
                    $mageNewsletter->unsubscribe();
                    $this->helperData->updateSubscriberListId($mageNewsletter->getSubscriberEmail(), $listId, false);
                }
            }
        } else {
            $this->helperData->log(sprintf(self::ERR_API_ERROR, $inactiveSubscriber['data']['Message']));
        }

        $deletedSubscriber = $apiFactory->getCampaignmonitorDeletedSubscribers($date, $storeId);
        if ($deletedSubscriber['success'] !== false) {
            foreach ($deletedSubscriber['data']['Results'] as $subscriber) {
                $email = $subscriber['EmailAddress'];

                /** @var \Magento\Newsletter\Model\Subscriber $mageNewsletter */
                $mageNewsletter = $this->newsletterFactory->create();
                $mageNewsletter->loadByEmail($email);
                if ($mageNewsletter->getId()) {
                    $mageNewsletter->unsubscribe();
                    $this->helperData->updateSubscriberListId($mageNewsletter->getSubscriberEmail(), $listId, false);
                }
            }
        } else {
            $this->helperData->log(sprintf(self::ERR_API_ERROR, $deletedSubscriber['data']['Message']));
        }
    }
}