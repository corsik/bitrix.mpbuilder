<?php
if (IsModuleInstalled('{MODULE_ID}')) {
    if (is_dir(dirname(__FILE__) . '/install/components')) {
        $updater->CopyFiles("install/components", "components/{NAMESPACE}");
    }

    if (is_dir(dirname(__FILE__) . '/install/js')) {
        $updater->CopyFiles("install/js", "js/{MODULE_ID}/");
    }

	if (is_dir(dirname(__FILE__) . '/install/css')) {
        $updater->CopyFiles("install/css", "css/{MODULE_ID}/");
    }
}


/*
//
// Sample database update
//
if($updater->CanUpdateDatabase())
{
	if($updater->TableExists("b_iblock_element_property"))
	{
		if(!$DB->IndexExists("b_iblock_element_property", array("VALUE_NUM", "IBLOCK_PROPERTY_ID")))
		{
			$updater->Query(array(
				"MySQL" => "CREATE INDEX ix_iblock_element_prop_num ON b_iblock_element_property(VALUE_NUM, IBLOCK_PROPERTY_ID)",
			));
		}
        }
}

*/
?>
