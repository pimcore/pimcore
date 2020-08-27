# HeadLink Templating Helper

> The HeadLink templating helper is an extension of the [Placeholder Templating Helper](./00_Placeholder.md)

The HTML `<link>` element is increasingly used for linking a variety of resources for your site: stylesheets, feeds, 
favicons, trackbacks, and more. The HeadLink helper provides a simple interface for creating and aggregating these 
elements for later retrieval and output in your layout script.

The HeadLink helper has special methods for adding stylesheet links to its stack:

- `appendStylesheet($href, $media, $conditionalStylesheet, $extras)`
- `offsetSetStylesheet($index, $href, $media, $conditionalStylesheet, $extras)`
- `prependStylesheet($href, $media, $conditionalStylesheet, $extras)`
- `setStylesheet($href, $media, $conditionalStylesheet, $extras)`

The `$media` value defaults to `screen`, but may be any valid media value. 
`$conditionalStylesheet` is a string or boolean `FALSE`, and will be used at rendering time to determine 
if special comments should be included to prevent loading of the stylesheet on certain platforms. 
`$extras` is an array of any extra values that you want to be added to the tag.

Additionally, the HeadLink helper has special methods for adding 'alternate' links to its stack:

- `appendAlternate($href, $type, $title, $extras)`
- `offsetSetAlternate($index, $href, $type, $title, $extras)`
- `prependAlternate($href, $type, $title, $extras)`
- `setAlternate($href, $type, $title, $extras)`

The `headLink()` helper method allows specifying all attributes necessary for a `<link>` element, 
and allows you to also specify placement -- whether the new element replaces all others, prepends (top of stack), 
or appends (end of stack).

## Basic Usage

You may specify a headLink at any time. 
Typically, you will specify global links in your layout script, and application specific links in your 
application view scripts. In your layout script, in the `<head>` section, you will then echo the helper to output it.

```php
<?php // setting links in a view script:
$this->headLink()->appendStylesheet('/styles/basic.css'); 
$this->headLink(['rel' => 'icon', 'href' => '/img/favicon.ico'], 'PREPEND')
     ->prependStylesheet('/styles/moz.css', 'screen', true,  ['id' => 'my_stylesheet']);
?>
<?php // rendering the links: ?>
<?= $this->headLink() ?>
```

## HTTP/2 Push Support

The HeadLink and HeadScript helpers have internal support for the [WebLink Component](https://symfony.com/blog/new-in-symfony-3-3-weblink-component).
While you can call `$this->webLink()->preload('/path/to/file.css', ['as' => 'style'])` directly in your templates, the HeadLink
and HeadScript helpers take care of adding a cache buster aware link instead of the unprefixed file path. Push support is
currently opt-in - to make the helpers automatically include links to the served assets either enable it globally on the 
helper level or individually for each item.

```php
<?php
/** @var \Pimcore\Templating\PhpEngine $this */

// enable web links for every item
$this->headLink()->enableWebLinks();

// set web link attributes passed to every item
$this->headLink()->setWebLinkAttributes(['as' => 'style']);

// enable webLink on an item level
// the item will be added even if enableWebLinks() was not called
$this->headLink()->appendStylesheet('/static/css/styles.css', 'screen', false, [
    'webLink' => ['as' => 'style']
]);

// disable webLink on an item level
// the item won't be added even if enableWebLinks() was called
$this->headLink()->appendStylesheet('/static/css/styles.css', 'screen', false, [
    'webLink' => false
]);

// override the used method (default is preload())
$this->headLink()->appendStylesheet('/static/css/styles.css', 'screen', false, [
    'webLink' => ['method' => 'prefetch']
]);
?>
```

Added links will be handled by the web link component and injected into the response. Make sure Symfony is properly configured
(this setting is enabled by default from Pimcore's core config):

```yaml
# config.yml

framework:
    web_links:
        enabled: true
```
