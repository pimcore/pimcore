<!DOCTYPE html>
<html lang="en">
<head>

    <link rel="stylesheet" type="text/css" href="/pimcore/static/js/frontend/admin/iframe.css" />

    <style type="text/css">
        li {
            cursor: pointer;
            text-decoration: underline;
        }

        .string { color: green; }
        .number { color: darkorange; }
        .boolean { color: blue; }
        .null { color: magenta; }
        .key { color: red; }
    </style>

    <script type="text/javascript">
        function changePersona(id) {
            var user = localStorage.getItem("pimcore_targeting_user");
            user = JSON.parse(user);
            user["personas"] = [id];
            delete user["persona"];
            localStorage.setItem("pimcore_targeting_user", JSON.stringify(user));

            top.location.href = top.location.href.replace(/\??_ptp=[0-9]+/, "");
        }

        function syntaxHighlight(json) {
            if (typeof json != 'string') {
                json = JSON.stringify(json, undefined, 2);
            }
            json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
                var cls = 'number';
                if (/^"/.test(match)) {
                    if (/:$/.test(match)) {
                        cls = 'key';
                    } else {
                        cls = 'string';
                    }
                } else if (/true|false/.test(match)) {
                    cls = 'boolean';
                } else if (/null/.test(match)) {
                    cls = 'null';
                }
                return '<span class="' + cls + '">' + match + '</span>';
            });
        }
    </script>
</head>


<body>

    <div>
        <h1>
            <?php echo $this->translate("change_persona_to"); ?>:
        </h1>

        <ul>
            <?php foreach($this->personas as $persona) { ?>
                <li onclick="changePersona(<?= $persona->getId() ?>)"><?= $persona->getName() ?></li>
            <?php } ?>
        </ul>

        <h1>
            <?php echo $this->translate("change_persona_to"); ?>:
        </h1>

        <pre id="json" class="source"></pre>
        <script type="text/javascript">
            document.getElementById("json").innerHTML = syntaxHighlight(JSON.parse(localStorage.getItem("pimcore_targeting_user")));
        </script>
</body>

</html>
