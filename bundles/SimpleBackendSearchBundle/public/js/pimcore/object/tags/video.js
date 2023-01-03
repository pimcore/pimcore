/**
 * @internal
 */
pimcore.registerNS('pimcore.simpleBackendSearch.object.tags.video');

pimcore.simpleBackendSearch.object.tags.video = Class.create({
    openSearchEditor: function (classScope, fieldPath) {
        pimcore.helpers.itemselector(false, function (item) {
            if (item) {
                fieldPath.setValue(item.fullpath);
                return true;
            }
        }, {
            type: ["asset"],
            subtype: {
                asset: ["video"]
            }
        });
    },

    openSearchEditorPoster: function (classScope, poster) {
        pimcore.helpers.itemselector(false, function (item) {
            if (item) {
                poster.setValue(item.fullpath);
                return true;
            }
        }, {
            type: ["asset"],
            subtype: {
                asset: ["image"]
            }
        });
    }
});

const backendSearchVideo = new pimcore.simpleBackendSearch.object.tags.video();