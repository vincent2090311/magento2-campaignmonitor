# Campaign Monitor connector for Magento 2

This extension is entirely based on the official Campaign Monitor extension https://github.com/campaignmonitor/magento-extension. But this module can work with Magento2

## Install

1. Via Github
    + Clone code from repository
    + Copy content in folder src to your_installation_folder/app/code/Luma/Campaignmonitor
    + Run these commands 

    ```
    $ php bin/magento setup:upgrade 
    $ php bin/magento setup:static-content:deploy
    $ php bin/magento cache:clean
    ```
2. Via composer
    composer require luma/module-campaignmonitor

## Usage

* Register a Campaign Monitor account to get API key and ClientID
* Add your API key and ClientID to admin > Stores > Configuration > Campaign Monitor > General > API

## Note
I haven't implemented all feature from Campaign Monitor to this module. I will improve it in future.
