/**
 * @private
 */
Ext.define('Ext.layout.component.field.FieldContainer', {
    extend: 'Ext.layout.component.Auto',
    alias: 'layout.fieldcontainer',

    type: 'fieldcontainer',

    waitForOuterHeightInDom: true,
    waitForOuterWidthInDom: true,

    beginLayout: function(ownerContext) {
        var containerEl = this.owner.containerEl;

        this.callParent([ownerContext]);

        // Tell Component.measureAutoDimensions to measure the DOM
        // when containerChildrenSizeDone is true
        ownerContext.hasRawContent = true;
        containerEl.setStyle('width', '');
        containerEl.setStyle('height', '');
        ownerContext.containerElContext = ownerContext.getEl('containerEl');
    },

    calculateOwnerHeightFromContentHeight: function(ownerContext, contentHeight) {
        var h = this.callParent([ownerContext, contentHeight]);

        return h + this.getHeightAdjustment();
    },

    calculateOwnerWidthFromContentWidth: function(ownerContext, contentWidth) {
        var w = this.callParent([ownerContext, contentWidth]);

        return w + this.getWidthAdjustment();
    },

    measureContentHeight: function(ownerContext) {
        // since we are measuring the outer el, we have to wait for whatever is in our
        // container to be flushed to the DOM... especially for things like box layouts
        // that size the innerCt since that is all that will contribute to our size!
        return ownerContext.hasDomProp('containerLayoutDone')
            ? this.callParent([ownerContext])
            : NaN;
    },

    measureContentWidth: function(ownerContext) {
        // see measureContentHeight
        return ownerContext.hasDomProp('containerLayoutDone')
            ? this.callParent([ownerContext])
            : NaN;
    },

    publishInnerHeight: function(ownerContext, height) {
        height -= this.getHeightAdjustment();
        ownerContext.containerElContext.setHeight(height);
    },

    publishInnerWidth: function(ownerContext, width) {
        width -= this.getWidthAdjustment();
        ownerContext.containerElContext.setWidth(width);
    },

    privates: {
        getHeightAdjustment: function() {
            var owner = this.owner,
                h = 0;

            if (owner.labelAlign === 'top' && owner.hasVisibleLabel()) {
                h += owner.labelEl.getHeight();
            }

            if (owner.msgTarget === 'under' && owner.hasActiveError()) {
                h += owner.errorWrapEl.getHeight();
            }

            return h + owner.bodyEl.getPadding('tb');
        },

        getWidthAdjustment: function() {
            var owner = this.owner,
                w = 0;

            if (owner.labelAlign !== 'top' && owner.hasVisibleLabel()) {
                w += (owner.labelWidth + (owner.labelPad || 0));
            }

            if (owner.msgTarget === 'side' && owner.hasActiveError()) {
                w += owner.errorWrapEl.getWidth();
            }

            return w + owner.bodyEl.getPadding('lr');
        }
    }

});
