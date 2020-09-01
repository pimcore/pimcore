# HeadStyle Templating Helper

> The HeadStyle templating helper is an extension of the [Placeholder Templating Helper](./00_Placeholder.md)

The HTML `<style>` element is used to include CSS stylesheets inline in the HTML <head> element.

> **Note**: Use HeadLink to link CSS files
HeadLink should be used to create <link> elements for including external stylesheets. HeadStyle is used when you wish to define your stylesheets inline. 
The HeadStyle helper supports the following methods for setting and adding stylesheet declarations:

- `appendStyle($content, $attributes = [])`
- `offsetSetStyle($index, $content, $attributes = [])`
- `prependStyle($content, $attributes = [])`
- `setStyle($content, $attributes = [])`

In all cases, `$content` is the actual CSS declarations. $attributes are any additional attributes you wish to provide 
to the style tag: `lang`, `title`, `media`, or dir are all permissible.

> Note: Setting Conditional Comments
HeadStyle allows you to wrap the style tag in conditional comments, which allows you to hide it from specific browsers. 
To add the conditional tags, pass the conditional value as part of the $attributes parameter in the method calls. 

### Headstyle With Conditional Comments

<div class="code-section">

```php
// adding scripts
$this->headStyle()->appendStyle($styles, array('conditional' => 'lt IE 11'));
``` 

```twig
{# adding scripts #}
{% do pimcore_head_style().appendStyle(styles, {'conditional': 'lt IE 11'}) %}
```

</div>

HeadStyle also allows capturing style declarations; this can be useful if you want to create the declarations 
programmatically, and then place them elsewhere. The usage for this will be showed in an example below.

Finally, you can also use the `headStyle()` method to quickly add declarations elements; the signature for this is 
`headStyle($content$placement = 'APPEND', $attributes = [])`. `$placement` should be either `APPEND`, `PREPEND` , or `SET`.

HeadStyle overrides each of `append()`, `offsetSet()`, `prepend()`, and `set()` to enforce usage of the special 
methods as listed above. Internally, it stores each item as a `stdClass` token, which it later serializes using the 
`itemToString()` method. This allows you to perform checks on the items in the stack, and optionally modify these 
items by simply modifying the object returned.


### Basic Usage

You may specify a new style tag at any time:

<div class="code-section">

```php
// adding styles
$this->headStyle()->appendStyle($styles);
```

```twig
{# adding styles #}
{% do pimcore_head_style().appendStyle(styles) %}
```

</div>

Order is very important with CSS; you may need to ensure that declarations are loaded in a specific order due to the 
order of the cascade; use the various append, prepend, and offsetSet directives to aid in this task:

<div class="code-section">

```php
// Putting styles in order
 
// place at a particular offset:
$this->headStyle()->offsetSetStyle(100, $customStyles);
 
// place at end:
$this->headStyle()->appendStyle($finalStyles);
 
// place at beginning
$this->headStyle()->prependStyle($firstStyles);
```

```twig
{# place at a particular offset: #}
{% do pimcore_head_style().offsetSetStyle(100, customStyles) %}

{# place at end: #}
{% do pimcore_head_style().appendStyle(finalStyles) %}

{# place at beginning #}
{% do pimcore_head_style().prependStyle(firstStyles) %}
```

</div>

When you're finally ready to output all style declarations in your layout script, simply echo the helper:

<div class="code-section">

```php
<?= $this->headStyle() ?>
```

```twig
{{ pimcore_head_style() }}
```

</div>

### Capturing Style Declarations

Sometimes you need to generate CSS style declarations programmatically. While you could use string concatenation, 
heredocs, and the like, often it's easier just to do so by creating the styles and sprinkling in PHP tags. 
HeadStyle lets you do just that, capturing it to the stack:

<div class="code-section">

```php
<?php $this->headStyle()->captureStart() ?>
body {
    background-color: <?php echo $this->bgColor ?>;
}
<?php $this->headStyle()->captureEnd() ?>
```

```twig
{% do pimcore_head_style().captureStart() %}
    body {
        background-color: red
    }
{% do pimcore_head_style().captureEnd() %}
```

</div>

The following assumptions are made:

The style declarations will be appended to the stack. If you wish for them to replace the stack or be added to the top, 
you will need to pass `SET` or `PREPEND`, respectively, as the first argument to `captureStart()`.

If you wish to specify any additional attributes for the `<style>` tag, pass them in an array as the second argument to 
`captureStart()`.

