# HeadMeta Templating Helper

> The HeadMeta templating helper is an extension of the [Placeholder Templating Helper](./00_Placeholder.md)

The HTML `<meta>` element is used to provide meta information about your HTML document -- typically keywords, 
document character set, caching pragmas, etc. Meta tags may be either of the `http-equiv` or `name` types, 
must contain a `content` attribute, and can also have either of the `lang` or `scheme` modifier attributes.

The HeadMeta helper supports the following methods for setting and adding meta tags:

- `appendName($keyValue, $content, $conditionalName)`
- `offsetSetName($index, $keyValue, $content, $conditionalName)`
- `prependName($keyValue, $content, $conditionalName)`
- `setName($keyValue, $content, $modifiers)`
- `appendHttpEquiv($keyValue, $content, $conditionalHttpEquiv)`
- `offsetSetHttpEquiv($index, $keyValue, $content, $conditionalHttpEquiv)`
- `prependHttpEquiv($keyValue, $content, $conditionalHttpEquiv)`
- `setHttpEquiv($keyValue, $content, $modifiers)`

- `appendProperty($property, $content, $modifiers)`
- `offsetSetProperty($index, $property, $content, $modifiers)`
- `prependProperty($property, $content, $modifiers)`
- `setProperty($property, $content, $modifiers)`

The `$keyValue` item is used to define a value for the `name` or `http-equiv` key; `$content` is the value for 
the `content` key, and `$modifiers` is an optional associative array that can contain keys for `lang` and/or `scheme`.

You may also set meta tags using the `headMeta()` helper method, which has the following signature: 
`headMeta($content, $keyValue, $keyType = 'name', $modifiers = [], $placement = 'APPEND')`. 

`$keyValue` is the content for the key specified in `$keyType`, which should be either `name` or `http-equiv`. 
`$placement` can be `SET` (overwrites all previously stored values), `APPEND` (added to end of stack), or `PREPEND` (added to top of stack).

HeadMeta overrides each of `append()`, `offsetSet()`, `prepend()`, and `set()` to enforce usage of the special methods as 
listed above. Internally, it stores each item as a `stdClass` token, which it later serializes using the `itemToString()` 
method. This allows you to perform checks on the items in the stack, and optionally modify these items by simply 
modifying the object returned.

### Basic Usage

You may specify a new meta tag at any time. Typically, you will specify client-side caching rules or SEO keywords.

For instance, if you wish to specify SEO description, you'd be creating a meta name tag with the name 
'keywords' and the content the keywords you wish to associate with your page:

<div class="code-section">

```php
// setting meta description
$this->headMeta()->appendName('description', 'My SEO description for my awesome page');

// setting open graph tags
$this->headMeta()->setProperty('og:title', 'my article title');
$this->headMeta()->setProperty('og:type', 'article');

// setting content type and character set
$this->headMeta()->appendHttpEquiv('Content-Type', 'text/html; charset=UTF-8')
                 ->appendHttpEquiv('Content-Language', 'en-US');
```

```twig
{# setting meta description #}
{% do pimcore_head_meta().appendName('description', 'My SEO description for my awesome page') %}

{# setting open graph tags #}
{% do pimcore_head_meta().setProperty('og:title', 'my article title') %}
{% do pimcore_head_meta().setProperty('og:type', 'article') %}

{# setting content type and character set #}
{% do pimcore_head_meta().appendHttpEquiv('Content-Type', 'text/html; charset=UTF-8').appendHttpEquiv('Content-Language', 'en-US') %}
```

</div>

When you're ready to place your meta tags in the layout, simply echo the helper:

<div class="code-section">

```php
<?= $this->headMeta() ?>
```

```twig
{{ pimcore_head_meta() }}
```

</div>
