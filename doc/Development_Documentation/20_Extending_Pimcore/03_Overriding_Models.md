# Overriding Models / Entities in Pimcore
 
Sometimes it is necessary to override certain functionalities of Pimocore's core models, therefore it is possible to 
override the core models with your own classes. 

Currently this works for all implementations of the following classes: 
- `Pimcore\Model\Document`
- `Pimcore\Model\Document\Listing`
- `Pimcore\Model\AbstractObject`
- `Pimcore\Model\Object\Listing`
- `Pimcore\Model\Asset`
- `Pimcore\Model\Asset\Listing` 

So for example overriding a listing class of a custom class definition like `Pimcore\Model\Object\News\Listing` or 
`Pimcore\Model\Asset\Image` is supported. 

## Configure an Override 

The configuration is a simple key / value map in your `app/config/config.yml` using the key 
`pimcore.models.class_overrides`, for example: 

```yaml 
pimcore:
    models:
        class_overrides:
            'Pimcore\Model\Object\News': 'AppBundle\Model\Object\News'
            'Pimcore\Model\Object\News\Listing': 'AppBundle\Model\Object\News\Listing'
```

**It is crucial that your override class extends the origin class, if not you'll break the entire system.**

> **Don't forget to clear all caches (Symfony + Data Cache) after you have configured a class override**
`./bin/console cache:clear --no-warmup && ./bin/cache pimcore:cache:clear`

## Example 

In your `app/config/config.yml`: 

```yaml 
pimcore:
    models:
        class_overrides:
            'Pimcore\Model\Object\News': 'AppBundle\Model\Object\News'
```

Your `AppBundle\Model\Object\News`: 

```php
<?php 

namespace AppBundle\Model\Object; 

class News extends \Pimcore\Model\Object\News {

    // start overriding stuff 
    public function getMyCustomAttribute() {
        
    }
}
```
