/**
 * @internal
 */
pimcore.registerNS('pimcore.simpleBackendSearch.layout.searchModal');

pimcore.simpleBackendSearch.layout.searchModal = Class.create({
    initialize: function () {
        document.addEventListener(pimcore.events.onBackendSearchOpenDialog, this.openSearchEditorDispatcher.bind(this));
    },

    openSearchEditorDispatcher: function (event) {
        const classScope = event.detail.class ?? {};

        let string = `pimcore.simpleBackendSearch.object.tags.${classScope.fieldConfig.fieldtype}`
        console.log('load class: ' +string);

        pimcore.simpleBackendSearch.object.tags[classScope.fieldConfig.fieldtype].prototype.openSearchEditor(classScope);
    },
});

const backendSearchDialog = new pimcore.simpleBackendSearch.layout.searchModal();