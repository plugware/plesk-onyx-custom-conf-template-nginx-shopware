<?php
/**
 * @var Template_VariableAccessor $VAR
 * @var array $OPT
 */
?>
server {
    listen <?php echo $OPT['ipAddress']->escapedAddress . ':' . $OPT['frontendPort'] .
        ($OPT['default'] ? ' default_server' : '') . ($OPT['ssl'] ? ' ssl' : '') .
        ($OPT['ssl'] && $VAR->domain->physicalHosting->proxySettings['nginxHttp2'] ? ' http2' : '') ?>;

    server_name <?php echo $VAR->domain->asciiName ?>;
<?php if ($VAR->domain->isWildcard): ?>
    server_name ~^<?php echo $VAR->domain->pcreName ?>$;
<?php else: ?>
    server_name www.<?php echo $VAR->domain->asciiName ?>;
    <?php if ($OPT['ipAddress']->isIpV6()): ?>
    server_name ipv6.<?php echo $VAR->domain->asciiName ?>;
    <?php else: ?>
    server_name ipv4.<?php echo $VAR->domain->asciiName ?>;
    <?php endif ?>
<?php endif ?>
<?php if ($VAR->domain->webAliases): ?>
    <?php foreach ($VAR->domain->webAliases as $alias): ?>
    server_name <?php echo $alias->asciiName ?>;
    server_name www.<?php echo $alias->asciiName ?>;
    <?php endforeach ?>
<?php endif ?>
<?php if ($VAR->domain->previewDomainName): ?>
    server_name "<?php echo $VAR->domain->previewDomainName ?>";
<?php endif ?>

<?php if ($OPT['ssl']): ?>
<?php $sslCertificate = $VAR->server->sni && $VAR->domain->physicalHosting->sslCertificate ?
    $VAR->domain->physicalHosting->sslCertificate :
    $OPT['ipAddress']->sslCertificate; ?>
    <?php if ($sslCertificate->ce): ?>
    ssl_certificate             <?php echo $sslCertificate->ceFilePath ?>;
    ssl_certificate_key         <?php echo $sslCertificate->ceFilePath ?>;
        <?php if ($sslCertificate->ca): ?>
    ssl_client_certificate      <?php echo $sslCertificate->caFilePath ?>;
        <?php endif ?>
    <?php endif ?>
<?php endif ?>

<?php if (!empty($VAR->domain->physicalHosting->proxySettings['nginxClientMaxBodySize'])): ?>
    client_max_body_size <?php echo $VAR->domain->physicalHosting->proxySettings['nginxClientMaxBodySize'] ?>;
<?php endif ?>

<?php if ($VAR->domain->physicalHosting->scriptTimeout): ?>
    proxy_read_timeout <?php echo min($VAR->domain->physicalHosting->scriptTimeout, 2147483) ?>;
<?php endif ?>

<?php if (!$OPT['ssl'] && $VAR->domain->physicalHosting->ssl && $VAR->domain->physicalHosting->sslRedirect): ?>

<?php echo $VAR->includeTemplate('domain/service/nginxSeoSafeRedirects.php', array('ssl' => true)) ?>

        return 301 https://$host$request_uri;
    }
    <?php return ?>
<?php endif ?>

    root "<?php echo $OPT['ssl'] ? $VAR->domain->physicalHosting->httpsDir : $VAR->domain->physicalHosting->httpDir ?>";
    access_log "<?php echo $VAR->domain->physicalHosting->logsDir . '/' . ($OPT['ssl'] ? 'proxy_access_ssl_log' : 'proxy_access_log') ?>";
    error_log "<?php echo $VAR->domain->physicalHosting->logsDir . '/proxy_error_log' ?>";

<?php if ($OPT['default']): ?>
    <?php echo $VAR->includeTemplate('service/nginxSitePreview.php') ?>
<?php endif ?>

<?php echo $VAR->domain->physicalHosting->proxySettings['allowDeny'] ?>

<?=$VAR->includeTemplate('domain/service/nginxSeoSafeRedirects.php', $OPT)?>

<?=$VAR->includeTemplate('domain/service/nginxCache.php', $OPT)?>

<?php echo $VAR->domain->physicalHosting->nginxExtensionsConfigs ?>

