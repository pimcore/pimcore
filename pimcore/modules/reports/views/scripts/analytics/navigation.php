<?= '<?xml version="1.0" encoding="utf-8"?>'; ?>
<navigation>
	<current><?= $this->path; ?></current>
	<entries direct="0,90" previous="99,10">
        <?php foreach ($this->prev as $p) { ?>
		  <entry 
            percent="<?= $p["percent"] ?>"
            <?php if ($p["id"]) { ?>action="pimcore.helpers.openDocument(<?= $p["id"] ?>,'page');" <?php } ?>
            id="<?= $p["id"] ?>"
            weight="<?= $p["weight"] ?>"
            ><![CDATA[<?= $p["path"] ?>]]></entry>
        <?php } ?>
	</entries>
	<exits away="25,25" next="72,75">
        <?php foreach ($this->next as $n) { ?>
		  <exit 
            percent="<?= $n["percent"] ?>"
            <?php if ($n["id"]) { ?>action="pimcore.helpers.openDocument(<?= $n["id"] ?>,'page');" <?php } ?>
            id="<?= $n["id"] ?>"
            weight="<?= $n["weight"] ?>"
            ><![CDATA[<?= $n["path"] ?>]]></exit>
        <?php } ?>
	</exits>
    <texts>
        <headline><![CDATA[<?= $this->translate("navigation_summary"); ?>]]></headline>
        <entrances><![CDATA[<?= $this->translate("entrances"); ?>]]></entrances>
        <prev_pages><![CDATA[<?= $this->translate("prev_pages"); ?>]]></prev_pages>
        <exits><![CDATA[<?= $this->translate("exits"); ?>]]></exits>
        <next_pages><![CDATA[<?= $this->translate("next_pages"); ?>]]></next_pages>
    </texts>
</navigation>