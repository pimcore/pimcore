# Pimcore Bundles

Pimcore bundles follow the same rules as normal bundles, but need to implement `Pimcore\Extension\Bundle\PimcoreBundleInterface`
in order to show up in the extension manager. To get started quickly, you can extend `Pimcore\Extension\Bundle\AbstractPimcoreBundle`
which already implements all methods defined by the interface.

Besides name, description and version as shown in the extension manager, the interface defines the following methods you
can use to configure your bundle:

```php
interface PimcoreBundleInterface extends BundleInterface
{
    // name, description, version, ...

    /**
     * If the bundle has an installation routine, an installer is responsible of handling installation related tasks
     *
     * @return InstallerInterface|null
     */
    public function getInstaller();

    /**
     * Get path to include in admin iframe
     *
     * @return string|RouteReferenceInterface|null
     */
    public function getAdminIframePath();

    /**
     * Get javascripts to include in admin interface
     *
     * @return string[]|RouteReferenceInterface[]
     */
    public function getJsPaths();

    /**
     * Get stylesheets to include in admin interface
     *
     * @return string[]|RouteReferenceInterface[]
     */
    public function getCssPaths();

    /**
     * Get javascripts to include in editmode
     *
     * @return string[]|RouteReferenceInterface[]
     */
    public function getEditmodeJsPaths();

    /**
     * Get stylesheets to include in editmode
     *
     * @return string[]|RouteReferenceInterface[]
     */
    public function getEditmodeCssPaths();
}
```

## Installer

By default, a Pimcore bundle does not define any installation or update routines, but you can use the `getInstaller()` method
to return an instance of a `Pimcore\Extension\Bundle\Installer\InstallerInterface`. If a bundle returns an installer instance,
this installer will be used by the extension manager to allow installation/uninstallation.

The `install` method can be used to create database tables and do other initial tasks. The `uninstall` method should make
sure to undo all these things. The installer is also the right place to check for requirements such as minimum Pimcore
version or read/write permissions on the filesystem.

## Registration to ExtensionManager

To make use of the installer, a bundle needs to be manager through the extension manager and not manually registered on
the `AppKernel` as normal bundles. As the extension manager needs to find the bundles it can manage, a pimcore bundle needs
to fulfill the following requirements:

  * Implement the `PimcoreBundleInterface`
  * Located in a directory which is scanned for Pimcore bundles. This is configured in the `pimcore.bundles.search_paths`
    configuration and defaults to `src/`
    
If you add a new bundle to `src/YourBundleName/YourBundleName.php` and it implements the interface, it should be automatically
shown in the extension manager.

### Composer bundles

If you provide your bundle via composer, it won't be automatically found. To include your package directory to the list 
of scanned paths, please set the package type of your package to `pimcore-bundle`. Additionally, if you set the specific
bundle name through the `pimcore.bundles` composer extra config no filesystem scanning will be done which will have a
positive effect on the bundle lookup performance.

> Whenever you can, you should explicitely set the bundle class name through the extra config.

An example of a `composer.json` defining a pimcore bundle:

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


