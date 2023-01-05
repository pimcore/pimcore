pimcore.registerNS('pimcore.bundle.search.element.service');

pimcore.bundle.search.element.service = Class.create({
    openItemSelector: function (multiselect, callback, restrictions, config) {
        new pimcore.bundle.search.element.selector(multiselect, callback, restrictions, config);
    }
});