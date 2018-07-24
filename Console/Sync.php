<?php

namespace Luma\Campaignmonitor\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Sync
 */
class Sync extends Command
{
    private $objectManagerFactory;
    private $objectManager;

    public function __construct(
        \Magento\Framework\App\ObjectManagerFactory $objectManagerFactory
    ) {
        $this->objectManagerFactory = $objectManagerFactory;
        parent::__construct();
    }

    protected function getObjectManager()
    {
        if (null == $this->objectManager) {
            $area = \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE;
            $this->objectManager = $this->objectManagerFactory->create($_SERVER);
            /** @var \Magento\Framework\App\State $appState */
            $appState = $this->objectManager->get('Magento\Framework\App\State');
            $appState->setAreaCode($area);
            $configLoader = $this->objectManager->get('Magento\Framework\ObjectManager\ConfigLoaderInterface');
            $this->objectManager->configure($configLoader->load($area));
        }
        return $this->objectManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('campaignmonitor')->setDescription('Sync Campaign Monitor Data');
        $this->addArgument('type');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $objectManager = $this->getObjectManager();

        switch ($input->getArgument('type')) {
            case 'magento':
                $magentoSync = $objectManager->create('Luma\Campaignmonitor\Cron\SynchroniseFromMagento');
                $magentoSync->execute();
                break;
            default:
                $cmSync = $objectManager->create('Luma\Campaignmonitor\Cron\SynchroniseFromCm');
                $cmSync->execute();
                break;
        }
    }
}