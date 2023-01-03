/**
 * @internal
 */
pimcore.registerNS('pimcore.simpleBackendSearch.object.tags.image');

pimcore.simpleBackendSearch.object.tags.image = Class.create({
    openSearchEditor: function (classScope) {
        pimcore.helpers.itemselector(false, classScope.addDataFromSelector.bind(this), {
            type: ["asset"],
            subtype: {
                asset: ["image"]
            }
        },
        {
            context: Ext.apply({scope: "objectEditor"}, classScope.getContext())
        });
    },
});

const backendSearchImage = new pimcore.simpleBackendSearch.object.tags.image();