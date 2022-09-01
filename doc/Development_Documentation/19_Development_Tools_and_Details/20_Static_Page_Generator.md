# Static Page Generator
Pimcore offers a Static Page Generator service, which is used to generate HTML pages from Pimcore documents. This generator service works by taking a Pimcore document with content and templates and renders them into a full HTML page, that can served directly from the server without the intervention of templating engine.

## Enable Static Page generator for a Document
To enable automatic static page generation on document save or by CLI command, go to Document -> Settings -> Satic Page Generator.
![Static Page Settings](../img/static_page1.png)

Mark enable checkbox and define optional lifetime for static pages (which regenerates static page after lifetime) and save document.

Once, the static page generator is enabled, the document icon changes to grey icon:
![Static Page Detail](../img/static_page2.png)

and last generated information is displayed in document settings, when the generation is requested from frontend or cli command.
![Static Page Detail](../img/static_page3.png)

In addition, if you are using default local storage for static pages, then make sure your project `.htaccess` has this below section (after the `# Thumbnails` section), which is responsible for looking up static page before passing to templating engine. 
```apache
# static pages
RewriteCond %{REQUEST_METHOD} ^(GET|HEAD)
RewriteCond %{QUERY_STRING}   !(pimcore_editmode=true|pimcore_preview|pimcore_version)
RewriteCond %{DOCUMENT_ROOT}/var/tmp/pages%{REQUEST_URI}.html -f
RewriteRule ^(.*)$ /var/tmp/pages%{REQUEST_URI}.html [PT,L]
```

If you are using NGINX as web server, this must be added **before** the `server` block
```nginx
map $args $static_page_root {
    default                                 /var/tmp/pages;
    "~*(^|&)pimcore_editmode=true(&|$)"     /var/nonexistent;
    "~*(^|&)pimcore_preview=true(&|$)"      /var/nonexistent;
    "~*(^|&)pimcore_version=[^&]+(&|$)"     /var/nonexistent;
}
```
and the following modification must be done to the location block that matches all requests 
```nginx
server {
    ... 
    
    location / {
        error_page 404 /meta/404;
        try_files $static_page_root$uri.html $uri /index.php$is_args$args;
    }
    
    ...
}
```

## Processing
Once the static generator option is enabled, Pimcore generates static pages on following actions:
 - First request to the page, after updating and saving the document in admin.
 - Maintenance job
 - CLI command
 
In background, maintenance job takes care of generating static pages for documents on regular intervals. However, you can also use CLI command to generate static pages on demand:
  `php bin/console pimcore:documents:generate-static-pages`
 
 also, you can filter the documents by parent path, which should processed for static generation:
 `php bin/console pimcore:documents:generate-static-pages -p /en/Magazine`
 
## Storage
By default, Pimcore stores the generated HTML pages on local path: `'document_root/public/var/tmp/pages'`.

It is possible to customize the local storage path for static pages by defining Flysystem config in config.yaml:
```yaml
flysystem:
    storages:
        # and add directory_visibility config option to the storages
        pimcore.document_static.storage:
            # Storage for generated static document pages, e.g. .html files generated out of Pimcore documents
            # which are then delivered directly by the web-server
            adapter: 'local'
            visibility: public
            options:
                directory: '%kernel.project_dir%/public/var/tmp/pages'
```

## Static Page Generate Router
In case, you are using custom remote storage for static pages and need to serve pages from this remote location, then you would need to enable the static page router with following configuration in config.yaml:

```yaml
pimcore:
    documents:
        static_page_router:
            enabled: true
            route_pattern: '@^/(en/Magazine|de/Magazin)@'
```

| config         | Description                                                   |
|----------------|---------------------------------------------------------------|
| enabled        | Set it true to enable Static Page Router                      |
| route_pattern  | Regular expression to match routes for static page rendering  |

## Static Page Generation with Ajax Request
The static pages with XMLHttpRequest fetches the data and displays it on the page, just like a standard document page. 
However, if you are using the Fetch API to request the data, then must add the `XMLHttpRequest` header as shown below, 
otherwise the sub-request will replace the content of the generated static page.

```js
fetch('/test/page', {
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
    }
})
```