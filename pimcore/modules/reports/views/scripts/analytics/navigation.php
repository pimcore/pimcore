<?php echo '<?xml version="1.0" encoding="utf-8"?>'; ?>
<navigation>
	<current><?php echo $this->path; ?></current>
	<entries direct="0,90" previous="99,10">
        <?php foreach ($this->prev as $p) { ?>
		  <entry 
            percent="<?php echo $p["percent"] ?>" 
            <?php if ($p["id"]) { ?>action="pimcore.helpers.openDocument(<?php echo $p["id"] ?>,'page');" <?php } ?>
            id="<?php echo $p["id"] ?>"
            weight="<?php echo $p["weight"] ?>"
            ><![CDATA[<?php echo $p["path"] ?>]]></entry>
        <?php } ?>
	</entries>
	<exits away="25,25" next="72,75">
        <?php foreach ($this->next as $n) { ?>
		  <exit 
            percent="<?php echo $n["percent"] ?>" 
            <?php if ($n["id"]) { ?>action="pimcore.helpers.openDocument(<?php echo $n["id"] ?>,'page');" <?php } ?>
            id="<?php echo $n["id"] ?>"
            weight="<?php echo $n["weight"] ?>"
            ><![CDATA[<?php echo $n["path"] ?>]]></exit>
        <?php } ?>
	</exits>
    <texts>
        <headline><![CDATA[<?php echo $this->translate("navigation_summary"); ?>]]></headline>
        <entrances><![CDATA[<?php echo $this->translate("entrances"); ?>]]></entrances>
        <prev_pages><![CDATA[<?php echo $this->translate("prev_pages"); ?>]]></prev_pages>
        <exits><![CDATA[<?php echo $this->translate("exits"); ?>]]></exits>
        <next_pages><![CDATA[<?php echo $this->translate("next_pages"); ?>]]></next_pages>
    </texts>
</navigation>