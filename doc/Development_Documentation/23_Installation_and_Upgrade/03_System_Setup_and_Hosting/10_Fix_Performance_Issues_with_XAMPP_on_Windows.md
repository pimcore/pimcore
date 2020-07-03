# Fix Performance Issues with XAMPP on Windows

By default XAMPP comes installed with Mod-PHP. Mod-PHP is usually the way-to-go to execute PHP within the Apache process,
which is handling the request. The problem is the poor performance of Mod-PHP in XAMPP especially on Windows.

If you are experiencing poor performance of the Pimcore/Symfony stack, try switching out Mod-PHP with FastCGI in your XAMPP installation.

##### Download FastCGI Module for Apache
You can download the latest version of the FastCGI Module for Apache at
[https://www.apachelounge.com/download/](https://www.apachelounge.com/download/). Depending on your XAMPP installation
download either the 32 Bit `mod_fcgid-x.x.x-win32-VCXX.zip` or the 64 Bit `mod_fcgid-x-x-x-win64-VCXX.zip` version.

##### Install the FastCGI Module
Assuming your XAMPP installation is located under `C:\xampp`, copy the `mod_fcgid.so` file from the ZIP to `C:\xampp\apache\modules`.

##### Enable FastCGI
In order to enable FastCGI for your XAMPP installation you have to edit the configuration file `C:\xampp\apache\conf\extra\httpd-xampp.conf`.
In the `PHP-Module setup` section comment out the following lines. (Example below is running Apache 2.4 with PHP 7.1)

```apacheconfig
#
# PHP-Module setup
#
LoadFile "C:/xampp/php/php7ts.dll"
LoadFile "C:/xampp/php/libpq.dll"
    
# comment out the following lines
#LoadModule php7_module "C:/xampp/php/php7apache2_4.dll"
    
#<FilesMatch "\.php$">
#    SetHandler application/x-httpd-php
#</FilesMatch>
#<FilesMatch "\.phps$">
#    SetHandler application/x-httpd-php-source
#</FilesMatch>
```

Add these lines to enable FastCGI.

```apacheconfig
FcgidInitialEnv PHPRC "C:/xampp/php"
AddHandler fcgid-script .php
FcgidWrapper "C:/xampp/php/php-cgi.exe" .php
```

After enabling FastCGI `phpmyadmin` will throw a forbidden exception when navigating to `localhost/phpmyadmin`. To fix
this error add the `ExecCGI` option in the `httpd-xampp.conf` to the phpmyadmin directory further below.

```apacheconfig
Alias /phpmyadmin "C:/xampp/phpMyAdmin/"
<Directory "C:/xampp/phpMyAdmin">
    
    # add this option to fix the error
    Options ExecCGI
    
    AllowOverride AuthConfig
    Require local
    ErrorDocument 403 /error/XAMPP_FORBIDDEN.html.var
</Directory>
```
