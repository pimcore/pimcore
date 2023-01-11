# Implement your own search

## Register Implementation

Pimcore provides two different registries (=facades) where you can register your custom implementation.
The two registries are: `searchImplementationRegistry` and `quickSearchImplementationRegistry`.

### Register a custom implementation
````js
pimcore.registerNS('pimcore.bundle.search');

pimcore.bundle.search = Class.create({
    initialize: function () {
        document.addEventListener(pimcore.events.pimcoreReady, this.pimcoreReady.bind(this));
    },

    pimcoreReady: function () {
        this.searchRegistry = pimcore.globalmanager.get('searchImplementationRegistry');
        this.searchRegistry.registerImplementation(new your.custom.search.implementation());

        this.quickSearchRegistry = pimcore.globalmanager.get('quickSearchImplementationRegistry');
        this.quickSearchRegistry.registerImplementation(new your.custom.quicksearch.implementation());
    }
)}
````

### Check for a Implementation

Thanks to the registry we can check if a certain implementation has been registered.

```js
pimcore.globalmanager.get('searchImplementationRegistry').hasImplementation();
//or
pimcore.globalmanager.get('quickSearchImplementationRegistry').hasImplementation();
```

## Create a custom search implementation

If you want to create your own search implementation you have to provide the `openItemSelector` method in some way.
The `openItemSelector` method will be triggered by certain data object fields and editables through the [helper.js](https://github.com/pimcore/pimcore/blob/11.x/bundles/AdminBundle/public/js/pimcore/helpers.js#L814)

For reference, you can check the implementation in the PimcoreSimpleBackendSearchBundle.
See [service.js](https://github.com/pimcore/pimcore/blob/11.x/bundles/SimpleBackendSearchBundle/public/js/pimcore/element/service.js) and [selector.js](https://github.com/pimcore/pimcore/blob/11.x/bundles/SimpleBackendSearchBundle/public/js/pimcore/element/selector/selector.js)

## Create a custom quick search implementation

To add you own implementation of the quickSearch you have to provide two main methods as defined in the registry.
These methods are `open` and `close`.

For reference, you can check the implementation in the PimcoreSimpleBackendSearchBundle.
See [quickSearch.js](https://github.com/pimcore/pimcore/blob/11.x/bundles/SimpleBackendSearchBundle/public/js/pimcore/layout/quickSearch.js)

## Using Pimcore without the SimpleBackendSearchBundle

If you use Pimcore without the SimpleBackendSearchBundle you have to consider the following drawbacks.

**SearchButton**<br>
Pimcore will hide all the search buttons from object fields and editables (e.g relations, image, gallery, video, ...)

**Toolbar Search**<br>
Pimcore will also have no search button in the toolbar. According to that the quickSearch will also be gone.

**GDPR Search**<br>
Pimcore will only have a very basic implementation of the GDPR search.
Especially for searching through data objects it's highly recommended to use the SimpleBackendSearchBundle.