<?php

namespace Bitrix\MpBuilder\Service;

use Bitrix\MpBuilder\Module;
use Bitrix\MpBuilder\Util\ExcludedFiles;
use Bitrix\MpBuilder\Util\Filesystem;

class FileCollector
{
	public static function getAll(Module $module): array
	{
		return array_values(array_filter(
			Filesystem::getFiles($module->getRootDirPath(), [], true),
			static fn($f) => !ExcludedFiles::matches($f)
		));
	}
}
