(function() {
    var ls = window["localStorage"];
    if (ls && !ls.getItem("pc-cookie-accepted")) {

        var code = templateCodePlaceholder;
        var ci = window.setInterval(function() {
            if (document.body) {
                clearInterval(ci);
                document.body.insertAdjacentHTML("beforeend", code);

                document.getElementById("pc-accept").onclick = function() {
                    document.getElementById("pc-cookie-notice").style.display = "none";
                    ls.setItem("pc-cookie-accepted", "true");
                };
                document.getElementById("pc-decline").onclick = function() {
                    document.getElementById("pc-cookie-notice").style.display = "none";
                };
            }
        }, 100);
    }
})();