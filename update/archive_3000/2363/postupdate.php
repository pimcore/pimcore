<?php

$htaccessFile = PIMCORE_DOCUMENT_ROOT . "/.htaccess";
$newHtaccessContent = '

# mime types
AddType video/mp4 .mp4
AddType video/webm .webm

# rewrites
RewriteEngine On

RewriteBase /

<IfModule mod_status.c>
    RewriteCond %{REQUEST_URI} ^/(server-info|server-status)
    RewriteRule . - [last]
</IfModule>

# ASSETS: check if request method is GET (because of WebDAV) and if the requested file (asset) exists on the filesystem, if both match, deliver the asset directly
RewriteCond %{REQUEST_METHOD} ^GET
RewriteCond %{DOCUMENT_ROOT}/website/var/assets%{REQUEST_URI} -f
RewriteRule ^(.*)$ /website/var/assets%{REQUEST_URI} [PT,L]

# allow access to thumnails, assets and plugin-data
RewriteRule ^website/var/tmp.* - [PT,L]
RewriteRule ^website/var/assets.* - [PT,L]
RewriteRule ^website/var/plugins.* - [PT,L]
RewriteRule ^website/var/areas.* - [PT,L]
RewriteRule ^plugins/.*/static.* - [PT,L]
RewriteRule ^pimcore/static.* - [PT,L]

# forbid the direct access to pimcore-internal data (eg. config-files, ...)
RewriteRule ^website/var/.*$ / [F,L]
RewriteRule ^plugins/.*$ / [F,L]
RewriteRule ^pimcore/.*$ / [F,L]

# basic zend-framework setup see: http://framework.zend.com/manual/en/zend.controller.html
RewriteCond %{REQUEST_FILENAME} -d [OR]
RewriteCond %{REQUEST_FILENAME} -s [OR]
RewriteCond %{REQUEST_FILENAME} -l [OR]
RewriteCond %{REQUEST_FILENAME} -f
RewriteRule ^.*$ - [NC,L]
RewriteRule ^.*$ index.php [NC,L]

';

// try to write the .htaccess file
if(is_writable($htaccessFile)) {
    file_put_contents($htaccessFile, $newHtaccessContent);
} else {
    echo "Please update your htaccess-file to: <br /><br /><code>" . nl2br($newHtaccessContent) . "</code>";
}


?>