<?php if ($VAR->domain->physicalHosting->proxySettings['nginxProxyMode']): ?>
    location / {
    <?php echo $VAR->includeTemplate('domain/service/proxy.php', $OPT) ?>
    }

    <?php if (!$VAR->domain->physicalHosting->proxySettings['nginxTransparentMode'] && !$VAR->domain->physicalHosting->proxySettings['nginxServeStatic']): ?>
    location /internal-nginx-static-location/ {
        alias <?php echo $OPT['documentRoot'] ?>/;
        internal;
        <?php if ($VAR->domain->physicalHosting->expires && $VAR->domain->physicalHosting->expiresStaticOnly): ?>
        expires <?=$VAR->quote($VAR->domain->physicalHosting->expires)?>;
        <?php endif ?>
    }
    <?php endif ?>
<?php endif ?>

<?php if ($VAR->domain->active && $VAR->domain->physicalHosting->hasWebstat): ?>
    <?php echo $VAR->includeTemplate('domain/service/nginxWebstatDirectories.php', $OPT) ?>
<?php endif ?>

<?php if ($VAR->domain->active && !$VAR->domain->physicalHosting->proxySettings['nginxProxyMode']): ?>
    <?php echo $VAR->includeTemplate('domain/service/nginxProtectedDirectories.php', $OPT) ?>
<?php else: ?>
    <?php echo $VAR->includeTemplate('domain/service/nginxProtectedDirectoriesProxy.php', $OPT) ?>
<?php endif ?>

<?php if ($VAR->domain->active && $VAR->domain->physicalHosting->proxySettings['fileSharingPrefix']
    && $VAR->domain->physicalHosting->proxySettings['nginxProxyMode']): ?>
    location ~ "^/<?php echo $VAR->domain->physicalHosting->proxySettings['fileSharingPrefix'] ?>/" {
        <?=$VAR->includeTemplate('domain/service/proxy.php', $OPT + ['nginxCacheEnabled' => false])?>
    }
<?php endif ?>

<?php if ($VAR->domain->active && $VAR->domain->physicalHosting->proxySettings['nginxServeStatic']): ?>
    location @fallback {
        <?php if ($VAR->domain->physicalHosting->proxySettings['nginxProxyMode']): ?>
            <?php echo $VAR->includeTemplate('domain/service/proxy.php', $OPT) ?>
        <?php else: ?>
        return 404;
        <?php endif ?>
    }

    location ~ ^/(.*\.(<?php echo $VAR->domain->physicalHosting->proxySettings['nginxStaticExtensions'] ?>))$ {
        try_files $uri @fallback;
        <?php if ($VAR->domain->physicalHosting->expires && $VAR->domain->physicalHosting->expiresStaticOnly): ?>
        expires <?=$VAR->quote($VAR->domain->physicalHosting->expires)?>;
        <?php endif ?>
    }
<?php endif ?>

