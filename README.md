# plesk-onyx-custom-conf-template-nginx-shopware

## Reference: NGINX Shopware configuration by bcremer
[https://github.com/bcremer/shopware-with-nginx](https://github.com/bcremer/shopware-with-nginx)

## Customization
- Added WebP Support - static files
- Added woff2 Support - cache
- Refactor nginx location configuration to match custom theme fonts - regex 

## How to use
```bash
cd /usr/local/psa/admin/conf/templates/
mkdir custom
cd ./custom
```

```bash
git clone https://github.com/plugware/plesk-onyx-custom-conf-template-nginx-shopware.git ./
```

Templates placed in /usr/local/psa/admin/conf/templates/custom/ overrides templates in /usr/local/psa/admin/conf/templates/default/.

## Plesk panel - activate custom template

The custom template is triggered by ***Index Files*** and ***Proxy mode*** configuration under **Apache & nginx Settings**.
Just add ***shopware.php*** and uncheck ***Proxy mode*** to activate custom template blocks.

![Index Files config](plesk-panel-screenshot-01.png?raw=true)
![Index Files config](plesk-panel-screenshot-02.png?raw=true)
