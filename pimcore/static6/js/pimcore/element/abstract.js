/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

pimcore.registerNS("pimcore.element.abstract");
pimcore.element.abstract = Class.create({

    dirty: false,

    addToHistory: true,

    confirmedClose: false,

    // startup / opening functions
    addLoadingPanel : function () {
        var type = pimcore.helpers.getElementTypeByObject(this);
        pimcore.helpers.addTreeNodeLoadingIndicator(type, this.id);
    },

    removeLoadingPanel: function () {
        var type = pimcore.helpers.getElementTypeByObject(this);
        pimcore.helpers.removeTreeNodeLoadingIndicator(type, this.id);
    },


    // CHANGE DETECTOR
    startChangeDetector: function () {
        if(!this.changeDetectorInterval) {
            this.changeDetectorInterval = window.setInterval(this.checkForChanges.bind(this),1000);
        }
    },

    stopChangeDetector: function () {
        window.clearInterval(this.changeDetectorInterval);
        this.changeDetectorInterval = null;
    },

    setupChangeDetector: function () {
        this.resetChanges();
        this.tab.on("deactivate", this.stopChangeDetector.bind(this));
        this.tab.on("activate", this.startChangeDetector.bind(this));
        this.tab.on("destroy", this.stopChangeDetector.bind(this));
    },

    isDirty: function () {
        return this.dirty;
    },

    detectedChange: function () {
        this.tab.setTitle(this.tab.initialConfig.title + " *");
        this.dirty = true;
        this.stopChangeDetector();
    },

    resetChanges: function () {
        this.changeDetectorInitData = {};

        this.tab.setTitle(this.tab.initialConfig.title);
        this.startChangeDetector();
        this.dirty = false;
    },

    checkForChanges: function () {
        if(!this.changeDetectorInitData) {
            this.setupChangeDetector();
        }

        this.ignoreMandatoryFields = true;
        var liveData = this.getSaveData();
        this.ignoreMandatoryFields = false;

        var keys = Object.keys(liveData);

        for (var i=0; i<keys.length; i++) {
            if(this.changeDetectorInitData[keys[i]]) {
                if(this.changeDetectorInitData[keys[i]] != liveData[keys[i]]) {
                    this.detectedChange();
                }
            }
            this.changeDetectorInitData[keys[i]] = liveData[keys[i]];
        }
    },

    setAddToHistory: function(addToHistory) {
        this.addToHistory = addToHistory;
    },

    getAddToHistory: function() {
        return this.addToHistory;
    },

    confirmCloseDirty: function() {
        Ext.MessageBox.confirm(
            t("element_has_unsaved_changes"), t("element_unsaved_changes_message"),
            function (buttonValue) {
                if (buttonValue === "yes") {
                    this.confirmedClose = true;
                    var tabPanel = Ext.getCmp("pimcore_panel_tabs");
                    tabPanel.remove(this.tab);
                }
            }.bind(this)
        );
    }

});