<?php
/**
 * Copyright Talisman Innovations Ltd. (2021). All rights reserved.
 */

namespace Luma\Campaignmonitor\Logger;

class Handler
{
    const LOGFILE = "campaignmonitor.log";

    protected $loggerType = Logger::INFO;

    protected $fileName = '/var/log/' . self::LOGFILE;
}