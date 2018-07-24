<?php

namespace Luma\Campaignmonitor\Observer;

class UpdateCustomFields implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $backendSession;

    /**
     * @var \Luma\Campaignmonitor\Model\ApiFactory
     */
    protected $apiFactory;

    public function __construct(
        \Magento\Backend\Model\Session $backendSession,
        \Luma\Campaignmonitor\Model\ApiFactory $apiFactory
    ) {
        $this->backendSession = $backendSession;
        $this->apiFactory = $apiFactory;
    }

    /**
     * Sync Campaignmonitor custom field
     *
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $error = $this->apiFactory->create()->createCustomerCustomFields(0);
        if(count($error) > 0){
            foreach ($error as $key => $msg) {
                $this->backendSession->addError($msg);
            }
        }
    }
}
