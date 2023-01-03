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
        const modalVariables = event.detail.modal ?? {};
        const modalMethod = event.detail.method ?? 'openSearchEditor'

        let string = `pimcore.simpleBackendSearch.object.tags.${event.detail.type}`
        console.log('load class: ' +string);

        pimcore.simpleBackendSearch.object.tags[event.detail.type].prototype[modalMethod](classScope, ...Object.values(modalVariables));
    },
});

const backendSearchDialog = new pimcore.simpleBackendSearch.layout.searchModal();