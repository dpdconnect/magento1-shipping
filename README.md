magento-DPD_Shipping
====================


composer.json:
{
    "require": {
        "magento-hackathon/magento-composer-installer": "3.0.*",
        "dpdconnect/magento1-shipping": "^1.0"
    },
    "repositories": [
        {
            "type": "composer",
            "url": "https://packages.firegento.com"
        }
    ],
    "extra": {
        "magento-root-dir": "./",
        "magento-deploystrategy":"copy",
        "magento-force": true
    }
}
