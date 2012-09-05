
(function () {


    // process targets
    if(typeof window["_ptd"] != "undefined") {
        var target;
        for(var t=0; t<_ptd.length; t++) {
            target = _ptd[t];

            if(target.conditions.length > 0) {
                if(conditionTest(target.conditions)) {
                    actionsFire(target.actions);
                }
            }
        }
    }

    function conditionTest (conditions) {
        return true;
    }

    function actionsFire (actions) {
        console.log(actions);
    }


    try {

    } catch (e) {}

    try {
        /*if('localStorage' in window && window['localStorage'] !== null) {

            var browsingHistory = localStorage.getItem("tracking_browsing_history");
            if(!browsingHistory) {
                browsingHistory = [];
            } else {
                browsingHistory = JSON.parse(browsingHistory);
            }

            browsingHistory.push(location.href);
            localStorage.setItem("tracking_browsing_history", JSON.stringify(browsingHistory));
        }
        */
    } catch (e) {}



})();

