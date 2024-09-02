# UUID Support

> **Note**
> This feature requires UUID bundle to be active. Please make sure that you have `\Pimcore\Bundle\UuidBundle\PimcoreUuidBundle::class` entry in your `config/bundles.php` and make sure that the bundle is installed and enabled.

Pimcore provides a toolkit for UUID-support. To activate the UUID-support, an instance identifier 
has to be set manually in the config.yaml file.

```yaml
pimcore_uuid:
  instance_identifier: 'your_unique_instance_identifier'
```

Once set, Pimcore automatically creates an UUID for each newly created document, asset, class and object. 
With the class `Tool\UUID` you have access to the UUIDs as follows:

```php
use Pimcore\Bundle\UuidBundle\Model\Tool;
  
//get UUID for given element (document, asset, class, object)
$uuid = Tool\UUID::getByItem($document);
 
//get element for given UUID
$document = Tool\UUID::getByUuid($uuid);
 
//create and save uuid for given element
$uuid = Tool\UUID::create($document);
```
