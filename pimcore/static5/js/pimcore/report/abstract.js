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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

pimcore.registerNS("pimcore.report.abstract");
pimcore.report.abstract = Class.create({

    initialize: function (reportPanel, type, reference, config) {
        this.reportPanel = reportPanel;
        this.type = type;
        this.reference = reference;
        this.config = config;

        this.addPanel();
    },

    getName: function () {
        return "no name set";
    },

    getIconCls: function () {
        return "";
    },

    matchType: function (type) {
        return false;
    },

    getPanel: function () {
        console.log("You have to implement the getPanel() method.");
    },

    addPanel: function () {
        this.reportPanel.addReport(this.getPanel());
    },

    matchTypeValidate: function (type, validTypes) {
        if (typeof type == "string") {
            return in_array(type, validTypes);
        }
        else if (typeof type == "object") {
            for (var i = 0; i < type.length; i++) {
                if (in_array(type[i], validTypes)) {
                    return true;
                }
            }
        }
        return false;
    }
});