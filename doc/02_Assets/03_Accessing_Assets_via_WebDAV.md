# Accessing Pimcore Assets via WebDAV

Pimcore provides the option to access all assets via [WebDAV](https://en.wikipedia.org/wiki/WebDAV). To do so, 
just open following URL via your browser or WebDAV client: https://YOUR-DOMAIN/asset/webdav

As user credentials use any Pimcore user. Permissions for asset access are based on the user's permissions.  

### Nginx configuration
Please make sure to have the following changes in your project 
[Nginx configuration](../backlog/23_Installation_and_Upgrade/03_System_Setup_and_Hosting/02_Nginx_Configuration.md):
```
location ~* ^(?!/admin|/asset/webdav)(.+?)....
```
