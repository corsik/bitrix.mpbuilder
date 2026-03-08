<?php

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/prolog.php");

global $USER;
global $APPLICATION;

if (!$USER->IsAdmin())
{
	$APPLICATION->AuthForm();
}

\Bitrix\Main\Loader::includeModule('bitrix.mpbuilder');

IncludeModuleLangFile(__FILE__);

$APPLICATION->SetTitle(GetMessage("BITRIX_MPBUILDER_SAG_TRETIY_SOZDANIE"));
require($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/include/prolog_admin_after.php");

$APPLICATION->IncludeComponent(
	'bitrix.mpbuilder:builder.archive',
	'.default',
	[]
);

require($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/include/epilog_admin.php");
