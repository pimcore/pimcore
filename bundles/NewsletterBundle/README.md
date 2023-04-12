# Pimcore Newsletter Bundle
This bundle provides a basic newsletter framework with the advantage to use all the data that is already stored in the system.

### Installation
#### Minimum Requirements
* Pimcore >= 11

#### Install

Install bundle via composer:
```bash 
composer require pimcore/newsletter-bundle
```

Enable bundle in `config/bundles.php`:
```php
return [
    ...
    Pimcore\Bundle\NewsletterBundle\PimcoreNewsletterBundle::class => ['all' => true],
    ...
];
```

Install bundle via console:
```bash
php bin/console pimcore:bundle:install PimcoreNewsletterBundle
```

Check if the bundle has been installed:
```bash
php bin/console pimcore:bundle:list
+---------------------------------+---------+-----------+----+-----+-----+
| Bundle                          | Enabled | Installed | I? | UI? | UP? |
+---------------------------------+---------+-----------+----+-----+-----+
| PimcoreNewsletterBundle         | ✔      | ✔       | ❌  | ✔  | ❌   |
+---------------------------------+---------+-----------+----+-----+-----+
```


#### Config options

```yaml
#### SYMFONY MAILER TRANSPORTS
framework:
    mailer:
        enabled: true
        transports:
            pimcore_newsletter: smtp://user:pass@smtp.example.com:port
        messenger:
            routing:
                'Pimcore\Bundle\NewsletterBundle\Messenger\SendNewsletterMessage': pimcore_core    

```

```yaml
pimcore_newsletter:
    source_adapters:
        defaultAdapter: pimcore_newsletter.document.newsletter.factory.default
        csvList: pimcore_newsletter.document.newsletter.factory.csv
    sender:
        name: 'Han Solo'
        email: 'han.solo@pimcore.com'
    return:
        name: 'Luke Skywalker'
        email: 'luke.skywalker@pimcore.com'
    debug:
        email_addresses: 'han.solo@pimcore.com,luke.skywalker@pimcore.com'
    use_specific: true
    default_url_prefix: 'https://my-host.com'    
    
```


### Uninstallation
Uninstalling the bundle does not clean up `newsletter` documents only the predefined document types. Before uninstalling make sure to remove or archive all dependent documents.
You can also use the following command to clean up you database. Create a backup before executing the command. All data will be lost.

```bash
 bin/console pimcore:document:cleanup newsletter
```


## Best Practice and Example

See [Newsletter](./doc/19_Newsletter.md) for a complete example and how to set up your newsletter.

## Document Types
This bundle introduces a new document type:

| Type                                           | Description                                   | 
|------------------------------------------------|-----------------------------------------------|
| [Newsletter](./doc/05_Newsletter_Documents.md) | Like an email but specialized for newsletter |

## Pimcore Twig Extensions
This bundle also adds a new twig extension. For more information checkout the main documentation

| Test                      | Description                                                                      |
|---------------------------|----------------------------------------------------------------------------------|
| `pimcore_document_newsletter`          | Checks if object is instanceof Newsletter                  |

## Contributing and Development

For details see our [Contributing guide](./CONTRIBUTING.md).
