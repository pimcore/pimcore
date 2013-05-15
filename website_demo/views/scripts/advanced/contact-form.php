
<?php $this->template("/content/default.php"); ?>


<?php if(!$this->success) { ?>
    <form action="" method="post">
        <fieldset>

            <label><?php echo $this->translate("Gender"); ?></label>
            <select name="gender">
                <option value="male">Male</option>
                <option value="female">Female</option>
            </select>

            <label><?php echo $this->translate("Firstname"); ?></label>
            <input name="firstname" type="text" placeholder="" value="<?php echo $this->firstname; ?>">

            <label><?php echo $this->translate("Lastname"); ?></label>
            <input name="lastname" type="text" placeholder="" value="<?php echo $this->lastname; ?>">

            <label><?php echo $this->translate("E-Mail"); ?></label>
            <input name="email" type="text" placeholder="example@example.com" value="<?php echo $this->email; ?>">


            <label><?php echo $this->translate("Message"); ?></label>
            <textarea name="message" type="text" placeholder="" value="<?php echo $this->message; ?>"></textarea>

            <br />

            <button type="submit" class="btn"><?php echo $this->translate("Submit"); ?></button>
        </fieldset>
    </form>
<?php } else { ?>

    <h2><?php echo $this->translate("Thank you very much"); ?></h2>

    <p>
        We received the following information from you:

        <br />
        <br />

        <b>Firstname: </b> <?php echo $this->firstname; ?><br />
        <b>Lastname: </b> <?php echo $this->lastname; ?><br />
        <b>E-Mail: </b> <?php echo $this->email; ?><br />
    </p>
<?php } ?>
