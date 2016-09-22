# Glossary

## General

The glossary module is a powerful tool making internal linking easy and smart.
In a special editor you can define your terms which are replaced automatically with a link to the defined page.

But the glossary is not only useful for internal linking, it's also perfect for explaining abbreviations and/or acronyms.

## How it Works

<div class="inline-imgs">

Open the glossary editor ![Tools](../img/Icon_tools.png) **Tools -> Glossary** and define some terms.

</div>

![Glossary grid](../img/glossary_grid.png)

Then you have to define one or more regions in your views, telling the glossary where you want it to replace your terms.

```php
<?php $this->glossary()->start(); ?>
<div>
    <?= $this->wysiwyg("content", [
        "height" => 200
    ]); ?>
</div>
<?php $this->glossary()->stop() ?>
```

Now the outpu of the WYSIWYG field will look like this.

![Glossary fomntend](../img/glossary_frontend.png)

And the HTML-markup will look like, below.

```php
<p>
    <abbr title="Hypertext Preprocessor">PHP</abbr> is a widely used, general-purpose scripting language that was originally designed for web development to produce dynamic web pages. For this purpose, <abbr title="Hypertext Preprocessor">PHP</abbr> code is embedded into the HTML source document and interpreted by a web server with a <abbr title="Hypertext Preprocessor">PHP</abbr> processor module, which generates the web page&nbsp; document. As a general-purpose programming language, <abbr title="Hypertext Preprocessor">PHP</abbr> code is processed by an interpreter application in command-line mode performing desired operating system operations and producing program output on its standard output channel. It may also function as a graphical application. <abbr title="Hypertext Preprocessor">PHP</abbr> is available as a processor for most modern web servers and as standalone interpreter on most operating systems and computing platforms. You can <a href="http://www.php.net/">download</a> it free at php.net.
</p>
```

> **Note**   
> Since the glossary depends on languages you'll have to register a language first.

[Read more about this topic here.](../06_Multi_Language_i18n/02_Localize_your_Documents.md)
