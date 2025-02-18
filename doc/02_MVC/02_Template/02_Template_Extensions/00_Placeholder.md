# Placeholder Templating Extension

The `Placeholder` extension is used to persist content between view scripts and view instances. It also offers 
some useful features such as aggregating content, capturing view script content for later use, and adding pre- and 
post-text to content (and custom separators for aggregated content).

### Basic Usage

Basic usage of placeholders is to persist view data. Each invocation of the Placeholder extension expects a 
placeholder `name`. The helper then returns a placeholder container object that you can either manipulate or simply 
echo out.

```twig
{% do pimcore_placeholder('foo').set("Some text for later") %}

{# outputs "Some text for later" #}
{{ pimcore_placeholder('foo') }}
```

### Aggregate Content
Aggregating content via placeholders can be useful at times as well. For instance, your view script may have a variable 
array from which you wish to retrieve messages to display later. A later view script can then determine how those will 
be rendered.

The Placeholder view helper uses containers that extend ArrayObject, providing a rich feature set for manipulating 
arrays. In addition, it offers a variety of methods for formatting the content stored in the container:

- `setPrefix($prefix)` sets text with which to prefix the content. Use `getPrefix()` at any time to determine what the 
current setting is.
- `setPostfix($postfix)` sets text with which to append the content. Use `getPostfix()` at any time to determine what 
the current setting is.
- `setSeparator($seperator)` sets text with which to separate aggregated content. Use `getSeparator()` at any time to 
determine what the current setting is.
- `setIndent($indent)` can be used to set an indentation value for content. If an integer is passed, that number of 
spaces will be used. If a string is passed, the string will be used. Use `getIndent()` at any time to determine what 
the current setting is.

```twig
{% do pimcore_placeholder('foo').setPrefix("<ul>\n    <li>")
    .setSeparator("</li><li>\n")
    .setIndent(4)
    .setPostfix("</li></ul>\n")
%}

{# outputs as unordered list with pretty indentation #}
{{ pimcore_placeholder('foo') }}

```

### Capture Content (deprecated)
Occasionally you may have content for a placeholder in a view script that is easiest to template. The `Placeholder` extension allows you to capture arbitrary content for later rendering using the following API.

- `captureStart($type, $key)` begins capturing content.
   - `$type` should be one of the Placeholder constants `APPEND` or `SET`. If `APPEND`, captured content is appended to the 
list of current content in the placeholder. If `SET`, captured content is used as the sole value of the placeholder 
(potentially replacing any previous content). By default, `$type` is `APPEND`.
   - `$key` can be used to specify a specific key in the placeholder container to which you want content captured.
   - `captureStart()` locks capturing until `captureEnd()` is called; you cannot nest capturing with the same placeholder 
   container. Doing so will raise an exception.

- `captureEnd()` stops capturing content, and places it in the container object according to how `captureStart()` was called.

```twig
{# Default capture: append #}
{% do pimcore_placeholder('foo').captureStart() %}

{% for datum in data %}
    <div class="foo">
        <h2>{{ datum.title }}</h2>
        <p>{{ datum.content }}</p>
    </div>
{% endfor %}

{% do pimcore_placeholder('foo').captureEnd() %}

{{ pimcore_placeholder('foo') }}
```

```twig
{#Default capture: to key #}
{% do pimcore_placeholder('foo').captureStart('SET', 'data') %}

{% for datum in data %}
    <div class="foo">
        <h2>{{ datum.title }}</h2>
        <p>{{ datum.content }}</p>
    </div>
{% endfor %}

{% do pimcore_placeholder('foo').captureEnd() %}

{{ pimcore_placeholder('foo').data | raw }}
```

**Note:** The `captureStart()` and `captureEnd()` methods are deprecated as of Pimcore 11.4.0. Use a combination of `set` and `pimcore_placeholder` to achieve the same result.

```twig
{% set placeholderData %}
    {% for datum in data %}
        <div class="foo">
            <h2>{{ datum.title }}</h2>
            <p>{{ datum.content }}</p>
        </div>
    {% endfor %}
{% endset %}

{% do pimcore_placeholder('foo').set(placeholderData) %}
{{ pimcore_placeholder('foo') }}
```

```twig
{% set placeholderData %}
    Test to append some additional content
{% endset %}

{% do pimcore_placeholder('foo').append(placeholderData) %}
{{ pimcore_placeholder('foo') }}
```
