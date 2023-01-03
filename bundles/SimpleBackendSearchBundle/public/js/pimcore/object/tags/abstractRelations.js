/**
 * @internal
 */
pimcore.registerNS('pimcore.simpleBackendSearch.object.tags.abstractRelations');

pimcore.simpleBackendSearch.object.tags.abstractRelations = Class.create({
    openSearchEditor: function (classScope) {
        let allowedTypes = [];
        let allowedSpecific = {};
        let allowedSubtypes = {};
        let i;

        if (classScope.fieldConfig.objectsAllowed) {
            allowedTypes.push("object");
            allowedSubtypes.object = [];
            if (classScope.fieldConfig.classes != null && classScope.fieldConfig.classes.length > 0) {
                allowedSpecific.classes = [];
                allowedSubtypes.object.push("object", "variant");
                for (i = 0; i < classScope.fieldConfig.classes.length; i++) {
                    allowedSpecific.classes.push(classScope.fieldConfig.classes[i].classes);

                }
            }
            if(classScope.dataObjectFolderAllowed) {
                allowedSubtypes.object.push("folder");
            }

            if(allowedSubtypes.length == 0) {
                allowedSubtypes.object = ["object", "folder", "variant"];
            }
        }
        if (classScope.fieldConfig.assetsAllowed) {
            allowedTypes.push("asset");
            if (classScope.fieldConfig.assetTypes != null && classScope.fieldConfig.assetTypes.length > 0) {
                allowedSubtypes.asset = [];
                for (i = 0; i < classScope.fieldConfig.assetTypes.length; i++) {
                    allowedSubtypes.asset.push(classScope.fieldConfig.assetTypes[i].assetTypes);
                }
            }
        }
        if (classScope.fieldConfig.documentsAllowed) {
            allowedTypes.push("document");
            if (classScope.fieldConfig.documentTypes != null && classScope.fieldConfig.documentTypes.length > 0) {
                allowedSubtypes.document = [];
                for (i = 0; i < classScope.fieldConfig.documentTypes.length; i++) {
                    allowedSubtypes.document.push(classScope.fieldConfig.documentTypes[i].documentTypes);
                }
            }
        }

        pimcore.helpers.itemselector(false, classScope.addDataFromSelector.bind(classScope), {
            type: allowedTypes,
            subtype: allowedSubtypes,
            specific: allowedSpecific
        }, {
            context: Ext.apply({scope: "objectEditor"}, classScope.getContext())
        });
    }
});