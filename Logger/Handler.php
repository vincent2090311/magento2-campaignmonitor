<?php
/**
 * Copyright Talisman Innovations Ltd. (2021). All rights reserved.
 */

namespace Luma\Campaignmonitor\Logger;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class Handler extends Base
{
    const LOGFILE = "campaignmonitor.log";

    protected $loggerType = Logger::INFO;

    protected $fileName = '/var/log/' . self::LOGFILE;
}