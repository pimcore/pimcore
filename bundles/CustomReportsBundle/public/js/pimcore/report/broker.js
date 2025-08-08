/**
* This source file is available under the terms of the
* Pimcore Open Core License (POCL)
* Full copyright and license information is available in
* LICENSE.md which is distributed with this source code.
*
*  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.com)
*  @license    Pimcore Open Core License (POCL)
*/

pimcore.registerNS("pimcore.bundle.customreports.broker");
/**
 * @private
 */
pimcore.bundle.customreports.broker = {

    reports: {},
    groups: [
        {
            id: "other",
            name: t("other")
        }
    ],

    groupIds: [],

    addGroup: function (id, name, iconCls) {

        if (!in_array(id, this.groupIds)) {
            this.groups.push({
                id: id,
                name: t(name),
                iconCls: iconCls
            });
        }

        this.groupIds.push(id);
    },

    addReport: function (reportClass, groupId, config) {
        if (!groupId) {
            groupId = "other";
        }

        if (typeof this.reports[groupId] != "object") {
            this.reports[groupId] = [];
        }

        this.reports[groupId].push({
            "class": reportClass,
            config: config
        });
    }
};