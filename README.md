# plesk-onyx-custom-conf-template-nginx-shopware

## How to use
```bash
cd /usr/local/psa/admin/conf/templates/
mkdir custom
cd ./custom
```

```bash
git clone https://github.com/plugware/plesk-onyx-custom-conf-template-nginx-shopware.git ./
```

Templates placed in custom overrides the default templates.

## Plesk panel - activate custom template

The custom template is triggered by ***Index Files*** configuration under **Apache & nginx Settings**.
Just add ***shopware.php*** to activate custom template blocks.

![Index Files config](plesk-panel-screenshot-01.png?raw=true)
![Index Files config](plesk-panel-screenshot-02.png?raw=true)
