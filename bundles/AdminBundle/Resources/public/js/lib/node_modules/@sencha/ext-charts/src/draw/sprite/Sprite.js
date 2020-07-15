/**
 * A sprite is a basic primitive from the charts package which represents a graphical 
 * object that can be drawn. Sprites are used extensively in the charts package to 
 * create the visual elements of each chart.  You can also create a desired image by 
 * adding one or more sprites to a {@link Ext.draw.Container draw container}.
 * 
 * The Sprite class itself is an abstract class and is not meant to be used directly.  
 * There are many different kinds of sprites available in the charts package that extend 
 * Ext.draw.sprite.Sprite. Each sprite type has various attributes that define how that 
 * sprite should look. For example, this is a {@link Ext.draw.sprite.Rect rect} sprite:
 * 
 *     @example
 *     Ext.create({
 *         xtype: 'draw', 
 *         renderTo: document.body,
 *         width: 400,
 *         height: 400,
 *         sprites: [{
 *             type: 'rect',
 *             x: 50,
 *             y: 50,
 *             width: 100,
 *             height: 100,
 *             fillStyle: '#1F6D91'
 *         }]
 *     });
 * 
 * By default, sprites are added to the default 'main' {@link Ext.draw.Surface surface} 
 * of the draw container.  However, sprites may also be configured with a reference to a 
 * specific Ext.draw.Surface when set in the draw container's 
 * {@link Ext.draw.Container#cfg-sprites sprites} config.  Specifying a surface 
 * other than 'main' will create a surface by that name if it does not already exist.
 * 
 *     @example
 *     Ext.create({
 *         xtype: 'draw', 
 *         renderTo: document.body,
 *         width: 400,
 *         height: 400,
 *         sprites: [{
 *             type: 'rect',
 *             surface: 'anim',  // a surface with id "anim" will be created automatically
 *             x: 50,
 *             y: 50,
 *             width: 100,
 *             height: 100,
 *             fillStyle: '#1F6D91'
 *         }]
 *     });
 * 
 * The ability to have multiple surfaces is useful for performance (and battery life) 
 * reasons. Because changes to sprite attributes cause the whole surface (and all 
 * sprites in it) to re-render, it makes sense to group sprites by surface, so changes 
 * to one group of sprites will only trigger the surface they are in to re-render.
 * 
 * You can add a sprite to an existing drawing by adding the sprite to a draw surface.  
 * 
 *     @example
 *     var drawCt = Ext.create({
 *         xtype: 'draw',
 *         renderTo: document.body,
 *         width: 400,
 *         height: 400
 *     });
 *     
 *     // If the surface name is not specified then 'main' will be used
 *     var surface = drawCt.getSurface();
 *     
 *     surface.add({
 *         type: 'rect',
 *         x: 50,
 *         y: 50,
 *         width: 100,
 *         height: 100,
 *         fillStyle: '#1F6D91'
 *     });
 *     
 *     surface.renderFrame();
 * 
 * **Note:** Changes to the sprites on a surface will be not be reflected in the DOM 
 * until you call the surface's {@link Ext.draw.Surface#method-renderFrame renderFrame} 
 * method.  This must be done after adding, removing, or modifying sprites in order to 
 * see the changes on-screen.
 * 
 * For information on configuring a sprite with an initial transformation see 
 * {@link #scaling}, {@link #rotation}, and {@link #translation}.
 * 
 * For information on applying a transformation to an existing sprite see the 
 * Ext.draw.Matrix class.
 */
