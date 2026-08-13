---
title: Accessing Assets via WebDAV
description: Connecting to the Pimcore asset tree using the WebDAV protocol.
---

# Accessing Pimcore Assets via WebDAV

Pimcore provides the option to access all assets via [WebDAV](https://en.wikipedia.org/wiki/WebDAV).
Open the following URL via your browser or WebDAV client: `https://YOUR-DOMAIN/asset/webdav`

Use any Pimcore user as credentials. Permissions for asset access are based on the user's permissions.

## Nginx Configuration

Make sure to have the following changes in your project
[Nginx configuration](https://github.com/pimcore/skeleton/blob/2026.x/.docker/nginx.conf):

```nginx
location ~* ^(?!/admin|/asset/webdav)(.+?)\.(?:css|js|jpg|jpeg|gif|png|svg|ico|woff|woff2|xml)$ {
```
