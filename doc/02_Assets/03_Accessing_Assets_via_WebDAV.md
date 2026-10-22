---
title: Accessing Assets via WebDAV
description: Connecting to the Pimcore asset tree using the WebDAV protocol.
---

# Accessing Pimcore Assets via WebDAV

Pimcore provides the option to access all assets via [WebDAV](https://en.wikipedia.org/wiki/WebDAV).
Connect your WebDAV client to the following URL: `https://YOUR-DOMAIN/asset/webdav`

Use any Pimcore user as credentials. Permissions for asset access are based on the user's permissions.

> **Note:** The HTML browsing UI (viewing the URL in a regular web browser) is disabled by default,
> so the endpoint responds to WebDAV clients only. It is opt-in because its POST-based file/folder
> creation form has no CSRF protection: the endpoint uses HTTP Basic authentication, which browsers
> replay on cross-site requests, so a third-party page could submit that form on behalf of a user
> whose browser holds WebDAV credentials. Enable it only if you need it, with:
>
> ```yaml
> pimcore:
>     assets:
>         webdav:
>             browser_plugin: true
> ```

## Nginx Configuration

Make sure to have the following changes in your project
[Nginx configuration](https://github.com/pimcore/skeleton/blob/2026.x/.docker/nginx.conf):

```nginx
location ~* ^(?!/admin|/asset/webdav)(.+?)\.(?:css|js|jpg|jpeg|gif|png|svg|ico|woff|woff2|xml)$ {
```
