# Implement Your Own Search

## Register Implementation

Pimcore provides the `searchImplementationRegistry` (= facade) where you can register your custom implementation.

### Register a custom implementation
```js
pimcore.registerNS('pimcore.bundle.search');

pimcore.bundle.search = Class.create({
    initialize: function () {
        document.addEventListener(pimcore.events.pimcoreReady, this.pimcoreReady.bind(this));
    },

    pimcoreReady: function () {
        this.searchRegistry = pimcore.globalmanager.get('searchImplementationRegistry');
        this.searchRegistry.registerImplementation(new your.custom.search.implementation());
    }
)};
```

### Check for an Implementation

Thanks to the registry we can check if a custom search implementation has been registered.

```js
pimcore.globalmanager.get('searchImplementationRegistry').hasImplementation();

//or a more readable way
pimcore.helpers.hasSearchImplementation()
```

## Create a custom search implementation

If you want to create your own search implementation you have to provide some predefined methods. 
These methods are: `openItemSelector`, `showQuickSearch`, `hideQuickSearch` and `getObjectRelationInlineSearchRoute`.
- The `openItemSelector` method will be triggered by certain data object fields and editables through the 
  [helper.js](https://github.com/pimcore/admin-ui-classic-bundle/blob/1.x/public/js/pimcore/helpers.js#L822).
- The `showQuickSearch` and `hideQuickSearch` is responsible for managing the quickSearch.
- The `getObjectRelationInlineSearchRoute` has to return the route to `DataObjectController::optionsAction`.

For reference, you can check the implementation in the PimcoreSimpleBackendSearchBundle.
See [service.js](https://github.com/pimcore/pimcore/blob/11.x/bundles/SimpleBackendSearchBundle/public/js/pimcore/element/service.js) 
and [selector.js](https://github.com/pimcore/pimcore/blob/11.x/bundles/SimpleBackendSearchBundle/public/js/pimcore/element/selector/selector.js).

## Using Pimcore without the SimpleBackendSearchBundle

If you use Pimcore without the SimpleBackendSearchBundle you have to consider the following drawbacks.

**SearchButton**

Pimcore will hide all the search buttons from object fields and editables (e.g relations, image, gallery, video, ...).

**Inline Search**

Pimcore provides the option to add a inline search to some relations. This option won't be there. 

**Toolbar Search**

Pimcore will also have no search button in the toolbar. According to that the quickSearch will also be gone.

**GDPR Search**

Pimcore will only have a very basic implementation of the GDPR search.
Especially for searching through data objects it's highly recommended to use the SimpleBackendSearchBundle.
