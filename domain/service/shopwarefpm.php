<?php
/**
 * @var Template_VariableAccessor $VAR
 * @var array $OPT
 */
?>
        try_files $uri $uri/ =404;
        ## NOTE: You should have "cgi.fix_pathinfo = 0;" in php.ini
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        fastcgi_pass "<?php echo $VAR->domain->physicalHosting->fpmSocket ?>";
        include /etc/nginx/fastcgi.conf;

        ## required for upstream keepalive
        # disabled due to failed connections
        #fastcgi_keep_conn on;

        # Mitigate httpoxy vulnerability, see: https://httpoxy.org/
        fastcgi_param HTTP_PROXY "";

        fastcgi_buffers 8 16k;
        fastcgi_buffer_size 32k;

        client_max_body_size 24M;
        client_body_buffer_size 128k;

        fastcgi_hide_header X-Powered-By;

<?php if ($OPT['nginxCacheEnabled'] ?? true): ?>
    <?=$VAR->includeTemplate('domain/service/nginxCacheFastCgi.php', $OPT)?>
<?php endif ?>
