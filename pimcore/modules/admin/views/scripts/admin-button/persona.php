<!DOCTYPE html>
<html lang="en">
<head>

    <link rel="stylesheet" type="text/css" href="/pimcore/static/js/frontend/admin/iframe.css" />
</head>


<body>

    <div>
        <h1>
            Change Persona to (not permanently):
        </h1>

        <ul>
            <?php foreach($this->personas as $persona) { ?>
                <li onclick="changePersona(<?= $persona->getId() ?>)"><?= $persona->getName() ?></li>
            <?php } ?>
        </ul>

        <style type="text/css">
            li {
                cursor: pointer;
            }
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
        </script>
</body>

</html>
