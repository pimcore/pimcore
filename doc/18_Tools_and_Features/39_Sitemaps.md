# Sitemaps
:::caution

To use this feature, please enable the `PimcoreSeoBundle` in your `bundle.php` file and install it accordingly with the following command:

`bin/console pimcore:bundle:install PimcoreSeoBundle`

:::

Pimcore includes the [`presta/sitemap-bundle`](https://github.com/prestaconcept/PrestaSitemapBundle) which adds a simple,
yet powerful API to generate XML sitemaps. Have a look at the bundle documentation on details how the bundle works and how
you can add sitemaps to it. The bundle exposes a way to add sitemap entries by [firing an event](https://github.com/prestaconcept/PrestaSitemapBundle/blob/3.x/doc/4-dynamic-routes-usage.md)
which you can handle to add entries to an `UrlContainerInterface`. For simple scenarios, you can directly implement such
an event handler and start to add entries.

## Exposing Sitemaps

Sitemaps can either be exposed by being generated on-the-fly or by being dumped to static files. What to use depends on the size
of your site (e.g. the size of the tree which needs to be processed). In general it's recommended to create static files
as it reduces the overhead of creating the sitemap on every crawler request. If you want to serve the sitemap directly,
you need to register the sitemaps routes in your routing config (see [PrestaSitemapBundle Documentation](https://github.com/prestaconcept/PrestaSitemapBundle/blob/3.x/doc/1-installation.md)
for details).

```yaml
PrestaSitemapBundle:
    resource: "@PrestaSitemapBundle/config/routing.yml"
    prefix:   /
```

After the route is registered, you should be able to access your sitemaps via `/sitemap.xml` and `/sitemap.<section>.xml`.

To dump the sitemaps to static files, use the `presta:sitemaps:dump` command:

    $ bin/console presta:sitemaps:dump
    Dumping all sections of sitemaps into public directory
    Created/Updated the following sitemap files:
        sitemap.default.xml
        sitemap.xml

### Configuring the scheme and host to be used by sitemaps

As the command-line context does not know what scheme (http/https) and host to use for the absolute URLs of your sitemap,
those values need to be configured. Symfony allows to set those parameters on the [Request Context](https://symfony.com/doc/current/routing.html#generating-urls-in-commands).
If configured, Pimcore will set the domain configured as main domain in system settings as default host. Those parameters
will be overridden from the current request in the web context when using the on-the-fly method by adding the route. When
using the `presta:sitemaps:dump` command, you can override those parameters by passing the `--base-url` option:

    $ bin/console presta:sitemaps:dump --base-url=https://pimcore.com/

For details see:

* [Bundle Documentation](https://github.com/prestaconcept/PrestaSitemapBundle/blob/3.x/doc/2-configuration.md#configuring-your-application-base-url)
* [Symfony Documentation on the Request Context](https://symfony.com/doc/current/routing.html#generating-urls-in-commands)
* [`UrlGenerator`](https://github.com/pimcore/pimcore/blob/11.x/bundles/SeoBundle/src/Sitemap/UrlGenerator.php)


## Sitemap Generators

Pimcore adds a way to hook one or more generators into the sitemap generation process. Such generators can be registered,
ordered by priority and enabled/disabled via config. The basic generator interface defines a single `populate()` method
which is expected to add entries to the URL container:

```php
<?php

namespace Pimcore\Bundle\SeoBundle\Sitemap;

use Presta\SitemapBundle\Service\UrlContainerInterface;

interface GeneratorInterface
{
    /**
     * Populates the sitemap
     */
    public function populate(UrlContainerInterface $urlContainer, string $section = null): void;
}
```

When the sitemap bundle fires its `SitemapPopulateEvent::class` event, Pimcore will iterate through every
registered generator and call the `populate()` method. To make a generator available to the event handler, it needs to be
registered via config. `generator_id` in the config below references a generator which was previously registered
as service. As you can see, generators can be enabled/disabled and ordered by priority.

```yaml
pimcore_seo:
    sitemaps:
        generators:
            app_news:
                enabled: true
                priority: 50
                generator_id: App\Sitemaps\NewsGenerator

            # Pimcore ships a default document tree generator which is enabled by default
            # but you can easily disable it here.
            pimcore_documents:
                enabled: false
```


### Element Sitemap Generators

For more advanced use cases involving Pimcore models, Pimcore defines an `AbstractElementGenerator` which is extendable
via pluggable filters and processors. This makes it possible to define reusable behaviour in a filter/processor which can
be used from multiple generators. A **filter** determines if an element can be added to the sitemap and if it is able to handle children (it's up to the
generator to query for this information). For example the [PropertiesFilter](https://github.com/pimcore/pimcore/blob/11.x/bundles/SeoBundle/src/Sitemap/Element/Filter/PropertiesFilter.php)
excludes elements with a property `sitemaps_exclude`. A **processor** can enhance an entry before it is added to the container. For example, the [ModificationDateProcessor](https://github.com/pimcore/pimcore/blob/11.x/bundles/SeoBundle/src/Sitemap/Element/Processor/ModificationDateProcessor.php)
adds the modification date of an element to the url.

Which filters and processors to use can be defined on the generator level. For example, the [`DocumentTreeGenerator`](#the-documenttreegenerator)
which is enabled by default is defined as follows:

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

If you need to influence the behaviour of the document tree sitemap either overwrite the core service definition or define
your own generator service and hook it into the config (see above). By selecting which filters and processors to use you
can change and enhance the behavior of the generator. Pimcore defines a set of standard implementations which are defined
as service and can directly be consumed.

| Filter                                               | Description                                                                                                                                                                                                                                                                                                        |
|------------------------------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `Pimcore\Bundle\SeoBundle\Sitemap\Element\Filter\PropertiesFilter`    | Excludes elements with the boolean property `sitemaps_exclude` set to true and excludes children handling of elements with the boolean property `sitemaps_exclude_children` set to true.                                                                                                                           |
| `Pimcore\Bundle\SeoBundle\Sitemap\Element\Filter\PublishedFilter`     | Excludes unpublished elements.                                                                                                                                                                                                                                                                                     |
| `Pimcore\Bundle\SeoBundle\Sitemap\Document\Filter\DocumentTypeFilter` | Used by the `DocumentTreeGenerator`. Excludes documents not matching the list of defined types and handles children only for defined types.                                                                                                                                                                        |
| `Pimcore\Bundle\SeoBundle\Sitemap\Document\Filter\SiteRootFilter`     | Used by the `DocumentTreeGenerator`. Excludes documents which are root documents of a site when the currently processed site doesn't match the document. E.g. if a document is a site root and the main site is currently processed, it will be excluded for the main site, but later be used for the actual site. |

| Processor                                                     | Description                                                                                                                                                                     |
|---------------------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `Pimcore\Bundle\SeoBundle\Sitemap\Element\Processor\ModificationDateProcessor` | Adds the modification date of an element as `lastmod` property.                                                                                                                 |
| `Pimcore\Bundle\SeoBundle\Sitemap\Element\Processor\PropertiesProcessor`       | Reads the properties `sitemaps_changefreq` and `sitemaps_priority` if set on the element and adds them to the sitemap entry to easily set those properties on an element level. |


#### The DocumentTreeGenerator

Pimcore ships a default generator for documents implemented in [`DocumentTreeGenerator`](https://github.com/pimcore/pimcore/blob/11.x/bundles/SeoBundle/src/Sitemap/Document/DocumentTreeGenerator.php).
This generator iterates the whole document tree and adds entries for every document while taking care of handling sites and
hardlinks. It uses the host names configured as main/site domain and falls back to the request context host by using
the [url generator service](#generating-absolute-urls). You can either disable the default generator completely as shown in the example above or define your own service using the
`DocumentTreeGenerator` class with your own filters/processors. The default service definition can be found in
[sitemaps.yaml in the CoreBundle](https://github.com/pimcore/pimcore/blob/11.x/bundles/CoreBundle/config/sitemaps.yaml).


#### Creating a custom generator

To create your own generator, start by implementing the `GeneratorInterface`. In this section we'll extend the
`AbstractElementGenerator` to create entries for Pimcore models. A generator to add `BlogArticle` entries to the sitemap
could look like the following:

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

        /** @var BlogArticle $blogArticle */
        foreach ($list as $blogArticle) {
            // only add element if it is not filtered
            if (!$this->canBeAdded($blogArticle, $context)) {
                continue;
            }

            // use a link generator to generate an URL to the article
            // you need to make sure the link generator generates an absolute url
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

The `AbstractElementGenerator` exposes the methods `canBeAdded()` and `process()` to run the entry through filters and
processors. If you handle nested tree structures, you can also use `handlesChildren()` to test if children should be
handled. All 3 methods accept a `GeneratorContextInterface` object which you can use to pass context metadata to filters
and processors. For example, the `DocumentTreeProcessor` uses the context to define the site the document lives in.

In the example above, the URL is created by using a [Link Generator](../05_Objects/01_Object_Classes/05_Class_Settings/30_Link_Generator.md).

> It's important that your link generator is able to generate an absolute URL for the given object. Above is only an example, but
  you can have a look at the [demo](https://github.com/pimcore/demo/tree/11.x/src/)
  for a working example building sitemap entries for News objects.

After creating the generator, register it as service and add it to the config. Use filters and processors to reuse already
implemented behaviour.

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

Make the generator available to the core listener by registering it on the configuration:

```yaml
# config.yaml

pimcore_seo:
    sitemaps:
        generators:
            app_blog:
                generator_id: App\Sitemaps\BlogGenerator
```


#### Creating a custom filter

Filters can be created by implementing the `FilterInterface`. An example filter excluding any element with a modification
date older than a year could look like the following:

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
        $modicationDate = \DateTimeImmutable::createFromFormat('U', (string)$element->getModificationDate());
        $now            = new \DateTimeImmutable();

        $diff = $modicationDate->diff($now);

        // exclude element if years is more than the configured amount
        return $diff->y < $this->maxYears;
    }

    public function handlesChildren(ElementInterface $element, GeneratorContextInterface $context): bool
    {
        // not matching the age constraint does not mean not handling children
        return true;
    }
}
```

Now you can define the filter as service and use it in your generators:

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


#### Creating a custom processor

Creating a processor is very similar to creating a filter. As example, let's create a processor which adds a random priority
to each entry.

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
    public function process(Url $url, ElementInterface $element, GeneratorContextInterface $context): Url
    {
        if ($url instanceof UrlConcrete) {
            $url->setPriority(rand(0, 10) / 10);
        }

        // important: return an Url instance to be added. if your
        // processor returns null it will be omitted.
        return $url;
    }
}
```

> It's important that a processor returns an Url instance as otherwise it will be omitted. You can use this in your own
  processors to apply some kind of filtering on the processor level or to return a different instance from your processor.
  A typical use case would be to use an [Url Decorator](https://github.com/prestaconcept/PrestaSitemapBundle/blob/3.x/doc/5-decorating-urls.md)
  in a processor and to return its instance instead of the original Url.

Again, define it as service and start using it from your generators:

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


### Generating absolute URLs

To generate absolute URLs, Pimcore defines an [url generator](https://github.com/pimcore/pimcore/blob/11.x/bundles/SeoBundle/src/Sitemap/UrlGenerator.php) which, given a path, takes care of creating an absolute URL
based on the [Request Context](https://symfony.com/doc/current/routing.html#generating-urls-in-commands).
See core processors/generators and [demo](https://github.com/pimcore/demo/tree/11.x/src/Sitemaps)
for details. As example how to use the URL generator in a processor:

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
