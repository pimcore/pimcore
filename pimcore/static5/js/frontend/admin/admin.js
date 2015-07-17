
(function () {

    try {

        document.write('<link rel="stylesheet" type="text/css" href="/pimcore/static/js/frontend/admin/admin.css" />');

        var openWindow = function (width, height, url) {

            var remove = function () {
                var existing = document.getElementById("pimcore_admin_lightbox");
                if(existing) {
                    existing.parentNode.removeChild(existing);
                }
            }

            remove();

            if(!width) {
                width = 600;
            }
            if(!height) {
                height = 400;
            }

            var lightbox = document.createElement("div");
            lightbox.id = "pimcore_admin_lightbox";
            lightbox.style.marginTop = "-" + (height/2) + "px";
            lightbox.style.height = height + "px";

            var inner = document.createElement("div");
            inner.className = "inner";
            inner.style.width = width + "px";
            inner.style.height = height + "px";
            inner.innerHTML = '<iframe src="' + url + '" frameborder="0"></iframe>';

            inner.onclick = function () {
                remove();
            };

            lightbox.appendChild(inner);

            document.body.appendChild(lightbox);

            return lightbox;
        };

        var log = function (msg) {
            if (typeof console != "undefined" && typeof console["log"] == "function") {
                console.log(msg);
            }
        };

        window.setTimeout(function () {

            try {

                try {
                    // do no display the button in preview frame inside the admin ui, or in any iframe/frame
                    if(parent != window) {
                        return;
                    }
                } catch (e) {
                    return;
                }

                var container = document.createElement("div");
                container.setAttribute("id", "pimcore_admin_console");
                container.className = "";

                var logo = document.createElement("img");
                logo.src = "/pimcore/static/img/logo-white.png";
                logo.className = "logo";
                container.appendChild(logo);

                var menu = document.createElement("ul");

                logo.onclick = function () {
                    if(container.className.indexOf("open") >= 0) {
                        container.className = "";
                        container.setAttribute("style", "");
                    } else {

                        container.className = "open";
                        container.style.height = (menu.children.length*25 + 45) + "px";
                    }
                };

                if(pimcore["admin"]["documentId"]) {
                    var editButton = document.createElement("li");
                    editButton.className = "button edit";
                    editButton.innerHTML = 'Edit Page';

                    editButton.onclick = function () {
                        if(window.opener && window.opener["pimcore"] && window.opener["pimcore"]["helpers"]) {
                            window.opener.pimcore.helpers.openDocument(pimcore["admin"]["documentId"],"page");
                            alert("Please switch to the pimcore admin tab. This document is already opened for you!");
                        } else {
                            window.open("/admin/login/deeplink?document_" + pimcore["admin"]["documentId"] + "_page");
                        }
                    };

                    menu.appendChild(editButton);
                }

                var featureButton = document.createElement("li");
                featureButton.className = "button feature";
                featureButton.innerHTML = 'Feature Request';
                featureButton.onclick = function () {
                    openWindow(800,500, "/admin/admin-button/feature-request?url=" + encodeURIComponent(window.location.href));
                };
                menu.appendChild(featureButton);

                var bugButton = document.createElement("li");
                bugButton.className = "button bug";
                bugButton.innerHTML = 'Bug Report';
                bugButton.onclick = function () {
                    openWindow(800,500, "/admin/admin-button/bug-report?url=" + encodeURIComponent(window.location.href));
                };
                menu.appendChild(bugButton);

                if(window.pimcore && window.pimcore["personas"]) {
                    var personaButton = document.createElement("li");
                    personaButton.className = "button persona";

                    if(window.pimcore["targeting"]
                        && window.pimcore["targeting"]["user"]
                        && window.pimcore["targeting"]["user"]["persona"]
                        && window.pimcore["personas"][window.pimcore["targeting"]["user"]["persona"]]) {
                        personaButton.innerHTML = '<small style="font-size: 10px">Persona: ' + window.pimcore["personas"][window.pimcore["targeting"]["user"]["persona"]] + '</small>';
                    } else {
                        personaButton.innerHTML = 'Persona';
                    }

                    personaButton.onclick = function () {
                        openWindow(800,500, "/admin/admin-button/persona");
                    };
                    menu.appendChild(personaButton);
                }

                container.appendChild(menu);

                // add the console
                document.body.appendChild(container);

            } catch (e) {
                log(e);
            }
        }, 1000);
    } catch (e) {
        log(e);
    }
})();

