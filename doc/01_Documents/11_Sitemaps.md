---
title: Sitemaps
description: Generating XML sitemaps for Pimcore documents and custom data objects.
---

# Sitemaps

:::caution

To use sitemaps, enable the `PimcoreSeoBundle` in your `bundle.php` and install it:

`bin/console pimcore:bundle:install PimcoreSeoBundle`

:::

Pimcore integrates [`presta/sitemap-bundle`](https://github.com/prestaconcept/PrestaSitemapBundle)
for XML sitemap generation. It ships a default `DocumentTreeGenerator` that creates sitemap entries
from your document tree automatically.

**Quick start:** Install PimcoreSeoBundle, register the sitemap route (see below),
and the document tree sitemap works out of the box - no custom code needed.

For advanced use cases, you can add custom generators for data objects, apply filters
to exclude specific elements, and use processors to enrich sitemap entries.
For simple scenarios, you can also bypass the generator system entirely and handle
the bundle's [SitemapPopulateEvent](https://github.com/prestaconcept/PrestaSitemapBundle/blob/3.x/doc/4-dynamic-routes-usage.md)
directly to add entries to the `UrlContainerInterface`.

## Exposing Sitemaps

Sitemaps can be served dynamically or dumped to static files. Static files are recommended for
larger sites as they avoid regenerating the sitemap on every crawler request.

### Dynamic (On-the-Fly)

Register the sitemap routes in your routing configuration
(see [PrestaSitemapBundle Documentation](https://github.com/prestaconcept/PrestaSitemapBundle/blob/3.x/doc/1-installation.md) for details):

```yaml
PrestaSitemapBundle:
    resource: "@PrestaSitemapBundle/config/routing.yml"
    prefix:   /
```

Your sitemaps are then available at `/sitemap.xml` and `/sitemap.<section>.xml`.
Verify the setup by visiting `/sitemap.xml` in your browser.

### Static File Dump

Use the `presta:sitemaps:dump` command to generate static sitemap files:

```bash
$ bin/console presta:sitemaps:dump
Dumping all sections of sitemaps into public directory
Created/Updated the following sitemap files:
    sitemap.default.xml
    sitemap.xml
```

### Configuring Scheme and Host

The command-line context does not know which scheme (http/https) and host to use for absolute URLs.
Symfony's [Request Context](https://symfony.com/doc/current/routing.html#generating-urls-in-commands)
controls these values. Pimcore sets the configured main domain as the default host automatically.

In the web context (dynamic route), the current request provides these values.
For the static dump, override them with `--base-url`:

```bash
$ bin/console presta:sitemaps:dump --base-url=https://pimcore.com/
```

For details see:

* [Bundle Documentation](https://github.com/prestaconcept/PrestaSitemapBundle/blob/3.x/doc/2-configuration.md#configuring-your-application-base-url)
* [Symfony Documentation on the Request Context](https://symfony.com/doc/current/routing.html#generating-urls-in-commands)
* [`UrlGenerator`](https://github.com/pimcore/pimcore/blob/2026.x/bundles/SeoBundle/src/Sitemap/UrlGenerator.php)

## Sitemap Generators

Pimcore uses a generator system to populate sitemaps. Each generator implements a `populate()` method
that adds entries to the URL container:

```php
<?php

namespace Pimcore\Bundle\SeoBundle\Sitemap;

use Presta\SitemapBundle\Service\UrlContainerInterface;

interface GeneratorInterface
{
    public function populate(UrlContainerInterface $urlContainer, string $section = null): void;
}
```

When `presta/sitemap-bundle` fires its `SitemapPopulateEvent`, Pimcore iterates through all
registered generators and calls `populate()`. Register generators via configuration:

```yaml
pimcore_seo:
    sitemaps:
        generators:
            app_news:
                enabled: true
                priority: 50
                generator_id: App\Sitemaps\NewsGenerator

            # The default document tree generator is enabled by default.
            # Disable it if you need full control:
            pimcore_documents:
                enabled: false
```

## Element Sitemap Generators

For use cases involving Pimcore models, the `AbstractElementGenerator` base class (which implements
`GeneratorInterface`) adds support for pluggable **filters** and **processors**:

- **Filters** determine whether an element should be included in the sitemap
  and whether its children should be processed.
- **Processors** enrich a sitemap entry before it is added to the container
  (e.g. adding modification dates or priorities).

This separation allows you to compose reusable behaviors across multiple generators.

### Built-in Filters

| Filter | Description |
|---|---|
| [`PropertiesFilter`](https://github.com/pimcore/pimcore/blob/2026.x/bundles/SeoBundle/src/Sitemap/Element/Filter/PropertiesFilter.php) | Excludes elements with the boolean property `sitemaps_exclude` set to true. Excludes children of elements with `sitemaps_exclude_children` set to true. |
| [`PublishedFilter`](https://github.com/pimcore/pimcore/blob/2026.x/bundles/SeoBundle/src/Sitemap/Element/Filter/PublishedFilter.php) | Excludes unpublished elements. |
| [`DocumentTypeFilter`](https://github.com/pimcore/pimcore/blob/2026.x/bundles/SeoBundle/src/Sitemap/Document/Filter/DocumentTypeFilter.php) | Used by `DocumentTreeGenerator`. Excludes documents not matching the list of allowed types and handles children only for those types. |
| [`SiteRootFilter`](https://github.com/pimcore/pimcore/blob/2026.x/bundles/SeoBundle/src/Sitemap/Document/Filter/SiteRootFilter.php) | Used by `DocumentTreeGenerator`. Excludes documents which are root documents of a site when the currently processed site does not match. For example, if a document is a site root and the main site is currently processed, it will be excluded for the main site but later included for the actual site. |

All filters are in the `Pimcore\Bundle\SeoBundle\Sitemap\Element\Filter` or
`Pimcore\Bundle\SeoBundle\Sitemap\Document\Filter` namespace.

### Built-in Processors

| Processor | Description |
|---|---|
| [`ModificationDateProcessor`](https://github.com/pimcore/pimcore/blob/2026.x/bundles/SeoBundle/src/Sitemap/Element/Processor/ModificationDateProcessor.php) | Adds the element's modification date as `lastmod`. |
| [`PropertiesProcessor`](https://github.com/pimcore/pimcore/blob/2026.x/bundles/SeoBundle/src/Sitemap/Element/Processor/PropertiesProcessor.php) | Reads `sitemaps_changefreq` and `sitemaps_priority` properties from the element and adds them to the sitemap entry. |

All processors are in the `Pimcore\Bundle\SeoBundle\Sitemap\Element\Processor` namespace.

## The DocumentTreeGenerator

Pimcore ships a default [`DocumentTreeGenerator`](https://github.com/pimcore/pimcore/blob/2026.x/bundles/SeoBundle/src/Sitemap/Document/DocumentTreeGenerator.php)
that iterates the entire document tree, handles sites and hardlinks, and uses configured
domain names (falling back to the request context host via the
[URL generator service](#generating-absolute-urls)) for URL generation.
Its default service definition:

```yaml
services:
    Pimcore\Bundle\SeoBundle\Sitemap\Document\DocumentTreeGenerator:
        arguments:
            $filters:
                - '@Pimcore\Bundle\SeoBundle\Sitemap\Element\Filter\PublishedFilter'
                - '@Pimcore\Bundle\SeoBundle\Sitemap\Element\Filter\PropertiesFilter'
                - '@Pimcore\Bundle\SeoBundle\Sitemap\Document\Filter\DocumentTypeFilter'
                - '@Pimcore\Bundle\SeoBundle\Sitemap\Document\Filter\SiteRootFilter'
            $processors:
                - '@Pimcore\Bundle\SeoBundle\Sitemap\Element\Processor\ModificationDateProcessor'
                - '@Pimcore\Bundle\SeoBundle\Sitemap\Element\Processor\PropertiesProcessor'
            $options:
                handleMainDomain: true
                handleCurrentSite: false
                handleSites: true
```

The `$options` control multi-site behavior:
- `handleMainDomain` - include documents belonging to the main domain
- `handleSites` - include documents belonging to configured sites
- `handleCurrentSite` - when serving sitemaps dynamically, limit to the current site only

To customize the document sitemap behavior, either override this service definition or create
your own generator service with different filters and processors. The full default definition
is in [sitemaps.yaml](https://github.com/pimcore/pimcore/blob/2026.x/bundles/CoreBundle/config/sitemaps.yaml).

## Creating a Custom Generator

To add non-document content (e.g. data objects) to your sitemap, create a generator
that extends `AbstractElementGenerator`. Each data object class needs a
[Link Generator](../03_Objects/01_Object_Classes/04_Additional_Class_Settings/05_Link_Generator.md)
that can produce absolute URLs. This example adds `BlogArticle` entries:

```php
<?php

namespace App\Sitemaps;

use Pimcore\Model\DataObject\BlogArticle;
use Pimcore\Bundle\SeoBundle\Sitemap\Element\AbstractElementGenerator;
use Pimcore\Bundle\SeoBundle\Sitemap\Element\GeneratorContext;
use Presta\SitemapBundle\Service\UrlContainerInterface;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class BlogGenerator extends AbstractElementGenerator
{
    public function populate(UrlContainerInterface $urlContainer, string $section = null): void
    {
        if (null !== $section && $section !== 'blog') {
            // do not add entries if section doesn't match
            return;
        }

        $section = 'blog';

        $list = new BlogArticle\Listing();
        $list->setOrderKey('date');
        $list->setOrder('DESC');

        // the context contains metadata for filters/processors
        // it contains at least the url container, but you can add additional data
        // with the params parameter
        $context = new GeneratorContext($urlContainer, $section, ['foo' => 'bar']);

        foreach ($list as $blogArticle) {
            // only add element if it passes all filters
            if (!$this->canBeAdded($blogArticle, $context)) {
                continue;
            }

            // generate an absolute URL using the class link generator
            $link = $blogArticle->getClass()->getLinkGenerator()->generate($blogArticle, [
                'referenceType' => UrlGeneratorInterface::ABSOLUTE_URL
            ]);

            // create an entry for the sitemap
            $url = new UrlConcrete($link);

            // run url through processors
            $url = $this->process($url, $blogArticle, $context);

            // processors can return null to exclude the url
            if (null === $url) {
                continue;
            }

            // add the url to the container
            $urlContainer->addUrl($url, $section);
        }
    }
}
```

The `AbstractElementGenerator` exposes `canBeAdded()`, `process()`, and `handlesChildren()`
methods that delegate to your configured filters and processors. All three accept a
`GeneratorContextInterface` for passing metadata (e.g. the current site).

> Your link generator must produce absolute URLs for sitemap entries.
> See the [demo](https://github.com/pimcore/demo-enterprise/tree/2026.x/src/)
> for a working example with News objects.

Register the generator as a service with your chosen filters and processors:

```yaml
# services.yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true
        public: false

    App\Sitemaps\BlogGenerator:
        arguments:
            $filters:
                - '@Pimcore\Bundle\SeoBundle\Sitemap\Element\Filter\PublishedFilter'
                - '@Pimcore\Bundle\SeoBundle\Sitemap\Element\Filter\PropertiesFilter'
            $processors:
                - '@Pimcore\Bundle\SeoBundle\Sitemap\Element\Processor\ModificationDateProcessor'
                - '@Pimcore\Bundle\SeoBundle\Sitemap\Element\Processor\PropertiesProcessor'
```

Then enable it in your Pimcore configuration:

```yaml
# config.yaml
pimcore_seo:
    sitemaps:
        generators:
            app_blog:
                generator_id: App\Sitemaps\BlogGenerator
```

## Creating a Custom Filter

Filters implement `FilterInterface` with two methods:
- `canBeAdded()` - whether the element should appear in the sitemap
- `handlesChildren()` - whether the element's children should be processed

This example excludes elements not modified within a configurable number of years:

```php
<?php

namespace App\Sitemaps\Filter;

use Pimcore\Model\Element\ElementInterface;
use Pimcore\Bundle\SeoBundle\Sitemap\Element\FilterInterface;
use Pimcore\Bundle\SeoBundle\Sitemap\Element\GeneratorContextInterface;

class AgeFilter implements FilterInterface
{
    private int $maxYears;

    public function __construct(int $maxYears = 1)
    {
        $this->maxYears = $maxYears;
    }

    public function canBeAdded(ElementInterface $element, GeneratorContextInterface $context): bool
    {
        $modificationDate = \DateTimeImmutable::createFromFormat('U', (string)$element->getModificationDate());
        $now = new \DateTimeImmutable();
        $diff = $modificationDate->diff($now);

        return $diff->y < $this->maxYears;
    }

    public function handlesChildren(ElementInterface $element, GeneratorContextInterface $context): bool
    {
        // not matching the age constraint does not mean children should be skipped
        return true;
    }
}
```

Register the filter and use it in your generators:

```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true
        public: false

    App\Sitemaps\Filter\AgeFilter: ~

    App\Sitemaps\BlogGenerator:
        arguments:
            $filters:
                - '@App\Sitemaps\Filter\AgeFilter'
```

## Creating a Custom Processor

Processors implement `ProcessorInterface`. They receive a URL entry and can modify it
or return `null` to exclude it from the sitemap. This example adds a random priority:

```php
<?php

namespace App\Sitemaps\Processor;

use Pimcore\Model\Element\ElementInterface;
use Pimcore\Bundle\SeoBundle\Sitemap\Element\GeneratorContextInterface;
use Pimcore\Bundle\SeoBundle\Sitemap\Element\ProcessorInterface;
use Presta\SitemapBundle\Sitemap\Url\Url;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;

class RandomPriorityProcessor implements ProcessorInterface
{
    public function process(Url $url, ElementInterface $element, GeneratorContextInterface $context): ?Url
    {
        if ($url instanceof UrlConcrete) {
            $url->setPriority(rand(0, 10) / 10);
        }

        // return a Url instance to include the entry, or null to exclude it
        return $url;
    }
}
```

> You can use a processor to apply additional filtering or to return a different URL instance,
> such as a [Url Decorator](https://github.com/prestaconcept/PrestaSitemapBundle/blob/3.x/doc/5-decorating-urls.md).

Register and use the processor:

```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true
        public: false

    App\Sitemaps\Processor\RandomPriorityProcessor: ~

    App\Sitemaps\BlogGenerator:
        arguments:
            $processors:
                - '@App\Sitemaps\Processor\RandomPriorityProcessor'
```

## Generating Absolute URLs

Pimcore provides a
[URL generator service](https://github.com/pimcore/pimcore/blob/2026.x/bundles/SeoBundle/src/Sitemap/UrlGenerator.php)
that creates absolute URLs based on the
[Request Context](https://symfony.com/doc/current/routing.html#generating-urls-in-commands).
Use it in custom processors or generators when you need to turn a relative path into an absolute URL:

```php
<?php

namespace App\Sitemaps\Processor;

use Pimcore\Model\Element\ElementInterface;
use Pimcore\Bundle\SeoBundle\Sitemap\Element\GeneratorContextInterface;
use Pimcore\Bundle\SeoBundle\Sitemap\Element\ProcessorInterface;
use Pimcore\Bundle\SeoBundle\Sitemap\UrlGeneratorInterface;
use Presta\SitemapBundle\Sitemap\Url\Url;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;

class RandomPathProcessor implements ProcessorInterface
{
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function process(Url $url, ElementInterface $element, GeneratorContextInterface $context): UrlConcrete
    {
        $path = $this->urlGenerator->generateUrl('/foo/bar');
        $url  = new UrlConcrete($path);

        return $url;
    }
}
```

See the core processors/generators and
[demo](https://github.com/pimcore/demo-enterprise/tree/2026.x/src/Sitemaps) for more examples.
