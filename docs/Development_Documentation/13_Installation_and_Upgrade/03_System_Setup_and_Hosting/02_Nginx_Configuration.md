# Nginx Configuration 

**This guide is still unfinished, it only includes the working server config not other Nginx requirements.**   

**It's highly recommended to use Apache until we definitely have finished this reference configuration**.

Installation on Nginx is entirely possible, and in my experience quite a lot faster then apache.I won't dive into how niginx is installed etc. But I will share the Nginx configuration which works.

## Configuration 

Below is the configuration for a nginx server ( just the server bit, the http etc. bit can be kept default, as long as you include mime.types ).

```
# mime types are covered in nginx.conf by:
# http {
#   include       mime.types;
# }
 
# mod_status is not (yet) available for nginx, so that directive is skipped
server {
    root /vagrant/www/pimcore;
    index index.php;
    server_name pimcore.loc;
    listen 80;
    access_log  /var/www/pimcore/website/var/log/nginx_access.log;
    error_log   /var/www/pimcore/website/var/log/nginx_error.log error;
 
    # ASSETS: check if request method is GET (because of WebDAV) and if the requested file (asset) exists on the filesystem, if both match, deliver the asset directly
    # nginx doesn't do double conditions, so we concat a teststring, if it's "AB" both checks are true
    set $getassets "";
    if ($uri ~* ^/website/var/assets)   { set $getassets "${getassets}A"; }
    if ($request_method = GET)      { set $getassets "${getassets}B"; }
    if ($getassets = "AB") {
        rewrite ^ $uri$args last;
    }
 
    # You could also use the location directive below, which is MUCH faster then the if statements above
    # However, this doen't check for request method GET
    #location ~* ^/website/var/assets {
    #   try_files $uri /index.php?$args;
    #   index index.php;
    #}
 
    # allow access to plugin-data and core assets ( done by excluding .*/static and static)
    # forbid the direct access to pimcore-internal data (eg. config-files, ...)
    # ~* = case-insensitive
    location ~* ^(/plugins/(?!.*/static).*|^/pimcore/(?!(static|modules/3rdparty)).*|/website/var/(?!tmp|assets|areas)|^.*modules/.*/static.*|^(vendor|tests|node_modules|phing)/.*|^(bower|package|composer|gulpfile)\.) {
        return 403;
    }
 
    # basic zend-framework setup see: http://framework.zend.com/manual/en/zend.controller.html
    location / {
        # First attempt to serve request as file, then as directory, then fall back to index.php
        try_files $uri $uri/ /index.php?$args;
        index index.php;
    }
 
    # pass the PHP scripts to FastCGI server listening on 127.0.0.1:9000
    location ~ \.php$ {
        # Zero-day exploit defense.
        # http://forum.nginx.org/read.php?2,88845,page=3
        # Won't work properly (404 error) if the file is not stored on this server, which is entirely possible with php-fpm/php-fcgi.
        # Comment the 'try_files' line out if you set up php-fpm/php-fcgi on another machine.  And then cross your fingers that you won't get hacked.
        try_files $uri =404;
 
 
        # This configuration depends on your set-up. The default nginx.conf contains an example for your setup.
        # fastcgi_pass 127.0.0.1:9000; (if you cannot run on a socket)
        fastcgi_pass   unix:/var/run/php5-fpm.sock;
        fastcgi_index  index.php;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_param  SCRIPT_FILENAME    $document_root$fastcgi_script_name;
        include        fastcgi_params;
        # Set a maximum execution time. This should be the same as your php_max_exec time and php-fpm pool request_terminate_timeout to prevent
        # any gateway timeouts and/or worker processes being tied up.
        fastcgi_read_timeout 60;
    }
 
 
    # cache some files
    # ~* = case-insensitive
    location ~* \.(jpe?g|gif|png|bmp|ico|css|js|pdf|zip|htm|html|docx?|xlsx?|pptx?|txt|wav|swf|avi|mp\d)$ {
        access_log off;
        log_not_found off;
        try_files $uri $uri/ /website/var/assets$uri /index.php?$args;
        expires 1w;
    }
    
    # cache-buster rule for scripts & stylesheets embedded using view helpers
    rewrite ^\/cache-buster-\d+(.*) $1 break;
}
``` 
