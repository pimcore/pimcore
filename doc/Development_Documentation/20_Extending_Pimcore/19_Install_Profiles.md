# Install Profiles

If you want to create a prepackaged setup structure, you can create a custom install profile which can be used when
installing Pimcore. An install profile is defined in a `manifest.yml` which contains all the information needed for the
installer:

```yaml
# The name of the install profile as shown in the installer.
name:                 null
files:

    # Files to copy/symlink during installations. Can be paths or globs.
    add:                  []
    
db:

    # DB data files to import during installation.
    data_files:           []
    
pimcore_bundles:

    # Bundles to enable during installation. Can be either set to a boolean or configure options explicitely.
    enable:

        # Prototype
        name:
            enabled:              true
            priority:             ~
            environments:         []

    # Bundles to install during installation. Not that bundles listed here will not be automatically enabled.
    install:

        # Prototype
        name:
            enabled:              true
```

An example using the manifest to implicitely enable and install a pimcore bundle:

```yaml
name: Example Profile
files:
    add:
        - app/Resources/views
        - src/AppBundle
        - var/classes
        - var/config/*
        - web/static
        - web/var/assets
db:
    data_files:
        - dump/data.sql
bundles:
    enable:
        Pimcore\Bundle\EcommerceFrameworkBundle\PimcoreEcommerceFrameworkBundle:
            priority: 5
            # environments: ['prod', 'dev']
    install:
        Pimcore\Bundle\EcommerceFrameworkBundle\PimcoreEcommerceFrameworkBundle: ~
```

## Composer Packages

To make an install profile available as composer package, use the type `pimcore-install-profile`.

## Examples

* [Install profiles shipped with Pimcore's default distribution](https://github.com/pimcore/pimcore/tree/master/install-profiles)
* [pimcore/demo-advanced install profile](https://github.com/pimcore/demo-ecommerce) 
