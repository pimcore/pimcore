# Robots.txt
:::caution

To use this feature, please enable the `PimcoreSeoBundle` in your `bundle.php` file and install it accordingly with the following command:

`bin/console pimcore:bundle:install PimcoreSeoBundle`

:::

Robots.txt files can be generated on a per-site basis.

![Robots.txt Editor](../img/robots-txt-editor.png)

By default, if a `robots.txt` file is not configured for a given site, the following is generated upon robots.txt being requested:

```
User-agent: *
Disallow:
```

Alternatively, you can still manually create a `robots.txt` file by putting them into the document root, this will 
override all robots.txt settings made within the Pimcore admin interface.
