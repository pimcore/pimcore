# HeadTitle Templating Helper

> The HeadTitle templating helper is an extension of the [Placeholder Templating Helper](./00_Placeholder.md)

The HTML `<title>` element is used to provide a title for an HTML document. 
The HeadTitle helper allows you to programmatically create and store the title for later retrieval and output.

### Basic Usage

You may specify a title tag at any time. 
A typical usage would have you setting title segments for each level of depth in your application: site, 
controller, action, and potentially resource.

<div class="code-section">

```php
$this->headTitle("My first part")
     ->headTitle("The 2nd part");
 
// setting the site in the title; possibly in the layout script:
$this->headTitle('My Pimcore Website');
 
// setting a separator string for segments:
$this->headTitle()->setSeparator(' / ');
```

```twig
{% do pimcore_head_title('My first part') %}
{% do pimcore_head_title('The 2nd part') %}

{# setting the site in the title; possibly in the layout script: #}
{% do pimcore_head_title('My Pimcore Website') %}

{# setting a separator string for segments: #}
{% do pimcore_head_title().setSeparator(' / ') %}
```

</div>

When you're finally ready to render the title in your layout script, simply echo the helper:

<div class="code-section">

```php
<?= $this->headTitle() ?>
<!-- renders My first part / The 2nd part / My Pimcore Website -->
```

```twig
{{ pimcore_head_title() }}
{# renders My first part / The 2nd part / My Pimcore Website #}
```

</div>
