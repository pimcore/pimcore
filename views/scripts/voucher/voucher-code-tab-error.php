<head>
    <link href="/plugins/OnlineShop/static/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>


<div class="alert alert-danger">
    <? if (is_array($this->errors)) { ?>
        <? foreach ($this->errors as $error) { ?>
            <?= $error ?>
        <? } ?>
    <? } ?>
</div>



</body>