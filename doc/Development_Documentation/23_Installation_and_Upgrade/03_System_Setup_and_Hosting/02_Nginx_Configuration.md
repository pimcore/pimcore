# Nginx Configuration

Installation on Nginx is entirely possible, and in our experience quite a lot faster than apache. This section won't dive into how Nginx is installed etc, but will show a working Nginx configuration.

_Note:_ At time of writing this config snippet doesn't care about WebDAV at all.

## Configuration

Below is the configuration for a Nginx server (just the server part, the http etc. part can be kept default, as long as mime.types are included).

Assumptions - change them to match your environment/distro:

- Pimcore was installed into: `/var/www/pimcore`; therefore, the Document-Root is: `/var/www/pimcore/web`
- Logfiles are written to the default location `/var/log/nginx`. If you prefer to have the logs together with the Pimcore Logs: these are in `/var/www/pimcore/var/logs`.
- PHP-FPM is configured to listen on the Socket `/var/run/php/pimcore.sock`. If your setup differs, change the `server` directive within the `upstream` block accordingly.
- Before you change the order of location blocks, read [Understanding Nginx Server and Location Block Selection Algorithms](https://www.digitalocean.com/community/tutorials/understanding-nginx-server-and-location-block-selection-algorithms)
- Assets are set to expire after 14 days; adjust all `expires` directives to suit your needs.

### Development Environment

The following configuration is used with the assumption that it is for development only. It is not approperiate for a production environment and *should not* be exposed towards public access.  

```nginx
# mime types are covered in nginx.conf by:
# http {
#   include       mime.types;
# }

upstream php-pimcore6 {
    server unix:/var/run/php/pimcore.sock;
}

server {
    listen 80;
    server_name pimcore.loc;
    root /var/www/pimcore/web;
    index index.php;
    
    # Filesize depending on your data
    client_max_body_size 100m;

    # It is recommended to seclude logs per virtual host
    access_log  /var/log/access.log;
    error_log   /var/log/error.log error;

    # Protected Assets
    #
    ### 1. Option - Restricting access to certain assets completely
    #
    # location ~ ^/protected/.* {
    #   return 403;
    # }
    # location ~ ^/var/.*/protected(.*) {
    #   return 403;
    # }
    #
    # location ~ ^/cache-buster\-[\d]+/protected(.*) {
    #   return 403;
    # }
    #
    ### 2. Option - Checking permissions before delivery
    #
    # rewrite ^(/protected/.*) /app.php$is_args$args last;
    #
    # location ~ ^/var/.*/protected(.*) {
    #   return 403;
    # }
    #
    # location ~ ^/cache-buster\-[\d]+/protected(.*) {
    #   return 403;
    # }

    # Pimcore Head-Link Cache-Busting
    rewrite ^/cache-buster-(?:\d+)/(.*) /$1 last;

    # Stay secure
    #
    # a) don't allow PHP in folders allowing file uploads
    location ~* /var/assets/.*\.php(/|$) {
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

    # Some Admin Modules need this:
    # Database Admin, Server Info
    location ~* ^/admin/(adminer|external) {
        rewrite .* /app.php$is_args$args last;
    }
    
    # Thumbnails
    location ~* .*/(image|video)-thumb__\d+__.* {
        try_files /var/tmp/$1-thumbnails$uri /app.php;
        expires 2w;
        access_log off;
        add_header Cache-Control "public";
    }

    # Assets
    # Still use a whitelist approach to prevent each and every missing asset to go through the PHP Engine.
    location ~* ^(?!/admin)(.+?)\.((?:css|js)(?:\.map)?|jpe?g|gif|png|svgz?|eps|exe|gz|zip|mp\d|ogg|ogv|webm|pdf|docx?|xlsx?|pptx?)$ {
        try_files /var/assets$uri $uri =404;
        expires 2w;
        access_log off;
        log_not_found off;
        add_header Cache-Control "public";
    }

    location / {
        error_page 404 /meta/404;
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
        # include fastcgi.conf if needed
        #include fastcgi.conf;
        # Bypass the fact that try_files resets $fastcgi_path_info
        # see: http://trac.nginx.org/nginx/ticket/321
        set $path_info $fastcgi_path_info;
        fastcgi_param PATH_INFO $path_info;

        # Activate these, if using Symlinks and opcache
        # fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        # fastcgi_param DOCUMENT_ROOT $realpath_root;

        fastcgi_pass php-pimcore6;
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
            fastcgi_pass php-pimcore6;
        }
        location /fpm-ping {
            fastcgi_pass php-pimcore6;
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

### Production Environment

The following configuration provides an approperiate base for a secure application hosting. It can be adapted to your setup and preferences. However it is primarily taking security into account. It is recommended to develop within a secured environment, too.

```nginx
# mime types are covered in nginx.conf by:
# http {
#   include       mime.types;
# }

upstream php-pimcore6 {
    server unix:/var/run/php/pimcore.sock;
}

server {
    listen 80;
    listen [::]:80;

    server_name pimcore.loc;

    root /var/www/pimcore/web;

    # We accept .well-known in case of acme challenge (e.g. letsencrypt)
    # Everything else, however, is return hostname with / location
    # A good reference as explaination can be found here:
    # https://www.digitalocean.com/community/tutorials/understanding-nginx-server-and-location-block-selection-algorithms#matching-location-blocks
    location ~* /\.well-known/ {
      try_files $uri /;
    }

    # Please note that return is cheaper than redirect    
    # See: https://www.nginx.com/resources/wiki/start/topics/tutorials/config_pitfalls/#taxing-rewrites
    location / {
       return 301 https://$host$request_uri;
    } 
}

# SSL-related configuration as recommended as "intermediate" by mozilla
# See: https://ssl-config.mozilla.org/
# This configuration utilizes nginx 1.17.7, OpenSSL 1.1.1d 
# Supports: Firefox 27, Android 4.4.2, Chrome 31, Edge, IE 11 on Windows 7, Java 8u31, OpenSSL 1.0.1, Opera 20, and Safari 9

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;

    server_name pimcore.loc;

    root /var/www/pimcore/web;
    index index.php;

    # SSL Certificate and Key
    # To run letsencrypt you can use the following command:
    # certbot certonly -n --expand --nginx -d pimcore.loc
    # Depending on your OS you might need to install python-certbot-nginx
    ssl_certificate   /etc/letsencrypt/live/pimcore.loc/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/pimcore.loc/privkey.pem;

    # Verify security (if applicable) afterwards with https://ssllabs.com
    # It is recommended to cut out the following settings and include as file
    # Uncomment if applicable: 
    # include /etc/nginx/conf-available/ssl.configuration.conf;
    ### SSL Configuration
    ssl_session_timeout 1d;
    ssl_session_cache shared:MozSSL:10m;  # about 40000 sessions
    ssl_session_tickets off;

    # Run the following comment to attain the most up-to-date dhparam file
    # Please ensure that owner is set properly depending on OS (usually root) and rights are 644
    # curl https://ssl-config.mozilla.org/ffdhe2048.txt > /etc/ssl/dhparam.pem
    ssl_dhparam /etc/ssl/dhparam.pem;

    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:DHE-RSA-AES128-GCM-SHA256:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;

    # HSTS (ngx_http_headers_module is required) (63072000 seconds)
    add_header Strict-Transport-Security "max-age=63072000" always;

    # OCSP stapling
    ssl_stapling on;
    ssl_stapling_verify on;

    # verify chain of trust of OCSP response using Root CA and Intermediate certs
    # Run the following commands to get chain of trust for LetsEncrypt
    # curl https://letsencrypt.org/certs/isrgrootx1.pem.txt > /etc/ssl/letsencrypt.cot.pem \
    # && curl https://letsencrypt.org/certs/lets-encrypt-x3-cross-signed.pem.txt >> /etc/ssl/letsencrypt.cot.pem \
    # && curl https://letsencrypt.org/certs/letsencryptauthorityx3.pem.txt >> /etc/ssl/letsencrypt.cot.pem \
    # && chown root:ssl-cert /etc/ssl/letsencrypt.cot.pem && chmod 644 /etc/ssl/letsencrypt.cot.pem
    ssl_trusted_certificate /etc/ssl/letsencrypt.cot.pem;

    # replace with the IP address of your resolver
    # This can be 1.1.1.1 for example (using Cloudflares DNS service)
    # or alternatively an internal DNS Server
    resolver 127.0.0.1;
    ### SSL Configuration

    # set http headers for maximum security... as far as possible anyway.
    # Verify security (if applicable) afterwards with https://securityheaders.com/
    # It is recommended to cut out the following settings and include as file
    # Uncomment if applicable: 
    # include /etc/nginx/conf-include/http.header.configuration.conf;
    ### HTTP Header security
    # Remove token
    server_tokens off;

    # Set CSP
    # Please note that CSP are very tricky and can be quite advanced to get right
    # For most optimal security however they are absolutely mandatory
    # There are ways to 'override' them for easier development
    # However they should be carefully evaluated, defined and included 
    add_header Content-Security-Policy "default-src 'self';" always;

    # Referrer Policy
    add_header Referrer-Policy same-origin;

    # Feature Policy && Permissions Policy
    # Note that Feature Policy is to be replaced with Permissions Policy
    # See W3C Document regarding setup: https://github.com/w3c/webappsec-permissions-policy/blob/master/permissions-policy-explainer.md
    # 
    # Please check how to properly evaluate, define and include to your needs
    # Thanks to: https://fearby.com/article/set-up-feature-policy-referrer-policy-and-content-security-policy-headers-in-nginx/
    # For pre-writing these.
    add_header Feature-Policy "geolocation 'none';midi 'none';sync-xhr 'none';microphone 'none';camera 'none';magnetometer 'none';gyroscope 'none';fullscreen 'self';payment 'none';";
    add_header Permissions-Policy "geolocation=(), midi=(), sync-xhr=(), microphone=(), camera=(), magnetometer=(), gyroscope=(), fullscreen=(self), payment=()";

    # set X-Frame-Options
    add_header X-Frame-Options "SAMEORIGIN" always;

    # set Xss-Protection
    add_header X-Xss-Protection "1; mode=block" always;

    # X-Content-Type-Options 
    add_header X-Content-Type-Options "nosniff" always;
    ### HTTP Header security
    
    # Filesize depending on your data
    client_max_body_size 100m;

    # It is recommended to seclude logs per virtual host
    access_log  /var/log/access.log;
    error_log   /var/log/error.log error;

    # Protected Assets
    #
    ### 1. Option - Restricting access to certain assets completely
    #
    # location ~ ^/protected/.* {
    #   return 403;
    # }
    # location ~ ^/var/.*/protected(.*) {
    #   return 403;
    # }
    #
    # location ~ ^/cache-buster\-[\d]+/protected(.*) {
    #   return 403;
    # }
    #
    ### 2. Option - Checking permissions before delivery
    # rewrite ^(/protected/.*) /app.php$is_args$args last;
    # 
    # location ~ ^/var/.*/protected(.*) {
    #  return 403;
    # }
    #
    # location ~ ^/cache-buster\-[\d]+/protected(.*) {
    #  return 403;
    # }

    # Pimcore Head-Link Cache-Busting
    rewrite ^/cache-buster-(?:\d+)/(.*) /$1 last;

    # Stay secure
    #
    # a) don't allow PHP in folders allowing file uploads
    location ~* /var/assets/.*\.php(/|$) {
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

    # Some Admin Modules need this:
    # Database Admin, Server Info
    location ~* ^/admin/(adminer|external) {
        rewrite .* /app.php$is_args$args last;
    }
    
    # Thumbnails
    location ~* .*/(image|video)-thumb__\d+__.* {
        try_files /var/tmp/$1-thumbnails$uri /app.php;
        expires 2w;
        access_log off;
        add_header Cache-Control "public";
    }

    # Assets
    # Still use a whitelist approach to prevent each and every missing asset to go through the PHP Engine.
    location ~* ^(?!/admin)(.+?)\.((?:css|js)(?:\.map)?|jpe?g|gif|png|svgz?|eps|exe|gz|zip|mp\d|ogg|ogv|webm|pdf|docx?|xlsx?|pptx?)$ {
        try_files /var/assets$uri $uri =404;
        expires 2w;
        access_log off;
        log_not_found off;
        add_header Cache-Control "public";
    }

    location / {
        error_page 404 /meta/404;
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
        # include fastcgi.conf if needed
        include fastcgi.conf;
        # Bypass the fact that try_files resets $fastcgi_path_info
        # see: http://trac.nginx.org/nginx/ticket/321
        set $path_info $fastcgi_path_info;
        fastcgi_param PATH_INFO $path_info;

        # Activate these, if using Symlinks and opcache
        # fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        # fastcgi_param DOCUMENT_ROOT $realpath_root;

        fastcgi_pass php-pimcore6;
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
            fastcgi_pass php-pimcore6;
        }
        location /fpm-ping {
            fastcgi_pass php-pimcore6;
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

### Thumbnail generation overload protection

In case your Web-Application has a page with loads of images that are processed by a image pipeline, there's a chance this can overload your server due to too many PHP processes running in parallel that try to generate thumbnails - especially if your Users upload quite large images (e.g. 16:9 format, 5000+ pixels wide).

In that case you may extend the nginx configuration above to utilize [nginx rate-limiting](https://www.nginx.com/blog/rate-limiting-nginx/). You should get familiar with rate limiting anyway to protect your Site from Denial-of-Service attacks.

__Step 1: Create a Zone__

Somewhere in the _http_ Section of your nginx config add this:

```nginx
# Zone to Limit Pimcore On-demand Image generation
limit_req_zone $server_name zone=imggen:1M rate=5r/s;
```

This defines a new zone called _imggen_ which uses the [$server_name](http://nginx.org/en/docs/http/ngx_http_core_module.html#var_server_name) as key and allows 5 Requests per Second. You should adjust that number to match your servers capability.

__Step 2: Replace the location that handles on-demand thumbnail generation__

```nginx
    # Pimcore On-Demand Thumbnail generation
    # with Rate-Limit.
    location ~* .*/(image|video)-thumb__\d+__.* {
        try_files /var/tmp/$1-thumbnails$uri @imggen;
        expires 2w;
        access_log off;
        add_header Cache-Control "public";
    }
    location @imggen {
        limit_req zone=imggen burst=15;
        try_files /var/tmp/$1-thumbnails$uri /app.php;
        expires 2w;
        access_log off;
        add_header Cache-Control "public";
    }
```

It comes with the expense of a additional stat call - which should be cached anyways, therefore the overhead should be negligible.

This config allows to queue 15 requests in a bucket before rejecting additional ones with a [HTTP 429 Error](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/429). Such a bucket is maintained per virtual host and drained using 5 requests per second (as defined in Step 1).
