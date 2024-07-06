<?php

namespace Bitrix\MpBuilder;

use Bitrix\Main\Config\Option;

class Options
{
	public static function getBoolOptionByName(
		string      $moduleId,
		string      $name,
		?string     $default,
		string|bool $siteId = false
	): bool
	{
		return Option::get($moduleId, $name, $default, $siteId) === 'Y';
	}
}
