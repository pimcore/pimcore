# Apache Configuration 

## Virtual Hosts
Make sure `Allowoverride All` is set for the `DocumentRoot`, which enables `.htaccess` support. 

All the necessary rewrite rules, which are needed for Pimcore to work, are defined in the `.htaccess` of the install package, 
see [https://github.com/pimcore/skeleton/blob/master/public/.htaccess](https://github.com/pimcore/skeleton/blob/master/public/.htaccess). 

#### Example 
```
<VirtualHost *:443>
        ServerName YOUPROJECT.local
        DocumentRoot  /var/www/public

        <FilesMatch \.php$>
            SetHandler "proxy:unix:/var/run/php/pimcore.sock|fcgi://localhost"
        </FilesMatch>

        <Directory /var/www/public>
                Options FollowSymLinks
                AllowOverride All
                Require all granted
        </Directory>

        SSLEngine on
        # NEEDS TO BE CHANGED
        SSLCertificateFile /etc/getssl/YOUPROJECT.local/YOUPROJECT.local.crt
        SSLCertificateKeyFile /etc/getssl/YOUPROJECT.local/YOUPROJECT.local.key
        SSLCertificateChainFile /etc/getssl/YOUPROJECT.local/chain.crt

        RewriteEngine On

        # THE FOLLOWING NEEDS TO BE THE VERY LAST REWRITE RULE IN THIS VHOST
        # this is needed to pass the auth header correctly - fastcgi environment
        RewriteRule ".*" "-" [E=HTTP_AUTHORIZATION:%{HTTP:Authorization},L]

        ErrorLog ${APACHE_LOG_DIR}/YOUPROJECT.local_443_error.log
        CustomLog ${APACHE_LOG_DIR}/YOUPROJECT.local_443_access.log combined
</VirtualHost>
```
