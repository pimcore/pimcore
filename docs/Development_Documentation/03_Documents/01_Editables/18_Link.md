# Link Editable

## General 

The link editable is used for dynamic link creation in documents.

## Configuration

You can pass every valid attribute an `<a>`-tag can have ([w3.org - Link](http://www.w3.org/TR/html401/struct/links.html#h-12.2)), 
such as: `class`, `target`, `id`, `style`, `accesskey`, `name`, `title` and additionally the following: 

| Name     | Type     | Description                                                                                                    |
|----------|----------|----------------------------------------------------------------------------------------------------------------|
| `reload` | boolean  | Set to true to reload the page in editmode after changing the state.                                           |
| `tag`    | string   | A tag name that is used instead of the default `div` for the surrounding container of this element in editmode |

## Methods

| Name              | Return      | Description                          |
|-------------------|-------------|--------------------------------------|
| `getHref()`       | string      | Get the path of this link            |
| `getText()`       | string      | Get the text of the link             |
| `getTarget()`     | string      | Get the target of the link           |
| `getParameters()` | string      | Get the query params of the link     |
| `getAnchor()`     | string      | Get the anchor text of the link      |
| `getTitle()`      | string      | Get the title of the link            |
| `getRel()`        | string      | Get the rel text of the link         |
| `getTabindex()`   | string      | Get the tabindex of the link         |
| `getAccessKey()`  | string      | Get the access key of the link       |
| `isEmpty()`       | string      | Whether the editable is empty or not |

## Examples

### Basic Usage

```php
<p>
    <?= $this->translate("Visit our"); ?> 
    <?= $this->link("blogLink"); ?>
</p>
```

You could see the backend preview in the picture, below.

![Link editable - adminitration panel](../../img/editables_link_backend_preview.png)

And the frontend:

![Link editable - frontend](../../img/editables_link_frontend_preview.png)



### Use Link in the Block Editable

Let's see how to make a list of links with [Block](./06_Block.md).

```php
<h3><?= $this->translate("Useful links"); ?></h3>
<ul>
    <?php while ($this->block("linkblock")->loop()): ?>
        <li>
            <?= $this->link("myLink", ["class" => "special-link-class"]); ?>
        </li>
    <?php endwhile; ?>
</ul>
```

The above example renders a list of links: 
![The links list in the backend](../../img/editables_link_inside_block.png)





