/**
 * ## Basic DataView with Draggable mixin.
 *
 *     Ext.Loader.setPath('Ext.ux', '../../../SDK/extjs/examples/ux');
 *
 *     Ext.define('My.cool.View', {
 *         extend: 'Ext.view.View',
 *
 *         mixins: {
 *             draggable: 'Ext.ux.DataView.Draggable'
 *         },
 *
 *         initComponent: function() {
 *             this.mixins.draggable.init(this, {
 *                 ddConfig: {
 *                     ddGroup: 'someGroup'
 *                 }
 *             });
 * 
 *             this.callParent(arguments);
 *         }
 *     });
 *
 *     Ext.onReady(function () {
 *         Ext.create('Ext.data.Store', {
 *             storeId: 'baseball',
 *             fields: ['team', 'established'],
 *             data: [
 *                 { team: 'Atlanta Braves', established: '1871' },
 *                 { team: 'Miami Marlins', established: '1993' },
 *                 { team: 'New York Mets', established: '1962' },
 *                 { team: 'Philadelphia Phillies', established: '1883' },
 *                 { team: 'Washington Nationals', established: '1969' }
 *             ]
 *          });
 *
 *          Ext.create('My.cool.View', {
 *              store: Ext.StoreMgr.get('baseball'),
 *              tpl: [
 *                  '<tpl for=".">', 
 *                      '<p class="team">', 
 *                          'The {team} were founded in {established}.',
 *                      '</p>', 
 *                  '</tpl>'
 *              ],
 *              itemSelector: 'p.team',
 *              renderTo: Ext.getBody()
 *          });
 *      });
 */
Ext.define('Ext.ux.DataView.Draggable', {
    requires: 'Ext.dd.DragZone',

    /**
     * @cfg {String} ghostCls The CSS class added to the outermost element of the created
     * ghost proxy (defaults to 'x-dataview-draggable-ghost')
     */
    ghostCls: 'x-dataview-draggable-ghost',

    /**
     * @cfg {Ext.XTemplate/Array} ghostTpl The template used in the ghost DataView
     */
    ghostTpl: [
        '<tpl for=".">',
            '{title}', // eslint-disable-line indent
        '</tpl>'
    ],

    /**
     * @cfg {Object} ddConfig Config object that is applied to the internally created DragZone
     */

    /**
     * @cfg {String} ghostConfig Config object that is used to configure the internally created
     * DataView
     */

    init: function(dataview, config) {
        /**
         * @property dataview
         * @type Ext.view.View
         * The Ext.view.View instance that this DragZone is attached to
         */
        this.dataview = dataview;

        dataview.on('render', this.onRender, this);

        Ext.apply(this, {
            itemSelector: dataview.itemSelector,
            ghostConfig: {}
        }, config || {});

        Ext.applyIf(this.ghostConfig, {
            itemSelector: 'img',
            cls: this.ghostCls,
            tpl: this.ghostTpl
        });
    },

    /**
     * @private
     * Called when the attached DataView is rendered. Sets up the internal DragZone
     */
    onRender: function() {
        var me = this,
            config = Ext.apply({}, me.ddConfig || {}, {
                dvDraggable: me,
                dataview: me.dataview,
                getDragData: me.getDragData,
                getTreeNode: me.getTreeNode,
                afterRepair: me.afterRepair,
                getRepairXY: me.getRepairXY
            });

        /**
         * @property dragZone
         * @type Ext.dd.DragZone
         * The attached DragZone instane
         */
        me.dragZone = Ext.create('Ext.dd.DragZone', me.dataview.getEl(), config);

        // This is for https://www.w3.org/TR/pointerevents/ platforms.
        // On these platforms, the pointerdown event (single touchstart) is reserved for
        // initiating a scroll gesture. Setting the items draggable defeats that and
        // enables the touchstart event to trigger a drag.
        //
        // Two finger dragging will still scroll on these platforms.
        me.dataview.setItemsDraggable(true);
    },

    getDragData: function(e) {
        var draggable = this.dvDraggable,
            dataview = this.dataview,
            selModel = dataview.getSelectionModel(),
            target = e.getTarget(draggable.itemSelector),
            selected, dragData;

        if (target) {
            // preventDefault is needed here to avoid the browser dragging the image
            // instead of dragging the container like it's supposed to
            e.preventDefault();

            if (!dataview.isSelected(target)) {
                selModel.select(dataview.getRecord(target));
            }

            selected = dataview.getSelectedNodes();
            dragData = {
                copy: true,
                nodes: selected,
                records: selModel.getSelection(),
                item: true
            };

            if (selected.length === 1) {
                dragData.single = true;
                dragData.ddel = target;
            }
            else {
                dragData.multi = true;
                dragData.ddel = draggable.prepareGhost(selModel.getSelection());
            }

            return dragData;
        }

        return false;
    },

    getTreeNode: function() {
        // console.log('test');
    },

    afterRepair: function() {
        var nodes = this.dragData.nodes,
            length = nodes.length,
            i;

        this.dragging = false;

        // FIXME: Ext.fly does not work here for some reason, only frames the last node
        for (i = 0; i < length; i++) {
            Ext.get(nodes[i]).frame('#8db2e3', 1);
        }
    },

    /**
     * @private
     * Returns the x and y co-ordinates that the dragged item should be animated back to if it
     * was dropped on an invalid drop target. If we're dragging more than one item we don't animate
     * back and just allow afterRepair to frame each dropped item.
     */
    getRepairXY: function(e) {
        var repairEl, repairXY;

        if (this.dragData.multi) {
            return false;
        }
        else {
            repairEl = Ext.get(this.dragData.ddel);
            repairXY = repairEl.getXY();

            // take the item's margins and padding into account to make the repair animation
            // line up perfectly
            repairXY[0] += repairEl.getPadding('t') + repairEl.getMargin('t');
            repairXY[1] += repairEl.getPadding('l') + repairEl.getMargin('l');

            return repairXY;
        }
    },

    /**
     * Updates the internal ghost DataView by ensuring it is rendered and contains the correct
     * records
     * @param {Array} records The set of records that is currently selected in the parent DataView
     * @return {HTMLElement} The Ghost DataView's encapsulating HTMLElement.
     */
    prepareGhost: function(records) {
        return this.createGhost(records).getEl().dom;
    },

    /**
     * @private
     * Creates the 'ghost' DataView that follows the mouse cursor during the drag operation.
     * This div is usually a lighter-weight representation of just the nodes that are selected
     * in the parent DataView.
     */
    createGhost: function(records) {
        var me = this,
            store;

        if (me.ghost) {
            (store = me.ghost.store).loadRecords(records);
        }
        else {
            store = Ext.create('Ext.data.Store', {
                model: records[0].self
            });

            store.loadRecords(records);

            me.ghost = Ext.create('Ext.view.View', Ext.apply({
                renderTo: document.createElement('div'),
                store: store
            }, me.ghostConfig));

            me.ghost.container.skipGarbageCollection = me.ghost.el.skipGarbageCollection = true;
        }

        store.clearData();

        return me.ghost;
    },

    destroy: function() {
        var ghost = this.ghost;

        if (ghost) {
            ghost.container.destroy();
            ghost.destroy();
        }

        this.callParent();
    }
});
