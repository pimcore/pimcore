# HeadTitle Templating Helper

The HTML `<title>` element is used to provide a title for an HTML document. 
The HeadTitle helper allows you to programmatically create and store the title for later retrieval and output.

### Basic Usage

You may specify a title tag at any time. 
A typical usage would have you setting title segments for each level of depth in your application: site, 
controller, action, and potentially resource.

```php
$this->headTitle("My first part")
     ->headTitle("The 2nd part");
 
// setting the site in the title; possibly in the layout script:
$this->headTitle('My Pimcore Website');
 
// setting a separator string for segments:
$this->headTitle()->setSeparator(' / ');
```

When you're finally ready to render the title in your layout script, simply echo the helper:

```php
<?= $this->headTitle() ?>
<!-- renders My first part / The 2nd part / My Pimcore Website -->
```

