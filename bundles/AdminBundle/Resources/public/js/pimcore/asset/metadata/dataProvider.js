/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

pimcore.registerNS("pimcore.asset.metadata.dataProvider");
pimcore.asset.metadata.dataProvider = Class.create({

    initialize: function (config) {
        this.config = config || {};
        this.store = this.config.data || {};
        this.changeListeners = {};
        this.globalChangeListeners = {};
    },

    setStore: function(store) {
        this.store = store;
    },

    sanitizeItem: function (item) {
        var sItem = {
            data: item.data,
            language: item.language,
            name: item.name,
            type: item.type,
            config: item.config
        };
        return sItem;
    },

    buildKeyFromItem: function (item) {
        let language = item.language ? item.language : "";
        let key = item.name + "~" + language;

        return key;
    },

    getItemCount: function () {
        return Object.keys(this.store).length;
    },

    getData: function () {
        return this.store;
    },

    getDataAsArray: function () {
        var result = [];
        var records = Object.values(this.store);
        for (let i = 0; i < records.length; i++) {
            result.push(Ext.clone(records[i]));
        }
        return result;
    },

    /**
     * Register a change listener
     * @param key metadata key
     * @param targetId unique target identifier
     * @param callback callback function
     */
    registerChangeListener: function (key, targetId, callback) {
        if (!targetId) {
            throw "target id missing";
        }
        this.changeListeners[key] = this.changeListeners[key] || {};
        this.changeListeners[key][targetId] = {
            targetId: targetId,
            callback: callback
        };
    },

    unregisterChangeListener: function (key, targetId) {
        let list = this.changeListeners[key];
        delete list[targetId];

        if (Object.keys(this.changeListeners[key]).length == 0) {
            delete this.changeListeners[key];
        }
    },

    registerGlobalChangeListener: function (targetId, callback) {
        this.globalChangeListeners[targetId] = callback;
    },

    getSubmitValues: function () {
        var values = this.getDataAsArray();
        return values;
    },

    getItemData: function (prefix, name, language) {
        var item = this.getItem(prefix, name, language);
        if (item) {
            return item.data;
        }
    },

    getItemByKey: function(key) {
        let value = this.store[key];
        value = Ext.clone(value);
        return value;
    },

    getItem: function (prefix, name, language) {
        let key = this.buildKey(prefix, name, language);
        let value = this.getItemByKey(key);
        return value;
    },

    buildKey: function (prefix, name, language) {
        language = language || "";
        return prefix + "." + name + "~" + language;
    },

    update: function (item, newValue, originator) {
        if (this.notificationsDisabled) {
            return;
        }

        item = this.sanitizeItem(item);
        item.data = newValue;
        let key = this.buildKeyFromItem(item);
        this.store[key] = item;
        this.fireEvent("update", item, originator);
    },

    remove: function (item, originator) {
        if (this.notificationsDisabled) {
            return;
        }

        let key = this.buildKeyFromItem(item);
        delete this.store[key];
        item.data = null;
        this.fireEvent("remove", item, originator);
    },

    fireEvent: function (eventType, item, originator) {
        this.notificationsDisabled = true;

        try {
            let key = this.buildKeyFromItem(item);

            if (this.changeListeners[key]) {
                for (let targetId in this.changeListeners[key]) {
                    if (this.changeListeners[key].hasOwnProperty(targetId)) {
                        let listenerCfg = this.changeListeners[key][targetId];
                        let callback = listenerCfg["callback"];
                        callback(eventType, item.name, item.language, item.data, item.type, originator);
                    }
                }

            }
            for (let targetId in this.globalChangeListeners) {
                let callback = this.globalChangeListeners[targetId];
                callback(eventType, item.name, item.language, item.data, item.type, item.config, originator);
            }
        } catch (e) {
            console.log(e);
        }
        this.notificationsDisabled = false;
    }
});