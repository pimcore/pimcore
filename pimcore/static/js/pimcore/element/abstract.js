/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

pimcore.registerNS("pimcore.element.abstract");
pimcore.element.abstract = Class.create({

    
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
    
    detectedChange: function () {
        this.tab.setTitle(this.tab.initialConfig.title + " *");
        this.stopChangeDetector();
    },
    
    resetChanges: function () {
        this.changeDetectorInitData = {};
        
        this.tab.setTitle(this.tab.initialConfig.title);
        this.startChangeDetector();
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
    }
});