
(function () {

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

    window.setTimeout(function () {

        try {

            try {
                // do no display the button in preview frame inside the admin ui
                if(parent && parent["pimcore"] && parent["pimcore"]["helpers"]) {
                    return;
                }
            } catch (e) {}

            var html = '<div id="pimcore_admin_console"></div>';

            var container = document.createElement("div");
            container.setAttribute("id", "pimcore_admin_console");
            container.className = "";

            var logo = document.createElement("img");
            logo.src = "/pimcore/static/img/logo-white.png";
            logo.className = "logo";
            container.appendChild(logo);

            logo.onclick = function () {
                if(container.className.indexOf("open") >= 0) {
                    container.className = "";
                } else {
                    container.className = "open";
                }
            };


            var menu = document.createElement("ul");

            if(pimcore["admin"]["documentId"]) {
                var editButton = document.createElement("li");
                editButton.className = "button edit";
                editButton.innerHTML = 'Edit Page';

                editButton.onclick = function () {
                    if(window.opener && window.opener["pimcore"] && window.opener["pimcore"]["helpers"]) {
                        window.opener.pimcore.helpers.openDocument(pimcore["admin"]["documentId"],"page");
                    } else {
                        window.open("/admin/login/deeplink?document_" + pimcore["admin"]["documentId"] + "_page");
                    }
                };

                menu.appendChild(editButton);
            }

            if(pimcore["admin"]["contactEnabled"]) {
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

                var promoteButton = document.createElement("li");
                promoteButton.className = "button promote";
                promoteButton.innerHTML = 'Promote';
                promoteButton.onclick = function () {
                    openWindow(800,500, "/admin/admin-button/promote?url=" + encodeURIComponent(window.location.href));
                };
                menu.appendChild(promoteButton);
            }

            container.appendChild(menu);

            // add the console
            document.body.appendChild(container);

        } catch (e) {
            console.log(e);
        }
    }, 1000);
})();

