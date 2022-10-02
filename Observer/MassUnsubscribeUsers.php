<?php

namespace Luma\Campaignmonitor\Observer;

class MassUnsubscribeUsers implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    protected $subscriberFactory;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resource;

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
        \Magento\Framework\App\ResourceConnection $resource,
        \Luma\Campaignmonitor\Model\ApiFactory $apiFactory,
        \Luma\Campaignmonitor\Helper\Data $helperData
    ) {
        $this->subscriberFactory = $subscriberFactory;
        $this->resource = $resource;
        $this->apiFactory = $apiFactory;
        $this->helperData = $helperData;
    }

    /**
     * Mass sync subscribers data
     *
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $subscribers = $observer->getEvent()->getControllerAction()->getRequest()->getPost('subscriber');
        if (!is_array($subscribers)) {
            $subscribers = explode(',', $subscribers);
        }
        foreach ($subscribers as $id) {
            /** @var \Magento\Newsletter\Model\Subscriber $newsletter */
            $newsletter = $this->subscriberFactory->create();
            $newsletter->load($id);

            $listId = $this->helperData->getListId($newsletter->getStoreId());
            try {
                $this->apiFactory->create()->unsubscribe($newsletter->getSubscriberEmail(), $newsletter->getStoreId());
                $this->helperData->updateSubscriberListId($newsletter->getSubscriberEmail(), $listId, false);
            } catch (\Exception $e) {
            }
        }
    }
}
