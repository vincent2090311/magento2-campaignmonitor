<?php

namespace Luma\Campaignmonitor\Cron;

class SynchroniseFromMagento 
{
    /**
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    protected $subscriberFactory;

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
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Luma\Campaignmonitor\Model\ApiFactory $apiFactory,
        \Luma\Campaignmonitor\Helper\Data $helperData
    ) {
        $this->subscriberFactory = $subscriberFactory;
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
                    $this->_synchroniseFromMagento($storeId);
                }
            }
        }
    }

    protected function _synchroniseFromMagento($storeId)
    {
        $listId = $this->helperData->getListId($storeId);

        /* @var Mage_Newsletter_Model_Subscriber $subscribers */
        $mageNewsletter = $this->subscriberFactory->create();

        $pagedSize = 100;
        $collection = $mageNewsletter->getCollection()
            ->addFieldToFilter('store_id', $storeId)
            ->addFieldToFilter('subscriber_status', \Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED);
        $collection->getSelect()->where("NOT FIND_IN_SET(?, cm_listid) OR cm_listid = ''", $listId);
        $collection->setPageSize($pagedSize)->setCurPage(1);
        $lastPage = $collection->getLastPageNumber();

        if (!empty($collection) && $collection->count() > 0) {
            for ($i = 1; $i <= $lastPage; $i++) {
                $collection = $mageNewsletter->getCollection()
                    ->addFieldToFilter('store_id', $storeId)
                    ->addFieldToFilter('subscriber_status', \Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED);
                $collection->getSelect()->where("NOT FIND_IN_SET(?, cm_listid) OR cm_listid = ''", $listId);
                $collection->setPageSize($pagedSize)->setCurPage($i);

                foreach ($collection as $subscriber) {
                    $email = $subscriber->getEmail();
                    try {
                        $response = $this->apiFactory->create()->subscribe($email, $storeId);
                        if($response['success']){
                            $this->helperData->updateSubscriberListId($email, $listId);
                        }
                    } catch (\Exception $e) {
                        $this->helperData->log(print_r($e->getMessage(), true));
                    }
                }
            }
        }
    }
}