# Nginx Configuration

Installation on Nginx is entirely possible, and in our experience quite a lot faster than apache. This section won't dive into how Nginx is installed etc, but will show a working Nginx configuration.

_Note:_ At time of writing this config snippet doesn't care about WebDAV at all.

## Configuration

Below is the configuration for a Nginx server (just the server part, the http etc. part can be kept default, as long as mime.types are included).

Assumptions - change them to match your environment/distro:

- Pimcore 5 was installed into: `/var/www/pimcore`; therefore, the Document-Root is: `/var/www/pimcore/web`
- Logfiles are written to the default location `/var/log/nginx`. If you prefer to have the logs together with the Pimcore Logs: these are in `/var/www/pimcore/var/logs`.
- PHP-FPM is configured to listen on the Socket `/var/run/php/pimcore5.sock`. If your setup differs, change the `server` directive within the `upstream` block accordingly.
- Before you change the order of location blocks, read [Understanding Nginx Server and Location Block Selection Algorithms](https://www.digitalocean.com/community/tutorials/understanding-nginx-server-and-location-block-selection-algorithms)
- Assets are set to expire after 14 days; adjust all `expires` directives to suit your needs.

```nginx
# mime types are covered in nginx.conf by:
# http {
#   include       mime.types;
# }

upstream php-pimcore5 {
    server unix:/var/run/php/pimcore5.sock;
}

server {
    listen 80;
    server_name pimcore.loc;
    root /var/www/pimcore/web;
    index index.php;

    access_log  /var/log/access.log;
    error_log   /var/log/error.log error;

    # Pimcore Head-Link Cache-Busting
    rewrite ^/cache-buster-(?:\d+)/(.*) /$1 last;

    # Stay secure
    #
    # a) don't allow PHP in folders allowing file uploads
    location ~* /var/assets/*\.php(/|$) {
        return 404;
    }
    # b) Prevent clients from accessing hidden files (starting with a dot)
    # Access to `/.well-known/` is allowed.
    # https://www.mnot.net/blog/2010/04/07/well-known
    # https://tools.ietf.org/html/rfc5785
    location ~* /\.(?!well-known/) {
        deny all;
        log_not_found off;
        access_log off;
    }
    # c) Prevent clients from accessing to backup/config/source files
    location ~* (?:\.(?:bak|conf(ig)?|dist|fla|in[ci]|log|psd|sh|sql|sw[op])|~)$ {
        deny all;
    }

    # Thumbnails
    location ~* .*/(image|video)-thumb__\d+__.* {
        try_files /var/tmp/$1-thumbnails$request_uri /app.php;
        expires 2w;
        access_log off;
        add_header Cache-Control "public";
    }

    # Assets
    # Still use a whitelist approach to prevent each and every missing asset to go through the PHP Engine.
    location ~* (.+?)\.((?:css|js)(?:\.map)?|jpe?g|gif|png|svgz?|eps|exe|gz|zip|mp\d|ogg|ogv|webm|pdf|docx?|xlsx?|pptx?)$ {
        try_files /var/assets$uri $uri =404;
        expires 2w;
        access_log off;
        log_not_found off;
        add_header Cache-Control "public";
    }

    # Installer
    # Remove this if you don't need the web installer (anymore)
    if (-f $document_root/install.php) {
        rewrite ^/install(/?.*) /install.php$1 last;
    }

    location / {
        error_page 404 /meta/404;
        add_header "X-UA-Compatible" "IE=edge";
        try_files $uri /app.php$is_args$args;
    }

    # Use this location when the installer has to be run
    # location ~ /(app|install)\.php(/|$) {
    #
    # Use this after initial install is done:
    location ~ ^/app\.php(/|$) {
        send_timeout 1800;
        fastcgi_read_timeout 1800;
        # regex to split $uri to $fastcgi_script_name and $fastcgi_path
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        # Check that the PHP script exists before passing it
        try_files $fastcgi_script_name =404;
        include fastcgi.conf;
        # Bypass the fact that try_files resets $fastcgi_path_info
        # see: http://trac.nginx.org/nginx/ticket/321
        set $path_info $fastcgi_path_info;
        fastcgi_param PATH_INFO $path_info;

        # Activate these, if using Symlinks and opcache
        # fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        # fastcgi_param DOCUMENT_ROOT $realpath_root;

        fastcgi_pass php-pimcore5;
        # Prevents URIs that include the front controller. This will 404:
        # http://domain.tld/app.php/some-path
        # Remove the internal directive to allow URIs like this
        internal;
    }

    # PHP-FPM Status and Ping
    location /fpm- {
        access_log off;
        include fastcgi_params;
        location /fpm-status {
            allow 127.0.0.1;
            # add additional IP's or Ranges
            deny all;
            fastcgi_pass php-pimcore5;
        }
        location /fpm-ping {
            fastcgi_pass php-pimcore5;
        }
    }
    # nginx Status
    # see: https://nginx.org/en/docs/http/ngx_http_stub_status_module.html
    location /nginx-status {
        allow 127.0.0.1;
        deny all;
        access_log off;
        stub_status;
    }
}
```
