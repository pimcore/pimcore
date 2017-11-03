(function () {
    var visitorId;

    // see http://clubmate.fi/setting-and-reading-cookies-with-javascript/
    var Cookie = {
        set: function (name, value, days) {
            var expires;
            if (days) {
                var date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                expires = "; expires=" + date.toGMTString();
            }
            else {
                expires = "";
            }

            document.cookie = name + "=" + value + expires + "; path=/";
        }
    };

    window.pimcore = window.pimcore || {};
    window.pimcore.Targeting = {
        setVisitorId: function (id) {
            console.log('[TARGETING] Setting visitor ID to', id);
            Cookie.set('_pc_vis', id, 365);
        }
    };
}());
