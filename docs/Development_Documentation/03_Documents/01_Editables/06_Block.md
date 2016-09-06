# Block editable

## General

A block element is a iterating component which is really powerful.
Basically a block is only a loop, but you can use other editables in this loop, so it's possible to repeat a set of editables to create a structured page.
The items in the loop as well as their order can be defined by the editor with the block controls.

## Configuration

| Name      | Type      | Description                                                                                                                |
|-----------|-----------|----------------------------------------------------------------------------------------------------------------------------|
| limit     | integer   | Max. amount of iterations.                                                                                                 |
| default   | integer   | If block is empty, this specifies the iterations at startup.                                                               |
| manual    | bool      | forces the manual mode, which enables a complete free implementation for blocks, for example using read \<table\> elements |

## Methods

| Name                | Description                                                 |
|---------------------|-------------------------------------------------------------|
| ```getCount()```    | Get the total amount of iterations.                         |
| ```getCurrent()```  | Get the current index while looping.                        |
| ```getElements()``` | Return a array for every loop to access the defined childs. |

## The Block controls

| Control                                   | Operation                                |
|-------------------------------------------|------------------------------------------|
| ![+](../../img/block_plus.png)            | Add a new block at the current position. |
| ![-](../../img/block_x.png)               | Remove the current block.                |
| ![up and down](../../img/block_order.png) | Move Block up and down.                  |

## Basic usage

```php
<?php while($this->block("contentblock")->loop()) { ?>
    <h2><?php echo $this->input("subline"); ?></h2>
    <?php echo $this->wysiwyg("content"); ?>
<?php } ?>
```

The result in editmode should look like, below:

![Block in editmode](../../img/block_editmode.png)

And in the frontend of the application:

![Block in the frontend](../../img/block_frontend_preview.png)

## Advanced usage

### Advanced usage with different bricks.

```php
<?php while($this->block("contentblock")->loop()) { ?>
    <?php if($this->editmode) { ?>
        <?php echo $this->select("blocktype", [
            "store" => [
                ["wysiwyg", "WYSIWYG"],
                ["contentimages", "WYSIWYG with images"],
                ["video", "Video"]
            ],
            "reload" => true
        ]); ?>
    <?php } ?>
     
    <?php if(!$this->select("blocktype")->isEmpty()) {
        $this->template("content/blocks/".$this->select("blocktype")->getData().".php");
    } ?>
<?php } ?>
 
<?php while($this->block("teasers", ["limit" => 2])->loop()) { ?>
    <?php echo $this->snippet("teaser") ?>
<?php } ?>
```

### Example for ```getCurrent()```

```php

<?php while ($this->block("myBlock")->loop()) { ?>
    <?php if ($this->block("myBlock")->getCurrent() > 0) { ?>
        Insert this line only after the first iteration<br />
        <br />
    <?php } ?>
    <h2><?php echo $this->input("subline"); ?></h2>
     
<?php } ?>
```

### Using manual mode

The manual mode offers you the possibility to deal with block the way you like, this is for example useful with tables: 

```php
<?php $block = $this->block("gridblock", ["manual" => true])->start(); ?>
<table>
    <tr>
        <?php while ($block->loop()) { ?>
            <?php $block->blockConstruct(); ?>
                <td customAttribute="<?php echo $this->input("myInput")->getData() ?>">
                    <?php $block->blockStart(); ?>
                        <div style="width:200px; height:200px;border:1px solid black;">
                            <?php echo $this->input("myInput"); ?>
                        </div>
                    <?php $block->blockEnd(); ?>
                </td>
            <?php $block->blockDestruct(); ?>
        <?php } ?>
    </tr>
</table>
<?php $block->end(); ?>
```

### Using fluent interfaces

```php
// load document
$doc = \Pimcore\Model\Document\Page::getByPath('/en/basic-examples/galleries');
 
// Bsp #1 | get the first picture from the first "gallery-single-images" brick
$image = $doc
    ->getElement('content')                             // view.php > $this->areablock('content')
        ->getElement('gallery-single-images')[0]        // get the first entry for this brick
            ->getBlock('gallery')->getElements()[0]     // view.php > $this->block("gallery")->loop()
                ->getImage('image')                     // view.php > $this->image("image")
;
 
 
var_dump("Bsp #1: " . $image->getSrc());
```