Ext.define('Ext.draw.sprite.Sprite', {
    alias: 'sprite.sprite',

    mixins: {
        observable: 'Ext.mixin.Observable'
    },

    requires: [
        'Ext.draw.Draw',
        'Ext.draw.gradient.Gradient',
        'Ext.draw.sprite.AttributeDefinition',
        'Ext.draw.modifier.Target',
        'Ext.draw.modifier.Animation',
        'Ext.draw.modifier.Highlight'
    ],

    isSprite: true,

    $configStrict: false,

    statics: {
        //<debug>
        /* eslint-disable max-len */
        /**
         * Debug rendering options:
         *
         * debug: {
         *     bbox: true, // renders the bounding box of the sprite
         *     xray: true  // renders control points of the path (for Ext.draw.sprite.Path and descendants only)
         * }
         *
         */
        debug: false,
        /* eslint-enable max-len */
        //</debug>

        defaultHitTestOptions: {
            fill: true,
            stroke: true
        }
    },

    inheritableStatics: {
        def: {
            processors: {
                //<debug>
                debug: 'default',
                //</debug>

                /**
                 * @cfg {String} [strokeStyle="none"] The color of the stroke (a CSS color value).
                 */
                strokeStyle: "color",

                /**
                 * @cfg {String} [fillStyle="none"] The color of the shape (a CSS color value).
                 */
                fillStyle: "color",

                /**
                 * @cfg {Number} [strokeOpacity=1] The opacity of the stroke. Limited from 0 to 1.
                 */
                strokeOpacity: "limited01",

                /**
                 * @cfg {Number} [fillOpacity=1] The opacity of the fill. Limited from 0 to 1.
                 */
                fillOpacity: "limited01",

                /**
                 * @cfg {Number} [lineWidth=1] The width of the line stroke.
                 */
                lineWidth: "number",

                /**
                 * @cfg {String} [lineCap="butt"] The style of the line caps.
                 */
                lineCap: "enums(butt,round,square)",

                /**
                 * @cfg {String} [lineJoin="miter"] The style of the line join.
                 */
                lineJoin: "enums(round,bevel,miter)",

                /**
                 * @cfg {Array} [lineDash=[]]
                 * An even number of non-negative numbers specifying a dash/space sequence.
                 * Note that while this is supported in IE8 (VML engine), the behavior is
                 * different from Canvas and SVG. Please refer to this document for details:
                 * http://msdn.microsoft.com/en-us/library/bb264085(v=vs.85).aspx
                 * Although IE9 and IE10 have Canvas support, the 'lineDash'
                 * attribute is not supported in those browsers.
                 */
                lineDash: "data",

                /**
                 * @cfg {Number} [lineDashOffset=0]
                 * A number specifying how far into the line dash sequence drawing commences.
                 */
                lineDashOffset: "number",

                /**
                 * @cfg {Number} [miterLimit=10]
                 * Sets the distance between the inner corner and the outer corner
                 * where two lines meet.
                 */
                miterLimit: "number",

                /**
                 * @cfg {String} [shadowColor="none"] The color of the shadow (a CSS color value).
                 */
                shadowColor: "color",

                /**
                 * @cfg {Number} [shadowOffsetX=0] The offset of the sprite's shadow on the x-axis.
                 */
                shadowOffsetX: "number",

                /**
                 * @cfg {Number} [shadowOffsetY=0] The offset of the sprite's shadow on the y-axis.
                 */
                shadowOffsetY: "number",

                /**
                 * @cfg {Number} [shadowBlur=0] The amount blur used on the shadow.
                 */
                shadowBlur: "number",

                /**
                 * @cfg {Number} [globalAlpha=1] The opacity of the sprite. Limited from 0 to 1.
                 */
                globalAlpha: "limited01",

                /**
                 * @cfg {String} [globalCompositeOperation=source-over]
                 * Indicates how source images are drawn onto a destination image.
                 * globalCompositeOperation attribute is not supported by the SVG and VML
                 * (excanvas) engines.
                 */
                // eslint-disable-next-line max-len
                globalCompositeOperation: "enums(source-over,destination-over,source-in,destination-in,source-out,destination-out,source-atop,destination-atop,lighter,xor,copy)",

                /**
                 * @cfg {Boolean} [hidden=false] Determines whether or not the sprite is hidden.
                 */
                hidden: "bool",

                /**
                 * @cfg {Boolean} [transformFillStroke=false]
                 * Determines whether the fill and stroke are affected by sprite transformations.
                 */
                transformFillStroke: "bool",

                /**
                 * @cfg {Number} [zIndex=0]
                 * The stacking order of the sprite.
                 */
                zIndex: "number",

                /**
                 * @cfg {Number} [translationX=0]
                 * The translation, position offset, of the sprite on the x-axis.
                 * 
                 * **Note:** Transform configs are *always* performed in the following 
                 * order:
                 * 
                 *  1. Scaling
                 *  2. Rotation
                 *  3. Translation
                 * 
                 * See also: {@link #translation} and {@link #translationY}
                 */
                translationX: "number",

                /**
                 * @cfg {Number} [translationY=0]
                 * The translation, position offset, of the sprite on the y-axis.
                 * 
                 * **Note:** Transform configs are *always* performed in the following 
                 * order:
                 * 
                 *  1. Scaling
                 *  2. Rotation
                 *  3. Translation
                 * 
                 * See also: {@link #translation} and {@link #translationX}
                 */
                translationY: "number",

                /**
                 * @cfg {Number} [rotationRads=0]
                 * The angle of rotation of the sprite in radians.
                 * 
                 * **Note:** Transform configs are *always* performed in the following 
                 * order:
                 * 
                 *  1. Scaling
                 *  2. Rotation
                 *  3. Translation
                 * 
                 * See also: {@link #rotation}, {@link #rotationCenterX}, and 
                 * {@link #rotationCenterY}
                 */
                rotationRads: "number",

                /**
                 * @cfg {Number} [rotationCenterX=null]
                 * The central coordinate of the sprite's scale operation on the x-axis.  
                 * Unless explicitly set, will default to the calculated center of the 
                 * sprite along the x-axis.
                 * 
                 * **Note:** Transform configs are *always* performed in the following 
                 * order:
                 * 
                 *  1. Scaling
                 *  2. Rotation
                 *  3. Translation
                 * 
                 * See also: {@link #rotation}, {@link #rotationRads}, and 
                 * {@link #rotationCenterY}
                 */
                rotationCenterX: "number",

                /**
                 * @cfg {Number} [rotationCenterY=null]
                 * The central coordinate of the sprite's rotate operation on the y-axis.
                 * Unless explicitly set, will default to the calculated center of the 
                 * sprite along the y-axis.
                 * 
                 * **Note:** Transform configs are *always* performed in the following 
                 * order:
                 * 
                 *  1. Scaling
                 *  2. Rotation
                 *  3. Translation
                 * 
                 * See also: {@link #rotation}, {@link #rotationRads}, and 
                 * {@link #rotationCenterX}
                 */
                rotationCenterY: "number",

                /**
                 * @cfg {Number} [scalingX=1] The scaling of the sprite on the x-axis.
                 * The number value represents a percentage by which to scale the 
                 * sprite.  **1** is equal to 100%, **2** would be 200%, etc.
                 * 
                 * **Note:** Transform configs are *always* performed in the following 
                 * order:
                 * 
                 *  1. Scaling
                 *  2. Rotation
                 *  3. Translation
                 * 
                 * See also: {@link #scaling}, {@link #scalingY}, 
                 * {@link #scalingCenterX}, and {@link #scalingCenterY}
                 */
                scalingX: "number",

                /**
                 * @cfg {Number} [scalingY=1] The scaling of the sprite on the y-axis.  
                 * The number value represents a percentage by which to scale the 
                 * sprite.  **1** is equal to 100%, **2** would be 200%, etc.
                 * 
                 * **Note:** Transform configs are *always* performed in the following 
                 * order:
                 * 
                 *  1. Scaling
                 *  2. Rotation
                 *  3. Translation
                 * 
                 * See also: {@link #scaling}, {@link #scalingX}, 
                 * {@link #scalingCenterX}, and {@link #scalingCenterY}
                 */
                scalingY: "number",

                /**
                 * @cfg {Number} [scalingCenterX=null]
                 * The central coordinate of the sprite's scale operation on the x-axis.
                 * 
                 * **Note:** Transform configs are *always* performed in the following 
                 * order:
                 * 
                 *  1. Scaling
                 *  2. Rotation
                 *  3. Translation
                 * 
                 * See also: {@link #scaling}, {@link #scalingX}, 
                 * {@link #scalingY}, and {@link #scalingCenterY}
                 */
                scalingCenterX: "number",

                /**
                 * @cfg {Number} [scalingCenterY=null]
                 * The central coordinate of the sprite's scale operation on the y-axis.
                 * 
                 * **Note:** Transform configs are *always* performed in the following 
                 * order:
                 * 
                 *  1. Scaling
                 *  2. Rotation
                 *  3. Translation
                 * 
                 * See also: {@link #scaling}, {@link #scalingX}, 
                 * {@link #scalingY}, and {@link #scalingCenterX}
                 */
                scalingCenterY: "number",

                constrainGradients: "bool"

                /**
                 * @cfg {Number/Object} rotation
                 * Applies an initial angle of rotation to the sprite.  May be a number 
                 * specifying the rotation in degrees.  Or may be a config object using 
                 * the below config options.
                 * 
                 * **Note:** Rotation config options will be overridden by values set on 
                 * the {@link #rotationRads}, {@link #rotationCenterX}, and 
                 * {@link #rotationCenterY} configs.  
                 * 
                 *     Ext.create({
                 *         xtype: 'draw',
                 *         renderTo: Ext.getBody(),
                 *         width: 600,
                 *         height: 400,
                 *         sprites: [{
                 *             type: 'rect',
                 *             x: 50,
                 *             y: 50,
                 *             width: 100,
                 *             height: 100,
                 *             fillStyle: '#1F6D91',
                 *             //rotation: 45
                 *             rotation: {
                 *                 degrees: 45,
                 *                 //rads: Math.PI / 4,
                 *                 //centerX: 50,
                 *                 //centerY: 50
                 *             }
                 *         }]
                 *     });
                 * 
                 * **Note:** Transform configs are *always* performed in the following 
                 * order:
                 * 
                 *  1. Scaling
                 *  2. Rotation
                 *  3. Translation
                 * 
                 * @cfg {Number} rotation.rads
                 * The angle in radians to rotate the sprite
                 * 
                 * @cfg {Number} rotation.degrees
                 * The angle in degrees to rotate the sprite (is ignored if rads or 
                 * {@link #rotationRads} is set
                 * 
                 * @cfg {Number} rotation.centerX
                 * The central coordinate of the sprite's rotation on the x-axis.  
                 * Unless explicitly set, will default to the calculated center of the 
                 * sprite along the x-axis.
                 * 
                 * @cfg {Number} rotation.centerY
                 * The central coordinate of the sprite's rotation on the y-axis.  
                 * Unless explicitly set, will default to the calculated center of the 
                 * sprite along the y-axis.
                 */

                /**
                 * @cfg {Number/Object} scaling
                 * Applies initial scaling to the sprite.  May be a number specifying 
                 * the amount to scale both the x and y-axis.  The number value 
                 * represents a percentage by which to scale the sprite.  **1** is equal 
                 * to 100%, **2** would be 200%, etc.  Or may be a config object using 
                 * the below config options.
                 * 
                 * **Note:** Scaling config options will be overridden by values set on 
                 * the {@link #scalingX}, {@link #scalingY}, {@link #scalingCenterX}, 
                 * and {@link #scalingCenterY} configs.
                 * 
                 *     Ext.create({
                 *         xtype: 'draw',
                 *         renderTo: Ext.getBody(),
                 *         width: 600,
                 *         height: 400,
                 *         sprites: [{
                 *             type: 'rect',
                 *             x: 50,
                 *             y: 50,
                 *             width: 100,
                 *             height: 100,
                 *             fillStyle: '#1F6D91',
                 *             //scaling: 2,
                 *             scaling: {
                 *                 x: 2,
	             *                 y: 2
                 *                 //centerX: 100,
                 *                 //centerY: 100
                 *             }
                 *         }]
                 *     });
                 * 
                 * **Note:** Transform configs are *always* performed in the following 
                 * order:
                 * 
                 *  1. Scaling
                 *  2. Rotation
                 *  3. Translation
                 * 
                 * @cfg {Number} scaling.x
                 * The amount by which to scale the sprite along the x-axis.  The number 
                 * value represents a percentage by which to scale the sprite.  **1** is 
                 * equal to 100%, **2** would be 200%, etc.
                 * 
                 * @cfg {Number} scaling.y
                 * The amount by which to scale the sprite along the y-axis.  The number 
                 * value represents a percentage by which to scale the sprite.  **1** is 
                 * equal to 100%, **2** would be 200%, etc.
                 * 
                 * @cfg scaling.centerX
                 * The central coordinate of the sprite's scaling on the x-axis.  Unless 
                 * explicitly set, will default to the calculated center of the sprite 
                 * along the x-axis.
                 * 
                 * @cfg {Number} scaling.centerY
                 * The central coordinate of the sprite's scaling on the y-axis.  Unless 
                 * explicitly set, will default to the calculated center of the sprite 
                 * along the y-axis.
                 */

                /**
                 * @cfg {Object} translation
                 * Applies an initial translation, adjustment in x/y positioning, to the 
                 * sprite.
                 * 
                 * **Note:** Translation config options will be overridden by values set 
                 * on the {@link #translationX} and {@link #translationY} configs.
                 * 
                 *     Ext.create({
                 *         xtype: 'draw',
                 *         renderTo: Ext.getBody(),
                 *         width: 600,
                 *         height: 400,
                 *             sprites: [{
                 *             type: 'rect',
                 *             x: 50,
                 *             y: 50,
                 *             width: 100,
                 *             height: 100,
                 *             fillStyle: '#1F6D91',
                 *             translation: {
                 *                 x: 50,
                 *                 y: 50
                 *             }
                 *         }]
                 *     });
                 * 
                 * **Note:** Transform configs are *always* performed in the following 
                 * order:
                 * 
                 *  1. Scaling
                 *  2. Rotation
                 *  3. Translation
                 * 
                 * @cfg {Number} translation.x
                 * The amount to translate the sprite along the x-axis.
                 * 
                 * @cfg {Number} translation.y
                 * The amount to translate the sprite along the y-axis.
                 */
            },

            aliases: {
                "stroke": "strokeStyle",
                "fill": "fillStyle",
                "color": "fillStyle",
                "stroke-width": "lineWidth",
                "stroke-linecap": "lineCap",
                "stroke-linejoin": "lineJoin",
                "stroke-miterlimit": "miterLimit",
                "text-anchor": "textAlign",
                "opacity": "globalAlpha",

                translateX: "translationX",
                translateY: "translationY",
                rotateRads: "rotationRads",
                rotateCenterX: "rotationCenterX",
                rotateCenterY: "rotationCenterY",
                scaleX: "scalingX",
                scaleY: "scalingY",
                scaleCenterX: "scalingCenterX",
                scaleCenterY: "scalingCenterY"
            },

            defaults: {
                hidden: false,
                zIndex: 0,

                strokeStyle: "none",
                fillStyle: "none",
                lineWidth: 1,
                lineDash: [],
                lineDashOffset: 0,
                lineCap: "butt",
                lineJoin: "miter",
                miterLimit: 10,

                shadowColor: "none",
                shadowOffsetX: 0,
                shadowOffsetY: 0,
                shadowBlur: 0,

                globalAlpha: 1,
                strokeOpacity: 1,
                fillOpacity: 1,
                transformFillStroke: false,

                translationX: 0,
                translationY: 0,
                rotationRads: 0,
                rotationCenterX: null,
                rotationCenterY: null,
                scalingX: 1,
                scalingY: 1,
                scalingCenterX: null,
                scalingCenterY: null,

                constrainGradients: false
            },

            triggers: {
                zIndex: "zIndex",

                globalAlpha: "canvas",
                globalCompositeOperation: "canvas",

                transformFillStroke: "canvas",
                strokeStyle: "canvas",
                fillStyle: "canvas",
                strokeOpacity: "canvas",
                fillOpacity: "canvas",

                lineWidth: "canvas",
                lineCap: "canvas",
                lineJoin: "canvas",
                lineDash: "canvas",
                lineDashOffset: "canvas",
                miterLimit: "canvas",

                shadowColor: "canvas",
                shadowOffsetX: "canvas",
                shadowOffsetY: "canvas",
                shadowBlur: "canvas",

                translationX: "transform",
                translationY: "transform",
                rotationRads: "transform",
                rotationCenterX: "transform",
                rotationCenterY: "transform",
                scalingX: "transform",
                scalingY: "transform",
                scalingCenterX: "transform",
                scalingCenterY: "transform",

                constrainGradients: "canvas"
            },

            updaters: {
                // 'bbox' updater is meant to be called by subclasses when changes
                // to attributes are expected to result in a change in sprite's dimensions.
                bbox: 'bboxUpdater',

                zIndex: function(attr) {
                    attr.dirtyZIndex = true;
                },

                transform: function(attr) {
                    attr.dirtyTransform = true;
                    attr.bbox.transform.dirty = true;
                }
            }
        }
    },

    /**
     * @property {Object} attr
     * The visual attributes of the sprite, e.g. strokeStyle, fillStyle, lineWidth...
     */

    /**
     * @cfg {Ext.draw.modifier.Animation} animation
     * @accessor
     */

    config: {
        /**
         * @private
         * @cfg {Ext.draw.Surface/Ext.draw.sprite.Instancing/Ext.draw.sprite.Composite} parent
         * The immediate parent of the sprite. Not necessarily a surface.
         */
        parent: null,
        /**
         * @private
         * @cfg {Ext.draw.Surface} surface
         * The surface that this sprite is rendered into.
         * This config is not meant to be used directly.
         * Please use the {@link Ext.draw.Surface#add} method instead.
         */
        surface: null
    },

    onClassExtended: function(subClass, data) {
        // The `def` here is no longer a config, but an instance
        // of the AttributeDefinition class created with that config,
        // which can now be retrieved from `initialConfig`.
        var superclassCfg = subClass.superclass.self.def.initialConfig,
            ownCfg = data.inheritableStatics && data.inheritableStatics.def,
            cfg;

        // If sprite defines attributes of its own, merge that with those of its parent.
        if (ownCfg) {
            cfg = Ext.Object.merge({}, superclassCfg, ownCfg);
            subClass.def = new Ext.draw.sprite.AttributeDefinition(cfg);
            delete data.inheritableStatics.def;
        }
        else {
            subClass.def = new Ext.draw.sprite.AttributeDefinition(superclassCfg);
        }

        subClass.def.spriteClass = subClass;
    },

    constructor: function(config) {
        //<debug>
        if (Ext.getClassName(this) === 'Ext.draw.sprite.Sprite') {
            throw 'Ext.draw.sprite.Sprite is an abstract class';
        }
        //</debug>

        // eslint-disable-next-line vars-on-top
        var me = this,
            attributeDefinition = me.self.def,
            // It is important to get defaults (make sure
            // 'defaults' config applier of the AttributeDefinition is called,
            // since it is initialized lazily) before the attributes
            // are initialized ('initializeAttributes' call).
            defaults = attributeDefinition.getDefaults(),
            processors = attributeDefinition.getProcessors(),
            modifiers, name;

        config = Ext.isObject(config) ? config : {};

        me.id = config.id || Ext.id(null, 'ext-sprite-');
        me.attr = {};
        // Observable's constructor also calls the initConfig for us.
        me.mixins.observable.constructor.apply(me, arguments);

        modifiers = Ext.Array.from(config.modifiers, true);
        me.createModifiers(modifiers);
        me.initializeAttributes();
        me.setAttributes(defaults, true);

        //<debug>
        for (name in config) {
            if (name in processors && me['get' + name.charAt(0).toUpperCase() + name.substr(1)]) {
                Ext.raise('The ' + me.$className +
                    ' sprite has both a config and an attribute with the same name: ' + name + '.');
            }
        }
        //</debug>

        me.setAttributes(config);
    },

    updateSurface: function(surface, oldSurface) {
        if (oldSurface) {
            oldSurface.remove(this);
        }
    },

    /**
     * @private
     * Current state of the sprite.
     * Set to `true` if the sprite needs to be repainted.
     * @cfg {Boolean} dirty
     * @accessor
     */

    getDirty: function() {
        return this.attr.dirty;
    },

    setDirty: function(dirty) {
        var parent;

        // This could have been a regular attribute.
        // Instead, it's a hidden one, which is initialized inside in the
        // Target's modifier `prepareAttributes` method and is exposed
        // as a config. The idea is to skip the modifier chain when
        // we simply need to change the sprite's state and notify
        // the sprite's parent.
        this.attr.dirty = dirty;

        if (dirty) {
            parent = this.getParent();

            if (parent) {
                parent.setDirty(true);
            }
        }
    },

    addModifier: function(modifier, reinitializeAttributes) {
        var me = this,
            mods = me.modifiers,
            animation = mods.animation,
            target = mods.target,
            type;

        if (!(modifier instanceof Ext.draw.modifier.Modifier)) {
            type = typeof modifier === 'string' ? modifier : modifier.type;

            if (type && !mods[type]) {
                mods[type] = modifier = Ext.factory(modifier, null, null, 'modifier');
            }
        }

        modifier.setSprite(me);

        if (modifier.preFx || modifier.config && modifier.config.preFx) {
            if (animation._lower) {
                animation._lower.setUpper(modifier);
            }

            modifier.setUpper(animation);
        }
        else {
            target._lower.setUpper(modifier);
            modifier.setUpper(target);
        }

        if (reinitializeAttributes) {
            me.initializeAttributes();
        }

        return modifier;
    },

    createModifiers: function(modifiers) {
        var me = this,
            Modifier = Ext.draw.modifier,
            animation = me.getInitialConfig().animation,
            mods, i, ln;

        // Create default modifiers.
        me.modifiers = mods = {
            target: new Modifier.Target({ sprite: me }),
            animation: new Modifier.Animation(Ext.apply({ sprite: me }, animation))
        };

        // Link modifiers.
        mods.animation.setUpper(mods.target);

        for (i = 0, ln = modifiers.length; i < ln; i++) {
            me.addModifier(modifiers[i], false);
        }

        return mods;
    },

    /**
     * Returns the current animation instance.
     * return {Ext.draw.modifier.Animation} The animation modifier used to animate the 
     * sprite
     */
    getAnimation: function() {
        return this.modifiers.animation;
    },

    /**
     * Sets the animation config used by the sprite when animating the sprite's 
     * attributes and transformation properties.
     * 
     *     var drawCt = Ext.create({
     *         xtype: 'draw',
     *         renderTo: document.body,
     *         width: 400,
     *         height: 400,
     *         sprites: [{
     *             type: 'rect',
     *             x: 50,
     *             y: 50,
     *             width: 100,
     *             height: 100,
     *             fillStyle: '#1F6D91'
     *         }]
     *     });
     *     
     *     var rect = drawCt.getSurface().getItems()[0];
     *     
     *     rect.setAnimation({
     *         duration: 1000,
     *         easing: 'elasticOut'
     *     });
     *     
     *     Ext.defer(function () {
     *         rect.setAttributes({
     *             width: 250
     *         });
     *     }, 500);
     * 
     * @param {Object} config The Ext.draw.modifier.Animation config for this sprite's 
     * animations.
     */
    setAnimation: function(config) {
        if (!this.isConfiguring) {
            this.modifiers.animation.setConfig(config || { duration: 0 });
        }
    },

    initializeAttributes: function() {
        this.modifiers.target.prepareAttributes(this.attr);
    },

    /**
     * @private
     * Calls updaters triggered by changes to sprite attributes.
     * @param attr The attributes of a sprite or its instance.
     */
    callUpdaters: function(attr) {
        var me = this,
            updaters = me.self.def.getUpdaters(),
            any = false,
            dirty = false,
            pendingUpdaters, flags, updater, fn;

        attr = attr || this.attr;
        pendingUpdaters = attr.pendingUpdaters;

        // If updaters set sprite attributes that trigger other updaters,
        // those updaters are not called right away, but wait until all current
        // updaters are called (till the next do/while loop iteration).

        me.callUpdaters = Ext.emptyFn; // Hide class method from the instance.

        do {
            any = false;

            for (updater in pendingUpdaters) {
                any = true;
                flags = pendingUpdaters[updater];
                delete pendingUpdaters[updater];
                fn = updaters[updater];

                if (typeof fn === 'string') {
                    fn = me[fn];
                }

                if (fn) {
                    fn.call(me, attr, flags);
                }
            }

            dirty = dirty || any;
        } while (any);

        delete me.callUpdaters; // Restore class method.

        if (dirty) {
            me.setDirty(true);
        }
    },

    /**
     * @private
     */
    callUpdater: function(attr, updater, triggers) {
        this.scheduleUpdater(attr, updater, triggers);
        this.callUpdaters(attr);
    },

    /**
     * @private
     * Schedules specified updaters to be called.
     * Updaters are called implicitly as a result of a change to sprite attributes.
     * But sometimes it may be required to call an updater without setting an attribute,
     * and without messing up the updater call order (by calling the updater immediately).
     * For example:
     *
     *     updaters: {
     *          onDataX: function (attr) {
     *              this.processDataX();
     *              // Process data Y every time data X is processed.
     *              // Call the onDataY updater as if changes to dataY attribute itself
     *              // triggered the update.
     *              this.scheduleUpdaters(attr, {onDataY: ['dataY']});
     *              // Alternatively:
     *              // this.scheduleUpdaters(attr, ['onDataY'], ['dataY']);
     *          }
     *     }
     *
     * @param {Object} attr The attributes object (not necesseraly of a sprite,
     * but of its instance).
     * @param {Object/String[]} updaters A map of updaters to be called to attributes
     * that triggered the update.
     * @param {String[]} [triggers] Attributes that triggered the update. An optional parameter.
     * If used, the `updaters` parameter will be treated as an array of updaters to be called.
     */
    scheduleUpdaters: function(attr, updaters, triggers) {
        var updater, i, ln;

        attr = attr || this.attr;

        if (triggers) {
            for (i = 0, ln = updaters.length; i < ln; i++) {
                updater = updaters[i];
                this.scheduleUpdater(attr, updater, triggers);
            }
        }
        else {
            for (updater in updaters) {
                triggers = updaters[updater];
                this.scheduleUpdater(attr, updater, triggers);
            }
        }
    },

    /**
     * @private
     * @param attr {Object} The attributes object (not necesseraly of a sprite,
     * but of its instance).
     * @param updater {String} Updater to be called.
     * @param {String[]} [triggers] Attributes that triggered the update.
     */
    scheduleUpdater: function(attr, updater, triggers) {
        var pendingUpdaters;

        triggers = triggers || [];
        attr = attr || this.attr;
        pendingUpdaters = attr.pendingUpdaters;

        if (updater in pendingUpdaters) {
            if (triggers.length) {
                pendingUpdaters[updater] = Ext.Array.merge(pendingUpdaters[updater], triggers);
            }
        }
        else {
            pendingUpdaters[updater] = triggers;
        }
    },

    /**
     * Set attributes of the sprite.
     * By default only the attributes that have processors will be set
     * and all other attributes will be filtered out as a result of the
     * normalization process.
     * The normalization process can be skipped. In that case all the given
     * attributes will be set unprocessed. This will result in better
     * performance, but might also pollute the sprite's attributes with
     * unwanted attributes or attributes with invalid values, if one is not
     * careful. See also {@link #setAttributesBypassingNormalization}.
     * If normalization is skipped, one may also chose to avoid copying
     * the given object. This may result in even better performance, but
     * only in cases where most of the attributes have values that are
     * different from the old values, because copying additionally checks
     * if the value has changed.
     *
     * @param {Object} changes The content of the change.
     * @param {Boolean} [bypassNormalization] `true` to avoid normalization of the given changes.
     * @param {Boolean} [avoidCopy] `true` to avoid copying the `changes` object.
     * `bypassNormalization` should also be `true`. The content of object may be destroyed.
     */
    setAttributes: function(changes, bypassNormalization, avoidCopy) {
        var me = this,
            changesToPush;

        //<debug>
        if (me.destroyed) {
            Ext.Error.raise("Setting attributes of a destroyed sprite.");
        }
        //</debug>

        if (bypassNormalization) {
            if (avoidCopy) {
                changesToPush = changes;
            }
            else {
                changesToPush = Ext.apply({}, changes);
            }
        }
        else {
            changesToPush = me.self.def.normalize(changes);
        }

        me.modifiers.target.pushDown(me.attr, changesToPush);
    },

    /**
     * Set attributes of the sprite, assuming the names and values have already been
     * normalized.
     *
     * @deprecated 6.5.0 Use setAttributes directly with bypassNormalization argument being `true`.
     * @param {Object} changes The content of the change.
     * @param {Boolean} [avoidCopy] `true` to avoid copying the `changes` object.
     * The content of object may be destroyed.
     */
    setAttributesBypassingNormalization: function(changes, avoidCopy) {
        return this.setAttributes(changes, true, avoidCopy);
    },

    /**
     * @private
     */
    bboxUpdater: function(attr) {
        var hasRotation = attr.rotationRads !== 0,
            hasScaling = attr.scalingX !== 1 || attr.scalingY !== 1,
            noRotationCenter = attr.rotationCenterX === null || attr.rotationCenterY === null,
            noScalingCenter = attr.scalingCenterX === null || attr.scalingCenterY === null;

        // 'bbox' is not a standard attribute (in the sense that it doesn't have
        // a processor = not explicitly declared and cannot be set by a user)
        // and is calculated automatically by the 'getBBox' method.
        // The 'bbox' attribute is created by the 'prepareAttributes' method
        // of the Target modifier at construction time.

        // Both plain and tranformed bounding boxes need to be updated.
        // Mark them as such below.
        attr.bbox.plain.dirty = true;      // updated by the 'updatePlainBBox' method

        // Before transformed bounding box can be updated,
        // we must ensure that we have correct forward and inverse
        // transformation matrices (which are also created by the Target modifier),
        // so that they reflect the current state of the scaling, rotation
        // and other transformation attributes.
        // The 'applyTransformations' method does just that.

        // The 'dirtyTransform' flag (another implicit attribute)
        // is set to true when any of the transformation attributes change,
        // to let us know that transformation matrices need to be updated.

        attr.bbox.transform.dirty = true;  // updated by the 'updateTransformedBBox' method

        if (hasRotation && noRotationCenter || hasScaling && noScalingCenter) {
            this.scheduleUpdater(attr, 'transform');
        }
    },

    /**
     * Returns the bounding box for the given Sprite as calculated with the Canvas engine.
     *
     * @param {Boolean} [isWithoutTransform] Whether to calculate the bounding box
     * with the current transforms or not.
     */
    getBBox: function(isWithoutTransform) {
        var me = this,
            attr = me.attr,
            bbox = attr.bbox,
            plain = bbox.plain,
            transform = bbox.transform;

        if (plain.dirty) {
            me.updatePlainBBox(plain);
            plain.dirty = false;
        }

        if (!isWithoutTransform) {
            // If tranformations are to be applied ('dirtyTransform' is true),
            // then this will itself call the 'getBBox' method
            // to get the plain untransformed bbox and calculate its center.
            me.applyTransformations();

            if (transform.dirty) {
                me.updateTransformedBBox(transform, plain);
                transform.dirty = false;
            }

            return transform;
        }

        return plain;
    },

    /**
     * @method
     * @protected
     * Subclass will fill the plain object with `x`, `y`, `width`, `height` information
     * of the plain bounding box of this sprite.
     *
     * @param {Object} plain Target object.
     */
    updatePlainBBox: Ext.emptyFn,

    /**
     * @protected
     * Subclass will fill the plain object with `x`, `y`, `width`, `height` information
     * of the transformed bounding box of this sprite.
     *
     * @param {Object} transform Target object (transformed bounding box) to populate.
     * @param {Object} plain Untransformed bounding box.
     */
    updateTransformedBBox: function(transform, plain) {
        this.attr.matrix.transformBBox(plain, 0, transform);
    },

    /**
     * Subclass can rewrite this function to gain better performance.
     * @param {Boolean} isWithoutTransform
     * @return {Array}
     */
    getBBoxCenter: function(isWithoutTransform) {
        var bbox = this.getBBox(isWithoutTransform);

        if (bbox) {
            return [
                bbox.x + bbox.width * 0.5,
                bbox.y + bbox.height * 0.5
            ];
        }
        else {
            return [0, 0];
        }
    },

    /**
     * Hide the sprite.
     * @return {Ext.draw.sprite.Sprite} this
     * @chainable
     */
    hide: function() {
        this.attr.hidden = true;
        this.setDirty(true);

        return this;
    },

    /**
     * Show the sprite.
     * @return {Ext.draw.sprite.Sprite} this
     * @chainable
     */
    show: function() {
        this.attr.hidden = false;
        this.setDirty(true);

        return this;
    },

    /**
     * Applies sprite's attributes to the given context.
     * @param {Object} ctx Context to apply sprite's attributes to.
     * @param {Array} rect The rect of the context to be affected by gradients.
     */
    useAttributes: function(ctx, rect) {
        // Always (force) apply transformation to sprite instances,
        // even if their 'dirtyTransform' flag is false.
        // The 'dirtyTransform' flag of an instance may never be set to 'true', as the
        // 'transform' updater won't ever be called for sprite instances that have
        // the same transform attributes as their template, because there's nothing to update
        // (an instance is simply a prototype chained template's 'attr' object, that only
        // has own properties for attributes whose values are different).
        // Making the modifier recognize transform attributes set on sprite instances
        // (see Ext.draw.modifier.Modifier's 'pushDown' method, where attributes with
        // same values are removed from the 'changes' object) and making sure their 'dirtyTransform'
        // flag is set to 'true' is not a correct solution here, because of the way instances
        // are rendered (see Ext.draw.sprite.Instancing's 'render' method) - there is no way
        // an instance wounldn't want its 'applyTransformations' method called.
        this.applyTransformations(this.isSpriteInstance);

        // eslint-disable-next-line vars-on-top
        var attr = this.attr,
            canvasAttributes = attr.canvasAttributes,
            strokeStyle = canvasAttributes.strokeStyle,
            fillStyle = canvasAttributes.fillStyle,
            lineDash = canvasAttributes.lineDash,
            lineDashOffset = canvasAttributes.lineDashOffset,
            id;

        if (strokeStyle) {
            if (strokeStyle.isGradient) {
                ctx.strokeStyle = 'black';
                ctx.strokeGradient = strokeStyle;
            }
            else {
                ctx.strokeGradient = false;
            }
        }

        if (fillStyle) {
            if (fillStyle.isGradient) {
                ctx.fillStyle = 'black';
                ctx.fillGradient = fillStyle;
            }
            else {
                ctx.fillGradient = false;
            }
        }

        if (lineDash) {
            ctx.setLineDash(lineDash);
        }

        // Only set lineDashOffset to contexts that support the property (excludes VML).
        if (Ext.isNumber(lineDashOffset) && Ext.isNumber(ctx.lineDashOffset)) {
            ctx.lineDashOffset = lineDashOffset;
        }

        for (id in canvasAttributes) {
            if (canvasAttributes[id] !== undefined && canvasAttributes[id] !== ctx[id]) {
                ctx[id] = canvasAttributes[id];
            }
        }

        this.setGradientBBox(ctx, rect);
    },

    setGradientBBox: function(ctx, rect) {
        var attr = this.attr;

        if (attr.constrainGradients) {
            ctx.setGradientBBox({ x: rect[0], y: rect[1], width: rect[2], height: rect[3] });
        }
        else {
            ctx.setGradientBBox(this.getBBox(attr.transformFillStroke));
        }
    },

    /**
     * @private
     *
     * Calculates forward and inverse transform matrices from sprite's attributes.
     * Transformations are applied in the following order: Scaling, Rotation, Translation.
     * @param {Boolean} [force=false] Forces recalculation of transform matrices even when
     * sprite's transform attributes supposedly haven't changed.
     */
    applyTransformations: function(force) {
        if (!force && !this.attr.dirtyTransform) {
            return;
        }

        // eslint-disable-next-line vars-on-top
        var me = this,
            attr = me.attr,
            center = me.getBBoxCenter(true),
            centerX = center[0],
            centerY = center[1],

            tx = attr.translationX,
            ty = attr.translationY,

            sx = attr.scalingX,
            sy = attr.scalingY === null ? attr.scalingX : attr.scalingY,
            scx = attr.scalingCenterX === null ? centerX : attr.scalingCenterX,
            scy = attr.scalingCenterY === null ? centerY : attr.scalingCenterY,

            rad = attr.rotationRads,
            rcx = attr.rotationCenterX === null ? centerX : attr.rotationCenterX,
            rcy = attr.rotationCenterY === null ? centerY : attr.rotationCenterY,

            cos = Math.cos(rad),
            sin = Math.sin(rad),

            tx_4, ty_4;

        if (sx === 1 && sy === 1) {
            scx = 0;
            scy = 0;
        }

        if (rad === 0) {
            rcx = 0;
            rcy = 0;
        }

        // Translation component after steps 1-4 (see below).
        // Saving it here to prevent double calculation.
        tx_4 = scx * (1 - sx) - rcx;
        ty_4 = scy * (1 - sy) - rcy;

        /* eslint-disable max-len */
        // The matrix below is a result of:
        //     (7)          (6)             (5)             (4)           (3)           (2)           (1)
        // | 1 0 tx |   | 1 0 rcx |   | cos -sin 0 |   | 1 0 -rcx |   | 1 0 scx |   | sx 0 0 |   | 1 0 -scx |
        // | 0 1 ty | * | 0 1 rcy | * | sin  cos 0 | * | 0 1 -rcy | * | 0 1 scy | * | 0 sy 0 | * | 0 1 -scy |
        // | 0 0  1 |   | 0 0  1  |   |  0    0  1 |   | 0 0  1   |   | 0 0  1  |   | 0  0 0 |   | 0 0  1   |
        /* eslint-enable max-len */
        attr.matrix.elements = [
            cos * sx, sin * sx,
            -sin * sy, cos * sy,
            cos * tx_4 - sin * ty_4 + rcx + tx,
            sin * tx_4 + cos * ty_4 + rcy + ty
        ];
        attr.matrix.inverse(attr.inverseMatrix);
        attr.dirtyTransform = false;
        attr.bbox.transform.dirty = true;
    },

    /**
     * Pre-multiplies the current transformation matrix of a sprite with the given matrix.
     * If `isSplit` parameter is `true`, the resulting matrix is also split into
     * individual components (scaling, rotation, translation) and corresponding sprite
     * attributes are updated. The shearing component is not extracted.
     * Note, that transformation attributes work as if transformations are applied to the
     * local coordinate system of a sprite, while matrix transformations transform
     * the global coordinate space or the surface grid.
     * Since the `transform` method returns the sprite itself, calls to the method
     * can be chained. And if updating sprite transformation attributes is desired,
     * it can be achieved by setting the `isSplit` parameter of the last call to `true`.
     * For example:
     *
     *     sprite.transform(matrixA).transform(matrixB).transform(matrixC, true);
     * 
     * See also: {@link #setTransform}
     *
     * @param {Ext.draw.Matrix/Number[]} matrix A transformation matrix or array of its elements.
     * @param {Boolean} [isSplit=false] If 'true', transformation attributes are updated.
     * @return {Ext.draw.sprite.Sprite} This sprite.
     */
    transform: function(matrix, isSplit) {
        var attr = this.attr,
            spriteMatrix = attr.matrix,
            elements;

        if (matrix && matrix.isMatrix) {
            elements = matrix.elements;
        }
        else {
            elements = matrix;
        }

        //<debug>
        if (!(Ext.isArray(elements) && elements.length === 6)) {
            Ext.raise("An instance of Ext.draw.Matrix or an array of 6 numbers is expected.");
        }
        //</debug>

        spriteMatrix.prepend.apply(spriteMatrix, elements.slice());
        spriteMatrix.inverse(attr.inverseMatrix);

        if (isSplit) {
            this.updateTransformAttributes();
        }

        attr.dirtyTransform = false;
        attr.bbox.transform.dirty = true;

        this.setDirty(true);

        return this;
    },

    /**
     * @private
     */
    updateTransformAttributes: function() {
        var attr = this.attr,
            split = attr.matrix.split();

        attr.rotationRads = split.rotate;
        attr.rotationCenterX = 0;
        attr.rotationCenterY = 0;
        attr.scalingX = split.scaleX;
        attr.scalingY = split.scaleY;
        attr.scalingCenterX = 0;
        attr.scalingCenterY = 0;
        attr.translationX = split.translateX;
        attr.translationY = split.translateY;
    },

    /**
     * Resets current transformation matrix of a sprite to the identify matrix.
     * @param {Boolean} [isSplit=false] If 'true', transformation attributes are updated.
     * @return {Ext.draw.sprite.Sprite} This sprite.
     */
    resetTransform: function(isSplit) {
        var attr = this.attr;

        attr.matrix.reset();
        attr.inverseMatrix.reset();

        if (!isSplit) {
            this.updateTransformAttributes();
        }

        attr.dirtyTransform = false;
        attr.bbox.transform.dirty = true;

        this.setDirty(true);

        return this;
    },

    /**
     * Resets current transformation matrix of a sprite to the identify matrix
     * and pre-multiplies it with the given matrix.
     * This is effectively the same as calling {@link #resetTransform},
     * followed by {@link #transform} with the same arguments.
     * 
     * See also: {@link #transform}
     * 
     *     var drawContainer = new Ext.draw.Container({
     *         renderTo: Ext.getBody(),
     *         width: 380,
     *         height: 380,
     *         sprites: [{
     *             type: 'rect',
     *             width: 100,
     *             height: 100,
     *             fillStyle: 'red'
     *         }]
     *     });
     *     
     *     var main = drawContainer.getSurface();
     *     var rect = main.getItems()[0];
     *     
     *     var m = new Ext.draw.Matrix().rotate(Math.PI, 100, 100);
     *     
     *     rect.setTransform(m);
     *     main.renderFrame();
     * 
     * There may be times where the transformation you need to apply cannot easily be 
     * accomplished using the sprite’s convenience transform methods.  Or, you may want 
     * to pass a matrix directly to the sprite in order to set a transformation.  The 
     * `setTransform` method allows for this sort of advanced usage as well.  The 
     * following tables show each transformation matrix used when applying 
     * transformations to a sprite.
     * 
     * ### Translate
     * <table style="text-align: center;">
     *     <tr>
     *         <td style="font-weight: normal;">1</td>
     *         <td style="font-weight: normal;">0</td>
     *         <td style="font-weight: normal;">tx</td>
     *     </tr>
     *     <tr>
     *         <td>0</td>
     *         <td>1</td>
     *         <td>ty</td>
     *     </tr>
     *     <tr>
     *         <td>0</td>
     *         <td>0</td>
     *         <td>1</td>
     *     </tr>
     * </table>
     * 
     * ### Rotate (θ is the angle of rotation)
     * <table style="text-align: center;">
     *     <tr>
     *         <td style="font-weight: normal;">cos(θ)</td>
     *         <td style="font-weight: normal;">-sin(θ)</td>
     *         <td style="font-weight: normal;">0</td>
     *     </tr>
     *     <tr>
     *         <td>0</td>
     *         <td>cos(θ)</td>
     *         <td>0</td>
     *     </tr>
     *     <tr>
     *         <td>0</td>
     *         <td>0</td>
     *         <td>1</td>
     *     </tr>
     * </table>
     * 
     * ### Scale
     * <table style="text-align: center;">
     *     <tr>
     *         <td style="font-weight: normal;">sx</td>
     *         <td style="font-weight: normal;">0</td>
     *         <td style="font-weight: normal;">0</td>
     *     </tr>
     *     <tr>
     *         <td>0</td>
     *         <td>cos(θ)</td>
     *         <td>0</td>
     *     </tr>
     *     <tr>
     *         <td>0</td>
     *         <td>0</td>
     *         <td>1</td>
     *     </tr>
     * </table>
     * 
     * ### Shear X _(λ is the distance on the x axis to shear by)_
     * <table style="text-align: center;">
     *     <tr>
     *         <td style="font-weight: normal;">1</td>
     *         <td style="font-weight: normal;">λx</td>
     *         <td style="font-weight: normal;">0</td>
     *     </tr>
     *     <tr>
     *         <td>0</td>
     *         <td>1</td>
     *         <td>0</td>
     *     </tr>
     *     <tr>
     *         <td>0</td>
     *         <td>0</td>
     *         <td>1</td>
     *     </tr>
     * </table>
     * 
     * ### Shear Y (λ is the distance on the y axis to shear by)
     * <table style="text-align: center;">
     *     <tr>
     *         <td style="font-weight: normal;">1</td>
     *         <td style="font-weight: normal;">0</td>
     *         <td style="font-weight: normal;">0</td>
     *     </tr>
     *     <tr>
     *         <td>λy</td>
     *         <td>1</td>
     *         <td>0</td>
     *     </tr>
     *     <tr>
     *         <td>0</td>
     *         <td>0</td>
     *         <td>1</td>
     *     </tr>
     * </table>
     * 
     * ### Skew X (θ is the angle to skew by)
     * <table style="text-align: center;">
     *     <tr>
     *         <td style="font-weight: normal;">1</td>
     *         <td style="font-weight: normal;">tan(θ)</td>
     *         <td style="font-weight: normal;">0</td>
     *     </tr>
     *     <tr>
     *         <td>0</td>
     *         <td>1</td>
     *         <td>0</td>
     *     </tr>
     *     <tr>
     *         <td>0</td>
     *         <td>0</td>
     *         <td>1</td>
     *     </tr>
     * </table>
     * 
     * ### Skew Y (θ is the angle to skew by)
     * <table style="text-align: center;">
     *     <tr>
     *         <td style="font-weight: normal;">1</td>
     *         <td style="font-weight: normal;">0</td>
     *         <td style="font-weight: normal;">0</td>
     *     </tr>
     *     <tr>
     *         <td>tan(θ)</td>
     *         <td>1</td>
     *         <td>0</td>
     *     </tr>
     *     <tr>
     *         <td>0</td>
     *         <td>0</td>
     *         <td>1</td>
     *     </tr>
     * </table>
     * 
     * Multiplying matrices for translation, rotation, scaling, and shearing / skewing 
     * any number of times in the desired order produces a single matrix for a composite 
     * transformation.  You can use the product as a value for the `setTransform`method 
     * of a sprite:
     * 
     *     mySprite.setTransform([a, b, c, d, e, f]);
     * 
     * Where `a`, `b`, `c`, `d`, `e`, `f` are numeric values that correspond to the 
     * following transformation matrix components:
     * 
     * <table style="text-align: center;">
     *     <tr>
     *         <td style="font-weight: normal;">a</td>
     *         <td style="font-weight: normal;">c</td>
     *         <td style="font-weight: normal;">e</td>
     *     </tr>
     *     <tr>
     *         <td>b</td>
     *         <td>d</td>
     *         <td>f</td>
     *     </tr>
     *     <tr>
     *         <td>0</td>
     *         <td>0</td>
     *         <td>1</td>
     *     </tr>
     * </table>
     * 
     * @param {Ext.draw.Matrix/Number[]} matrix The transformation matrix to apply or its 
     * raw elements as an array.
     * @param {Boolean} [isSplit=false] If `true`, transformation attributes are updated.
     * @return {Ext.draw.sprite.Sprite} This sprite.
     */
    setTransform: function(matrix, isSplit) {
        this.resetTransform(true);
        this.transform.call(this, matrix, isSplit);

        return this;
    },

    /**
     * @method
     * Called before rendering.
     */
    preRender: Ext.emptyFn,

    /**
     * @method
     * This is where the actual sprite rendering happens by calling `ctx` methods.
     * @param {Ext.draw.Surface} surface A draw container surface.
     * @param {CanvasRenderingContext2D} ctx A context object that is API compatible with the native
     * [CanvasRenderingContext2D](https://developer.mozilla.org/en/docs/Web/API/CanvasRenderingContext2D).
     * @param {Number[]} surfaceClipRect The clip rect: [left, top, width, height].
     * Not to be confused with the `surface.getRect()`, which represents the location
     * and size of the surface in a draw container, in draw container coordinates.
     * The clip rect on the other hand represents the portion of the surface that is being
     * rendered, in surface coordinates.
     *
     * @return {*} returns `false` to stop rendering in this frame.
     * All the sprites that haven't been rendered will have their dirty flag untouched.
     */
    render: Ext.emptyFn,

    //<debug>
    /**
     * @private
     * Renders the bounding box of transformed sprite.
     */
    renderBBox: function(surface, ctx) {
        var bbox = this.getBBox();

        ctx.beginPath();
        ctx.moveTo(bbox.x, bbox.y);
        ctx.lineTo(bbox.x + bbox.width, bbox.y);
        ctx.lineTo(bbox.x + bbox.width, bbox.y + bbox.height);
        ctx.lineTo(bbox.x, bbox.y + bbox.height);
        ctx.closePath();

        ctx.strokeStyle = 'red';
        ctx.strokeOpacity = 1;
        ctx.lineWidth = 0.5;

        ctx.stroke();
    },
    //</debug>

    /**
     * Performs a hit test on the sprite.
     * @param {Array} point A two-item array containing x and y coordinates of the point.
     * @param {Object} options Hit testing options.
     * @return {Object} A hit result object that contains more information about what
     * exactly was hit or null if nothing was hit.
     */
    hitTest: function(point, options) {
        var x, y, bbox, isBBoxHit;

        // Meant to be overridden in subclasses for more precise hit testing.
        // This version doesn't take any options and simply hit tests sprite's
        // bounding box, if the sprite is visible.
        if (this.isVisible()) {
            x = point[0];
            y = point[1];
            bbox = this.getBBox();
            isBBoxHit = bbox && x >= bbox.x && x <= (bbox.x + bbox.width) &&
                                y >= bbox.y && y <= (bbox.y + bbox.height);

            if (isBBoxHit) {
                return {
                    sprite: this
                };
            }
        }

        return null;
    },

    /**
     * @private
     * Checks if the sprite can be seen.
     * This includes the `hidden` attribute check, alpha/opacity checks,
     * fill/stroke color checks and surface/parent checks.
     * The method doesn't check if the sprite is off-screen.
     * @return {Boolean} Returns `true`, if the sprite can be seen.
     */
    isVisible: function() {
        var attr = this.attr,
            parent = this.getParent(),
            hasParent = parent && (parent.isSurface || parent.isVisible()),
            isSeen = hasParent && !attr.hidden && attr.globalAlpha,
            none1 = Ext.util.Color.NONE,
            none2 = Ext.util.Color.RGBA_NONE,
            hasFill = attr.fillOpacity && attr.fillStyle !== none1 && attr.fillStyle !== none2,
            hasStroke = attr.strokeOpacity && attr.strokeStyle !== none1 &&
                        attr.strokeStyle !== none2,
            result = isSeen && (hasFill || hasStroke);

        return !!result;
    },

    repaint: function() {
        var surface = this.getSurface();

        if (surface) {
            surface.renderFrame();
        }
    },

    /**
     * Removes this sprite from its surface.
     * The sprite itself is not destroyed.
     * @return {Ext.draw.sprite.Sprite} Returns the removed sprite or `null` otherwise.
     */
    remove: function() {
        var surface = this.getSurface();

        if (surface && surface.isSurface) {
            return surface.remove(this);
        }

        return null;
    },

    /**
     * Removes the sprite and clears all listeners.
     */
    destroy: function() {
        var me = this,
            modifier = me.modifiers.target,
            currentModifier;

        while (modifier) {
            currentModifier = modifier;
            modifier = modifier._lower;
            currentModifier.destroy();
        }

        delete me.attr;

        me.remove();

        if (me.fireEvent('beforedestroy', me) !== false) {
            me.fireEvent('destroy', me);
        }

        me.callParent();
    }
}, function() { // onClassCreated
    // Create one AttributeDefinition instance per sprite class when a class is created
    // and replace the `def` config with the instance that was created using that config.
    // Here we only create an AttributeDefinition instance for the base Sprite class,
    // attribute definitions for subclasses are created inside onClassExtended method.
    this.def = new Ext.draw.sprite.AttributeDefinition(this.def);
    this.def.spriteClass = this;
});

