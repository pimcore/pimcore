<!DOCTYPE html>
<html lang="en">
<head>

    <link rel="stylesheet" type="text/css" href="/pimcore/static/js/frontend/admin/iframe.css" />
</head>


<body>

    <div>
        <h1>
            Promotion Enquiry for:
            <br />
            <small><?php echo $this->getParam("url"); ?></small>

        </h1>

        <?php if(!$this->getParam("submit")) { ?>
            <form action="" method="post">

                <label>Ad-Type (Display, AdWords, ...)</label>
                <input type="text" name="type" />

                <label>Budget</label>
                <input type="text" name="budget" />

                <label>Duration</label>
                <input type="text" name="duration" />

                <label>Notes</label>
                <textarea name="description"></textarea>

                <input type="submit" name="submit" value="Submit" />
            </form>
        <?php } else { ?>
            <br />
            <br />
            <br />
            <strong>Your promotion enquiry was sent to the manager of this project (<?php echo $this->contactEmail; ?>).</strong>
        <?php } ?>
    </div>
</body>

</html>
