<!DOCTYPE html>
<html lang="en">
<head>

    <link rel="stylesheet" type="text/css" href="/pimcore/static/js/frontend/admin/iframe.css" />
</head>


<body>

    <div>
        <h1>
            <?php echo $this->translate("promotion_enquiry_for"); ?>:
            <br />
            <small><?php echo $this->getParam("url"); ?></small>

        </h1>

        <?php if(!$this->getParam("submit")) { ?>
            <form action="" method="post">

                <label><?php echo $this->translate("ad_type"); ?> (Display, AdWords, ...)</label>
                <input type="text" name="type" />

                <label><?php echo $this->translate("budget"); ?></label>
                <input type="text" name="budget" />

                <label><?php echo $this->translate("duration"); ?></label>
                <input type="text" name="duration" />

                <label><?php echo $this->translate("notes"); ?></label>
                <textarea name="notes"></textarea>

                <input type="submit" name="submit" value="<?php echo $this->translate("submit"); ?>" />
            </form>
        <?php } else { ?>
            <br />
            <br />
            <br />
            <strong><?php echo $this->translate("promotion_enquiry_sent_success"); ?> (<?php echo $this->contactEmail; ?>).</strong>


            <script type="text/javascript">
                window.setTimeout(function () {
                    var existing = top.document.getElementById("pimcore_admin_lightbox");
                    if(existing) {
                        existing.parentNode.removeChild(existing);
                    }
                }, 5000)
            </script>
        <?php } ?>
    </div>
</body>

</html>
