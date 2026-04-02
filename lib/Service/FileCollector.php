<?php

namespace Bitrix\MpBuilder\Service;

use Bitrix\MpBuilder\Module;
use Bitrix\MpBuilder\Util\ExcludedFiles;
use Bitrix\MpBuilder\Util\Filesystem;

class FileCollector
{
	public static function getAll(Module $module): array
	{
		$rootPath = $module->getRootDirPath();
		$allFiles = Filesystem::getFiles($rootPath, [], true);

		$result = [];

		foreach ($allFiles as $file)
		{
			if (ExcludedFiles::matches($file))
			{
				continue;
			}

			$hash = md5_file($rootPath . $file);

			if ($hash !== false)
			{
				$result[$file] = $hash;
			}
		}

		return $result;
	}
}
