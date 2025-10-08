# bconnect-magento-amsociallogin
Use b.connect with Amasty social login on Magento/Adobe Commerce

### How to configure:
Go to Store > Configurations > Amasty > Social Login
Enable Bconnect at the bottom
Simply fill the 3 first fields (client id, client secret, prod mode), and the last one (sort order)
All other advanced settings are pre-filled with default values

### Installation:

Drop the content into app/code/Bconnect/Amsociallogin

Enable the module with the following command:
```bash
bin/magento module:enable Bconnect_Amsociallogin
```

Once the module is enabled, run the setup upgrades:
```bash
bin/magento setup:upgrade
```

If necessary, deploy the static content:
```bash
bin/magento setup:static-content:deploy
```

And finally, flush Magento 2 cache:
```bash
bin/magento cache:flush
```
