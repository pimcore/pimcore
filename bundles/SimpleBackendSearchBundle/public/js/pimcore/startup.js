/**
* This source file is available under the terms of the
* Pimcore Open Core License (POCL)
* Full copyright and license information is available in
* LICENSE.md which is distributed with this source code.
*
*  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.com)
*  @license    Pimcore Open Core License (POCL)
*/

pimcore.registerNS('pimcore.bundle.search');

/**
 * @private
 */
pimcore.bundle.search = Class.create({
    registry: null,

    initialize: function () {
        document.addEventListener(pimcore.events.preRegisterKeyBindings, this.registerKeyBinding.bind(this));
        document.addEventListener(pimcore.events.preMenuBuild, this.preMenuBuild.bind(this));
        document.addEventListener(pimcore.events.pimcoreReady, this.pimcoreReady.bind(this));
    },

    pimcoreReady: function () {
        this.registerSearchService();
    },

    registerKeyBinding: function () {
        pimcore.helpers.keyBindingMapping.quickSearch = function () {
            pimcore.globalmanager.get('searchImplementationRegistry').showQuickSearch();
        }
    },

    registerSearchService: function () {
        this.searchRegistry = pimcore.globalmanager.get('searchImplementationRegistry');

        //register search/selector
        this.searchRegistry.registerImplementation(new pimcore.bundle.search.element.service());
    },

    preMenuBuild: function (event) {
        new pimcore.bundle.search.layout.toolbar(event.detail.menu); //TODO: check if that works
    }
});

const searchBundle = new pimcore.bundle.search();