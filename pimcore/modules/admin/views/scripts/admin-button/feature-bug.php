<!DOCTYPE html>
<html lang="en">
<head>

    <link rel="stylesheet" type="text/css" href="/pimcore/static/js/frontend/admin/iframe.css" />
</head>


<body>

    <div>
        <h1>
            <?php if($this->type == "bug") { ?>
                Bug Report for:
            <?php } else { ?>
                Feature Request for:
            <?php } ?>
            <br />
            <small><?php echo $this->getParam("url"); ?></small>

        </h1>

        <?php if(!$this->getParam("submit")) { ?>
            <form action="" method="post">
                <label>Description</label>
                <textarea name="description"></textarea>

                <?php if($this->image) { ?>
                    <br />
                    <br />

                    <label>Put some notes on the screenshot! (click on the image)</label>
                    <div class="screenshot">
                        <img src="<?php echo $this->image; ?>" />
                    </div>
                <?php } ?>


                <input type="hidden" name="markers" />
                <input type="hidden" name="screenshot" value="<?php echo $this->image; ?>" />

                <input type="submit" name="submit" value="Submit" />
            </form>

            <script type="text/javascript" src="/pimcore/static/js/lib/jquery.min.js"></script>
            <script type="text/javascript">
                $(document).ready(function () {
                    $(".screenshot img").click(function(ev) {
                        var offset = $(this).offset();
                        var left = ev.pageX - offset.left;
                        var top = ev.pageY - offset.top;

                        left -= 10;
                        top -= 40;

                        var marker = $('<div class="marker" style="top:' + top + 'px;left:' + left + 'px"><div class="close">x</div><div class="pin"></div><input type="text" /></div>');
                        $(".screenshot").append(marker);


                        $("input", marker).focus();

                        $(".close", marker).click(function () {
                            $(this).parent().remove();
                        });
                    });

                    $("form").submit(function () {

                        var markers = [];
                        $(".screenshot .marker").each(function (i, el) {
                            var pos = $(el).position();
                            pos.left += 10;
                            pos.top += 40;

                            pos.left = pos.left / $(el).parent().width() * 100;
                            pos.top = pos.top / $(el).parent().height() * 100;

                            markers.push({
                                position: pos,
                                text: $("input", el).val()
                            });
                        });

                        $("input[name=markers]").val(JSON.stringify(markers));
                    });
                });
            </script>
        <?php } else { ?>
            <br />
            <br />
            <br />
            <strong>Your
                <?php if($this->type == "bug") { ?>
                    bug report
                <?php } else { ?>
                    feature request
                <?php } ?>
                was sent to the manager of this project (<?php echo $this->contactEmail; ?>).
            </strong>


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