<?php if ($VAR->domain->active && $VAR->domain->physicalHosting->php && $VAR->domain->physicalHosting->proxySettings['nginxServePhp']): ?>
    location ~ ^/~(.+?)(/.*?\.php)(/.*)?$ {
        alias <?php echo $VAR->domain->physicalHosting->webUsersDir ?>/$1/$2;
        <?php echo $VAR->includeTemplate('domain/service/fpm.php', $OPT) ?>
    }

    <?php if ($VAR->domain->physicalHosting->proxySettings['nginxProxyMode']): ?>
    location ~ ^/~(.+?)(/.*)?$ {
            <?php echo $VAR->includeTemplate('domain/service/proxy.php', $OPT) ?>
    }
    <?php endif ?>

    location ~ \.php(/.*)?$ {
        <?php if (in_array('shopware.php', $VAR->domain->physicalHosting->directoryIndex)): ?>
        <?php echo $VAR->includeTemplate('domain/service/shopwarefpm.php', $OPT) ?>
        <? else: ?>
        <?php echo $VAR->includeTemplate('domain/service/fpm.php', $OPT) ?>
        <?php endif ?>
    }
    <?php if (in_array('shopware.php', $VAR->domain->physicalHosting->directoryIndex)): ?>

    location = /favicon.ico {
        log_not_found off;
        access_log off;
    }

    ## Deny all attempts to access hidden files such as .env, .htaccess, .htpasswd, .DS_Store (Mac).
    location ~ /\. {
        return 404;
    }

    ## Deny all attems to access possible configuration files
    location ~ \.(tpl|yml|ini|log)$ {
        return 404;
    }

    ## Deny access to media upload folder
    location ^~ /media/temp/ {
        return 404;
    }

    # Shopware caches and logs
    location ^~ /var/ {
        return 404;
    }

    # Deny access to root files
    location ~ (autoload\.php|composer\.(json|lock|phar)|CONTRIBUTING\.md|eula.*\.txt|license\.txt|README\.md|UPGRADE-(.*)\.md|.*\.dist)$ {
        return 404;
    }

    # Restrict access to shop configs files
    location ~ /(web\/cache\/(config_\d+\.json|all.less))$ {
        return 404;
    }

    # Restrict access to theme configurations
    location ~ /themes/(.*)(.*\.lock|package\.json|Gruntfile\.js|all\.less)$ {
        return 404;
    }

    location ^~ /files/documents/ {
        return 404;
    }

    # Block direct access to ESDs, but allow the follwing download options:
    #  * 'PHP' (slow)
    #  * 'X-Accel' (optimized)
    # Also see http://wiki.shopware.com/ESD_detail_1116.html#Ab_Shopware_4.2.2
    # With Shopware 5.5 a esdKey will be generated in the installation process, please consider changing this value
    location ^~ /files/552211cce724117c3178e3d22bec532ec/ {
        internal;
    }

    # Shopware install / update
    location /recovery/install {
        index index.php;
        try_files $uri /recovery/install/index.php$is_args$args;
    }

    location /recovery/update/ {
        location /recovery/update/assets {
        }
        if (!-e $request_filename){
            rewrite . /recovery/update/index.php last;
        }
    }
    <?php endif ?>

    <?php if ($VAR->domain->physicalHosting->directoryIndex): ?>
    <?php if (in_array('shopware.php', $VAR->domain->physicalHosting->directoryIndex)): ?>
    location / {
        location ~* "^/themes/Frontend/(?:.+)/frontend/_public/(?:.+)\.(?:ttf|eot|svg|woff|woff2)$" {
            expires max;
            add_header Cache-Control "public";
            access_log off;
            log_not_found off;
        }

        location ~* "^/web/cache/(?:[0-9]{10})_(?:.+)\.(?:js|css)$" {
            expires max;
            add_header Cache-Control "public";
            access_log off;
            log_not_found off;
        }

        ## All static files will be served directly.
        location ~* ^.+\.(?:css|cur|js|jpe?g|gif|ico|png|svg|webp|html)$ {
            ## Defining rewrite rules
            rewrite files/documents/.* /engine last;
            rewrite backend/media/(.*) /media/$1 last;

            expires 1w;
            add_header Cache-Control "public, must-revalidate, proxy-revalidate";

            access_log off;
            # The directive enables or disables messages in error_log about files not found on disk.
            log_not_found off;

            tcp_nodelay off;
            ## Set the OS file cache.
            open_file_cache max=3000 inactive=120s;
            open_file_cache_valid 45s;
            open_file_cache_min_uses 2;
            open_file_cache_errors off;

            ## Fallback to shopware
            ## comment in if needed
            try_files $uri /shopware.php?controller=Media&action=fallback;
        }

        index <?=$VAR->quote($VAR->domain->physicalHosting->directoryIndex)?>;
        try_files $uri $uri/ /shopware.php$is_args$args;
    }
    <?php else: ?>
    location ~ /$ {
        index <?=$VAR->quote($VAR->domain->physicalHosting->directoryIndex)?>;
    }
    <?php endif ?>
    <?php endif ?>
    <?php if (in_array('shopware.php', $VAR->domain->physicalHosting->directoryIndex)): ?>

    ## XML Sitemap support.
        location = /sitemap.xml {
        log_not_found off;
        access_log off;
        try_files $uri @shopware;
    }

    ## XML SitemapMobile support.
        location = /sitemapMobile.xml {
        log_not_found off;
        access_log off;
        try_files $uri @shopware;
    }

    ## robots.txt support.
        location = /robots.txt {
        log_not_found off;
        access_log off;
        try_files $uri @shopware;
    }

    location @shopware {
        rewrite / /shopware.php;
    }
    <?php endif ?>
<?php endif ?>

<?php if ($VAR->domain->physicalHosting->restrictFollowSymLinks): ?>
    disable_symlinks if_not_owner from=$document_root;
<?php endif ?>

<?php if ($VAR->domain->physicalHosting->expires && !$VAR->domain->physicalHosting->expiresStaticOnly): ?>
    expires <?=$VAR->quote($VAR->domain->physicalHosting->expires)?>;
<?php endif ?>

<?php foreach ((array)$VAR->domain->physicalHosting->headers as list($name, $value)): ?>
    add_header <?=$VAR->quote([$name, $value])?>;
<?php endforeach ?>
    add_header X-Powered-By Shopware;

<?php if (is_file($VAR->domain->physicalHosting->customNginxConfigFile)): ?>
    include "<?php echo $VAR->domain->physicalHosting->customNginxConfigFile ?>";
<?php endif ?>
}
