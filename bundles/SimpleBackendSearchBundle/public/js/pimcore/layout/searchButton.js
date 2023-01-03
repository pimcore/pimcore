/**
 * @internal
 */
pimcore.registerNS('pimcore.simpleBackendSearch.layout.searchButton');

pimcore.simpleBackendSearch.layout.searchButton = Class.create({
    initialize: function () {
        document.addEventListener(pimcore.events.onBackendSearchButtonInit, this.createSearchButton.bind(this));
    },

    createSearchButton: function (event) {
        let items = event.detail.items;
        const modalVariables = event.detail.modal ?? {};

        if(items !== undefined) {
            items.push({
                xtype: "button",
                iconCls: "pimcore_icon_search",
                style: "margin-left: 5px",
                handler: function () {
                    document.dispatchEvent(new CustomEvent(pimcore.events.onBackendSearchOpenDialog, {
                        detail: {
                            class: event.detail.class,
                            type: event.detail.type,
                            modal: {
                                ...modalVariables
                            }
                        }
                    }));
                }
            });
        }
    }
});

const backendSearchButton = new pimcore.simpleBackendSearch.layout.searchButton();