/**
 * @internal
 */
pimcore.registerNS('pimcore.simpleBackendSearch.object.tags.link');

pimcore.simpleBackendSearch.object.tags.link = Class.create({
    openSearchEditor: function (classScope, internalTypeField, linkTypeField, fieldPath) {
        pimcore.helpers.itemselector(false, function (item) {
            if (item) {
                internalTypeField.setValue(item.type);
                linkTypeField.setValue('internal');
                fieldPath.setValue(item.fullpath);
                return true;
            }
        }, {
            type: ["asset", "document", "object"]
        });
    },
});

const backendSearchLink = new pimcore.simpleBackendSearch.object.tags.link();