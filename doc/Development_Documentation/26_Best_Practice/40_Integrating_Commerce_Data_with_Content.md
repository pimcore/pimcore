# Integrating Commerce Data with Content

Content commerce, shop everywhere, vanish separation of content and commerce - these are key phrases that popup with 
every state-of-the-art e-commerce project. With its integrated approach Pimcore does exactly that and provides several
tools to provide the best experience for the users.

One of these tools are [Renderlets](../03_Documents/01_Editables/28_Renderlet.md),
which provide a great way to integrate dynamic object (thus commerce) content to Pimcore documents. 

![Demo](img/demo.jpg)


Follow the steps to create a Product teaser similar to the one in our [demo](https://demo.pimcore.fun/).

### Create Area Brick `MyProductTeaser` with Renderlet 

**MyProductTeaser Implementation** 
```php
<?php
namespace App\Document\Areabrick;

class MyProductTeaser extends AbstractAreabrick
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'My Product Teaser';
    }
}

```

**MyProductTeaser Template**
```twig
<div class="row">
    {% for i in pimcore_block('teaserblock').iterator %}
        {{ pimcore_renderlet('productteaser', {
            controller: 'shop',
            action: 'productCell',
            width: 270,
            height: 370,
            title: 'Drag a product here',
            editmode: editmode
        }) }}
    {% endfor %}
</div>
```


### Create Controller and Action for Teaser Content

**Controller Action** 
```php
    public function productCellAction(Request $request)
    {
    
        $id = $request->get("id");
        $type = $request->get("type");

        if($type == 'object') {

            $product = Product::getById($id);
            return $this->render('product/product_cell.html.twig', ['product' => $product]);
        } else {
            throw new \Exception("Invalid Type");
        }
    }
```

**Template** 
```twig
{% set col = app.request.get('editmode') ? 12 : 3 %}

<div class="col-sm-{{ col }} col-lg-{{ col }} col-md-{{ col }}">
    <div class="thumbnail product-list-item">
        <a href="{{ product.linkProduct.detailUrl }}">
            {{ product.getFirstImage('productList').html({class: 'product-image'}) }}
            <div class="caption">
                <h4 class="pull-right">{{ product.OSPrice }}</h4>

                <h4>{{ product.OSName }}</h4>
    
                <p>{{ product.description|striptags|trim[:70] }}</p>

            </div>
        </a>

        <div class="buttons">
            <div class="row">
                <div class="col-md-6">
                </div>
                <div class="col-md-6">
                    <a href="{{ pimcore_url({
                        language: language,
                        action: 'add',
                        item: product.id,
                    }, 'cart') }}" class="btn btn-success btn-product">
                        <span class="glyphicon glyphicon-shopping-cart"></span>
                        {{ 'shop.buy'|trans }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
```
