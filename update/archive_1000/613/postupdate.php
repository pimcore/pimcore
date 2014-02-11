<?php

if(!@mkdir(PIMCORE_WEBSITE_PATH . "/var/system",755)) {
    echo '<b style="color: red;">Please create the new folder: /website/var/system and give write access to it.</b><br /><br />';
}

?>

<b style="color: red;">Please update your .htaccess file to:</b>
<br />
<pre>
RewriteEngine On

# ASSETS: check if request method is GET (because of WebDAV) and if the requested file (asset) exists on the filesystem, if both match, deliver the asset directly 
RewriteCond %{REQUEST_METHOD} ^GET
RewriteCond %{DOCUMENT_ROOT}/website/var/assets%{REQUEST_URI} -f
RewriteRule ^(.*)$ /website/var/assets%{REQUEST_URI} [PT,L]

# allow access to thumnails & assets
RewriteRule ^website/var/tmp.* - [PT,L]
RewriteRule ^website/var/assets.* - [PT,L]
RewriteRule ^plugins/.*/static.* - [PT,L]
RewriteRule ^pimcore/static.* - [PT,L]

# forbid the direct access to pimcore-internal data (eg. config-files, ...)
RewriteRule ^website/var/.*$ / [F,L]
RewriteRule ^plugins/.*$ / [F,L]
RewriteRule ^pimcore/.*$ / [F,L]

# basic zend-framework setup see: http://framework.zend.com/manual/en/zend.controller.html
RewriteCond %{REQUEST_FILENAME} -s [OR]
RewriteCond %{REQUEST_FILENAME} -l [OR]
RewriteCond %{REQUEST_FILENAME} -f
RewriteRule ^.*$ - [NC,L]
RewriteRule ^.*$ index.php [NC,L]

</pre>