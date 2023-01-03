/**
 * @internal
 */
pimcore.registerNS('pimcore.simpleBackendSearch.object.tags.relation.manyToManyObjectRelation');

pimcore.simpleBackendSearch.object.tags.relation.manyToManyObjectRelation = Class.create({
    openSearchEditor: function (classScope) {
        let allowedClasses;
        if (classScope.fieldConfig.classes != null && classScope.fieldConfig.classes.length > 0) {
            allowedClasses = [];
            for (var i = 0; i < classScope.fieldConfig.classes.length; i++) {
                allowedClasses.push(classScope.fieldConfig.classes[i].classes);
            }
        }

        pimcore.helpers.itemselector(true, classScope.addDataFromSelector.bind(this), {
                type: ["object"],
                subtype: {
                    object: ["object", "variant"]
                },
                specific: {
                    classes: allowedClasses
                }
            },
            {
                context: Ext.apply({scope: "objectEditor"}, classScope.getContext())
            });
    }
});

const backendSearchManyToManyObjectRelation = new pimcore.simpleBackendSearch.object.tags.relation.manyToManyObjectRelation();