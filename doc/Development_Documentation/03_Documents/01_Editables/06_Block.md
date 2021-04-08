# Block Editable

## General

The block element is an iterating component which is really powerful.
Basically a block is only a loop, but you can use other editables within this loop, so it's possible to repeat a set of 
editables to create structured content (eg. a link list, or a image gallery).
The items in the loop as well as their order can be defined by the editor with the block controls provided in the editmode. 

## Configuration

| Name        | Type      | Description                                                                                                                  |
|-------------|-----------|------------------------------------------------------------------------------------------------------------------------------|
| `limit`     | integer   | Max. amount of iterations.                                                                                                   |
| `reload`    | bool      | Reload editmode on add, move or remove (default=false)                                                                       |
| `default`   | integer   | If block is empty, this specifies the iterations at startup.                                                                 |
| `manual`    | bool      | Forces the manual mode, which enables a complete custom HTML implementation for blocks, for example using `<table>` elements |
| `class`     | string    | A CSS class that is added to the surrounding container of this element in editmode                                           |

## Methods

| Name            | Return    | Description                                                   |
|-----------------|-----------|---------------------------------------------------------------|
| `isEmpty()`     | bool      | Whether the editable is empty or not.                         |
| `getCount()`    | int       | Get the total amount of iterations.                           |
| `getCurrent()`  | int       | Get the current index while looping.                          |
| `getElements()` | array     | Return a array for every loop to access the defined children. |

## The Block Controls

| Control                                   | Operation                                |
|-------------------------------------------|------------------------------------------|
| ![+](../../img/block_plus.png)            | Add a new block at the current position. |
| ![-](../../img/block_x.png)               | Remove the current block.                |
| ![up and down](../../img/block_order.png) | Move Block up and down.                  |

## Basic Usage

```twig
{% for i in pimcore_block('contentblock').iterator %}
    <h2>{{ pimcore_input('subline') }}</h2>
    {{ pimcore_wysiwyg('content') }}
{% endfor %}
```

The result in editmode should looks like to following: 
![Block in editmode](../../img/block_editmode.png)

And in the frontend of the application:
![Block in the frontend](../../img/block_frontend_preview.png)

## Advanced Usage

### Example for `getCurrent()`

```twig
{% set myBlock = pimcore_block('contentblock') %}
{% for i in myBlock.iterator %}
    {% if myBlock.current > 0 %}
        Insert this line only after the first iteration<br />
        <br />
    {% endif %}
    <h2>{{ pimcore_input('subline') }}</h2>
{% endfor %}
```

### Using Manual Mode

The manual mode offers you the possibility to deal with block the way you like, this is for example useful with tables: 

```twig
{% set block = pimcore_block('gridblock', {'manual' : true, 'limit' : 6}).start() %}
<table>
    <tr>
        {% for b in block.iterator %}
            {% do block.blockConstruct() %}
              <td customAttribute="{{ pimcore_input("myInput").getData() }}">
                    {% do block.blockStart() %}
                        <div style="width:200px; height:200px;border:1px solid black;">
                            {{ pimcore_input("myInput") }}
                        </div>
                    {% do block.blockEnd() %}
                </td>
            {% do block.blockDestruct() %}
        {% endfor %}
    </tr>
</table>
{% do block.end() %}
```

### Using Manual Mode with custom button position

If you want to wrap buttons in a div or change the Position.

```twig
{% set block = pimcore_block("gridblock", {"manual": true}).start() %}
<table>
    <tr>
        {% for b in block.iterator %}
            {% do block.blockConstruct() %}
                <td customAttribute="{{ pimcore_input("myInput").data }}">
                    {% do block.blockStart() %}
                        <div style="background-color: #fc0; margin-bottom: 10px; padding: 5px; border: 1px solid black;">
                            {% do block.blockControls() %}
                        </div>
                        <div style="width:200px; height:200px;border:1px solid black;">
                            {{ pimcore_input("myInput") }}
                        </div>
                    {% do block.blockEnd() %}
                </td>
            {% do block.blockDestruct() %}
        <?php } ?>
    </tr>
</table>
<?php $block->end(); ?>
```

### Accessing Data Within a Block Element

Bricks and structure refer to the CMS demo (content/default template).

```php
<?php
// load document
$document = \Pimcore\Model\Document\Page::getByPath('/en/basic-examples/galleries');
 
// Bsp #1 | get the first picture from the first "gallery-single-images" brick
$image = $document
    ->getElement('content')                             // view.html.php > $this->areablock('content')
        ->getElement('gallery-single-images')[0]        // get the first entry for this brick
            ->getBlock('gallery')->getElements()[0]     // view.html.php > $this->block("gallery")->loop()
                ->getImage('image')                     // view.html.php > $this->image("image")
;
 
 
var_dump("Bsp #1: " . $image->getSrc());
```
