# Pimcore Bundles

Pimcore bundles follow the same rules as normal bundles, but need to implement `Pimcore\Extension\Bundle\PimcoreBundleInterface`
in order to show up in the `pimcore:bundle:list` command. This gives you the following possibilities:

* The bundle shows up in the `pimcore:bundle:list` command with info, if bundle can be installed or uninstalled.
* The bundle can be installed with `pimcore:bundle:install` command or uninstall with `pimcore:bundle:uninstall`
  command to trigger the installation/uninstallation, for example to install/update database structure.
* The bundle adds methods to natively register JS and CSS files to be loaded with the admin interface and in editmode.

To get started quickly, you can extend `Pimcore\Extension\Bundle\AbstractPimcoreBundle` which already implements all methods
defined by the interface. Besides name, description and version, the interface defines the following methods you
can use to configure your bundle:

```php
interface PimcoreBundleInterface extends BundleInterface
{
    // name, description, version, ...

    /**
     * If the bundle has an installation routine, an installer is responsible of handling installation related tasks
     */
    public function getInstaller(): ?InstallerInterface;

    /**
     * Get javascripts to include in admin interface
     *
     * @return string[]|RouteReferenceInterface[]
     */
    public function getJsPaths(): array;

    /**
     * Get stylesheets to include in admin interface
     *
     * @return string[]|RouteReferenceInterface[]
     */
    public function getCssPaths(): array;

    /**
     * Get javascripts to include in editmode
     *
     * @return string[]|RouteReferenceInterface[]
     */
    public function getEditmodeJsPaths(): array;

    /**
     * Get stylesheets to include in editmode
     *
     * @return string[]|RouteReferenceInterface[]
     */
    public function getEditmodeCssPaths(): array;
}
```

## Installer

By default, a Pimcore bundle does not define any installation or update routines, but you can use the `getInstaller()` method
to return an instance of a `Pimcore\Extension\Bundle\Installer\InstallerInterface`. If a bundle returns an installer instance,
this installer will be used by the command `pimcore:bundle:install` to allow installation/uninstallation.

The `install` method can be used to create database tables and do other initial tasks. The `uninstall` method should make
sure to undo all these things. The installer is also the right place to check for requirements such as minimum Pimcore
version or read/write permissions on the filesystem.

Read more in [Installers](./01_Installers.md).

### Composer bundles

If you provide your bundle via composer, it won't be automatically found. To include your package directory to the list 
of scanned paths, please set the package type of your package to `pimcore-bundle`. Additionally, if you set the specific
bundle name through the `pimcore.bundles` composer extra config no filesystem scanning will be done which will have a
positive effect on the bundle lookup performance.

> Whenever you can, you should explicitly set the bundle class name through the extra config.

An example of a `composer.json` defining a Pimcore bundle:

```json
{
    "name": "myVendor/myBundleName",
    "type": "pimcore-bundle",
    "autoload": {
        "psr-4": {
            "MyBundleName\\": ""
        }
    },
    "extra": {
        "pimcore": {
            "bundles": [
                "MyBundleName\\MyBundleName"
            ]
        }
    }
}
```

### Encore
If you use Encore to build the assets of your bundle you can use the methods from `Pimcore\Helper\EncoreHelper`. 

`EncoreHelper::getBuildPathsFromEntrypoints` accept the path to `entrypoints.json`, file ending as string and returns an array with paths to the build files.

```php
class PimcoreExampleBundle extends AbstractPimcoreBundle
{
    use PackageVersionTrait;

    public function getCssPaths(): array
    {
        return EncoreHelper::getBuildPathsFromEntrypoints($this->getPath() . '/public/build/example/entrypoints.json', 'css');
    }

    public function getJsPaths(): array
    {
        return EncoreHelper::getBuildPathsFromEntrypoints($this->getPath() . '/public/build/example/entrypoints.json');
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
    
    ...
}
```
