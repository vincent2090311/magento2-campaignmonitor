<?php

namespace Luma\Campaignmonitor\Model\Config;

class ListSelection
{
    protected $request;
    protected $storeManager;
    protected $helperData;
    protected $apiFactory;

    public function __construct(
        \Magento\Framework\App\Request\Http $request,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Luma\Campaignmonitor\Model\ApiFactory $apiFactory,
        \Luma\Campaignmonitor\Helper\Data $helperData
    ) {
        $this->request = $request;
        $this->storeManager = $storeManager;
        $this->helperData = $helperData;
        $this->apiFactory = $apiFactory;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $storeId = '';
        $data = $this->request->getParams();
        if(array_key_exists(\Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE, $data)){
            $websiteId = $data[\Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE];
            $storeId = $this->storeManager->getWebsite($websiteId)->getDefaultGroup()->getDefaultStoreId();
        } elseif (array_key_exists(\Magento\Store\Model\ScopeInterface::SCOPE_STORE, $data)) {
            $storeId = $data[\Magento\Store\Model\ScopeInterface::SCOPE_STORE];
        } else {
            $storeId = 0;
        }

        $reply = $this->apiFactory->create()->getLists($storeId);

        $lists = [];
        if ($reply['success'] === false) {
            $this->helperData->log($reply);
            $lists[] = [
                'value'     => 'error',
                'label'     => \Luma\Campaignmonitor\Model\CampaignMonitor::LABEL_ENTER_YOUR_API_KEY,
                'message'   => sprintf(\Luma\Campaignmonitor\Model\CampaignMonitor::ERR_API_REQUEST, $reply['data']['Message'])
            ];
        } else {
            foreach ($reply['data'] as $client) {
                $lists[] = [
                    'value'  => $client['ListID'],
                    'label'  => $client['Name']
                ];
            }
        }
        
        return $lists;
    }
}