/**
 Wrap funnel actions and other SEO activities.
**/
var ecommerceFunnel = {

    init: function() {
        this._ecommerceExtensions();
    },

    _ecommerceExtractProductdata: function(element) {
        var data = $(element).data();
        var category = data.eccategory;
        var productName =  data.ecproductname;
        var brandName = data.ecproductbrand;
        var productId = data.ecproductid;
        var variant = data.ecvariant;
        var price = data.ecprice;

        var params = {
            'id': productId,
            'name': productName,
            'category': category,
            'brand': brandName,
            'price' : price
        };

        if (variant) {
            params.variant = variant;
        }
        return params;
    },

    /* register and execute page events */
    _ecommerceExtensions: function() {
        var clazz = this;
        ga('require', 'ec');

        //track product impressions
        $('.js-ec-productimpression:not(.js-inititalized-impression-ec)').each(function(i,elem) {
            var trackingParams = clazz._ecommerceExtractProductdata($(this));
            trackingParams.position=i+1;
            ga("ec:addImpression", trackingParams)
        });

        $('.js-ec-productclick:not(.js-inititalized-impression-ec)').addClass('js-inititalized-impression-ec');

        //track product clicks
        $('.js-ec-productclick:not(.js-inititalized-ec)').on('click', function(event) {
            event.preventDefault();
            var aElement = $(this);

            var trackingParams = clazz._ecommerceExtractProductdata(aElement);

            $('.js-ec-productclick').each(function(i,elem) {
                var elemProductId = $(elem).data('ecproductid');
                if (elemProductId == trackingParams.id) {
                    position=i+1;
                }
            });
            trackingParams.position = position;
            ga('ec:addProduct', trackingParams);
            ga("ec:setAction", "click", {list: ""})

            ga('send', 'event', '', 'Click on Product', trackingParams.productName, {
                hitCallback: function() {
                    document.location = $(aElement).attr('href');
                }
            });
        });
        $('.js-ec-productclick:not(.js-inititalized-ec)').addClass('js-inititalized-ec');
    },

    /* can be used to execute dynamically loaded JS tracking code, for instance when adding items to a cart. */
    ecommerceExecuteAjaxCode: function(trackingCode) {
        if (trackingCode) {
            var domElement = $( '<div></div>' );
            domElement.html(trackingCode);
            var scriptCode = $(domElement).find('script').html();
            eval(scriptCode);
        }
    },
};