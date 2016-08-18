<?php

$db = \Pimcore\Db::get();

$classesData = $db->fetchAll("SELECT * FROM classes");

foreach($classesData as $classData) {
    $class = new \Pimcore\Model\Object\ClassDefinition();
    $class->setValues($classData);

    $class->setPropertyVisibility(\Pimcore\Tool\Serialize::unserialize($classData["propertyVisibility"]));

    $file = PIMCORE_CLASS_DIRECTORY."/definition_". $class->getId() .".psf";
    $layoutData = \Pimcore\Tool\Serialize::unserialize(file_get_contents($file));

    $class->setLayoutDefinitions($layoutData);

    $definitionFile = PIMCORE_CLASS_DIRECTORY."/definition_". $class->getName() .".php";
    if (!is_writable(dirname($definitionFile)) || (is_file($definitionFile) && !is_writable($definitionFile))) {
        throw new \Exception("Cannot write definition file in: " . $definitionFile . " please check write permission on this directory.");
    }

    $clone = clone $class;
    $clone->setDao(null);
    unset($clone->id);
    unset($clone->fieldDefinitions);

    $exportedClass = var_export($clone, true);
    $data = '<?php ';

    $data .= "\n\n";
    $data .= "/** Generated at " . date('c') . " */";
    $data .= "\n\n";

    $data .= "\nreturn " . $exportedClass . ";\n";

    \Pimcore\File::put($definitionFile, $data);
}


$db->query("
ALTER TABLE `classes`
	DROP COLUMN `description`,
	DROP COLUMN `creationDate`,
	DROP COLUMN `modificationDate`,
	DROP COLUMN `userOwner`,
	DROP COLUMN `userModification`,
	DROP COLUMN `allowInherit`,
	DROP COLUMN `allowVariants`,
	DROP COLUMN `parentClass`,
	DROP COLUMN `useTraits`,
	DROP COLUMN `icon`,
	DROP COLUMN `previewUrl`,
	DROP COLUMN `propertyVisibility`,
	DROP COLUMN `showVariants`,
	DROP COLUMN `group`;
");
