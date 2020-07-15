/**
 * This mixin defines certain config options, properties, and APIs to be used
 * by Components that implement accessible traits according to WAI-ARIA 1.0 specification.
 *
 * @private
 */
Ext.define('Ext.mixin.Accessible', {
    extend: 'Ext.Mixin',

    mixinConfig: {
        id: 'accessible'
    },

    /**
     * @cfg {String} [ariaLabel] ARIA label for this Component. It is best to use
     * {@link #ariaLabelledBy} option instead, because screen readers prefer
     * `aria-labelledby` attribute to `aria-label`. {@link #ariaLabel} and
     * {@link #ariaLabelledBy} config options are mutually exclusive.
     */

    /**
     * @cfg {String} [ariaLabelledBy] DOM selector for a child element that is to be used
     * as label for this Component, set in `aria-labelledby` attribute.
     * If the selector is by `#id`, the label element can be any existing element,
     * not necessarily a child of the main Component element.
     *
     * {@link #ariaLabelledBy} and {@link #ariaLabel} config options are
     * mutually exclusive, and `ariaLabelledBy` has the higher precedence.
     */

    /**
     * @cfg {String} [ariaDescribedBy] DOM selector for a child element that is to be used
     * as description for this Component, set in `aria-describedby` attribute.
     * The selector works the same way as {@link #ariaLabelledBy}.
     */

    config: {
        /**
         * @cfg {Object} ariaAttributes An object containing ARIA attributes to be set
         * on this Component's ARIA element. Use this to set the attributes that cannot be
         * determined by the Component's state, such as `aria-live`, `aria-flowto`, etc.
         *
         * **Note** that this config is only meaningful at the Component rendering time,
         * and setting it after that will do nothing.
         */
        ariaAttributes: {
            $value: null,
            lazy: true
        }
    },

    /**
     * @property {String} [ariaRole] ARIA role for this Component, defaults to no role.
     * With no role, no other ARIA attributes are set.
     *
     * @readonly
     */

    /**
     * @property {Object} [ariaRenderAttributes] **Instance specific** ARIA attributes
     * to render into Component's ariaEl. This object is only used during rendering,
     * and is discarded afterwards.
     *
     * @private
     */

    /**
     * @property {String} [ariaEl='el'] The name of the Component property that holds
     * a reference to the Element that serves as that Component's ARIA element.
     * This property will be replaced with the actual Element reference after rendering.
     *
     * Most of the simple Components will have their main element as ariaEl.
     *
     * @private
     * @readonly
     * @since 6.0.0
     */
    ariaEl: 'el',

    privates: {
        /**
         * Find component(s) that label or describe this component,
         * and return the id(s) of their ariaEl elements.
         *
         * @param {Function/String/String[]} [reference] Component reference,
         * or array of component references, or a function that should return
         * the proper attribute string. The function will be called in the
         * context of the labelled component.
         *
         * @return {Ext.Element} Element id string, or null
         * @private
         */
        getAriaLabelEl: function(reference) {
            var ids = [],
                refHolder, i, len, cmp;

            if (reference) {
                if (Ext.isFunction(reference)) {
                    return reference.call(this);
                }
                else {
                    if (!Ext.isArray(reference)) {
                        reference = [reference];
                    }

                    refHolder = this.lookupReferenceHolder();

                    if (refHolder) {
                        for (i = 0, len = reference.length; i < len; i++) {
                            cmp = refHolder.lookupReference(reference[i]);

                            if (cmp) {
                                ids.push(cmp.ariaEl.id);
                            }
                        }
                    }
                }
            }

            return ids.length ? ids.join(' ') : null;
        }
    }
});
