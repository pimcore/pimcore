# Pimcore Newsletter Bundle
This bundle provides a basic newsletter framework with the advantage to use all the data that is already stored in the system.

## Document Types
This bundle introduces a new document type:

| Type                                             | Description                                   | 
|--------------------------------------------------|-----------------------------------------------|
| [Newsletter](./doc/05_Newsletter_Documents) | Like an email but specialized for newsletter |

## Pimcore Twig Extensions
This bundle also adds a new twig extension. For more information checkout the main documentation

| Test                      | Description                                                                      |
|---------------------------|----------------------------------------------------------------------------------|
| `pimcore_document_newsletter`          | Checks if object is instanceof Newsletter                  |


### Installation
#### Minimum Requirements
* Pimcore >= 11

#### Install
Install bundle via composer:
```bash 
composer require pimcore/newsletter-bundle
```
Enable bundle via console or extensions manager:
```bash
php bin/console pimcore:bundle:enable PimcoreNewsletterBundle
php bin/console pimcore:bundle:install PimcoreNewsletterBundle
```

Check if the bundle has been installed:
```bash
php bin/console pimcore:bundle:list
+---------------------------------+---------+-----------+----+-----+-----+
| Bundle                          | Enabled | Installed | I? | UI? | UP? |
+---------------------------------+---------+-----------+----+-----+-----+
| PimcoreNewsletterBundle  | ✔       | ✔        | ❌  | ✔  | ❌ |
+---------------------------------+---------+-----------+----+-----+-----+
```

After installing the bundle and the required dependencies you need to configure the settings under *Settings >  Web-to-Print*.


### Uninstallation
Uninstalling the bundle does not clean up `newsletter` documents only the predefined document types. Before uninstalling make sure to remove or archive all dependent documents.
You can also use the following command to clean up you database. Create a backup before executing the command. All data will be lost.

```bash
 bin/console pimcore:document:cleanup newsletter
```

### Best Practice

- [Newsletter](./doc/19_Newsletter.md)

## Contributing and Development

For details see our [Contributing guide](./CONTRIBUTING.md).