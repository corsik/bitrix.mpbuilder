<?php

namespace Bitrix\MpBuilder\Service;

use Bitrix\MpBuilder\Module;
use Bitrix\MpBuilder\Util\Filesystem;

class ComponentSyncer
{
	public static function sync(Module $module, bool $hasCustomNamespace, string $namespace): array
	{
		$errors = [];
		$bitrixComponentRootPath = $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/components';
		$ar = [];

		$componentDir = opendir($module->getRootDirComponentPath());

		if (!$componentDir)
		{
			return $errors;
		}

		if ($hasCustomNamespace)
		{
			while (false !== $item = readdir($componentDir))
			{
				if (Filesystem::isDot($item))
				{
					continue;
				}

				if (is_dir($f = $bitrixComponentRootPath . '/' . $namespace . '/' . $item))
				{
					$arTmp = Filesystem::getFiles($f, [], true);

					foreach ($arTmp as $file)
					{
						$ar[] = '/' . $namespace . '/' . $item . $file;
					}
				}
			}
		}
		else
		{
			while (false !== $item = readdir($componentDir))
			{
				if (Filesystem::isDot($item) || !is_dir($path0 = $module->getRootDirComponentPath() . '/' . $item))
				{
					continue;
				}

				$dir0 = opendir($path0);

				if (!$dir0)
				{
					continue;
				}

				while (false !== $item0 = readdir($dir0))
				{
					if (Filesystem::isDot($item0) || !is_dir($f = $path0 . '/' . $item0))
					{
						continue;
					}

					$arTmp = Filesystem::getFiles($bitrixComponentRootPath . '/' . $item . '/' . $item0, [], true);

					foreach ($arTmp as $file)
					{
						$ar[] = '/' . $item . '/' . $item0 . $file;
					}
				}

				closedir($dir0);
			}
		}

		closedir($componentDir);

		foreach ($ar as $file)
		{
			$from = $bitrixComponentRootPath . $file;
			$to = $module->getRootDirComponentPath() . ($hasCustomNamespace ? preg_replace('#^/[^/]+#', '', $file) : $file);

			if (!file_exists($to) || filemtime($from) > filemtime($to))
			{
				if (!is_dir($d = dirname($to)) && !mkdir($d, BX_DIR_PERMISSIONS, true) && !is_dir($d))
				{
					$errors[] = 'Failed to create directory: ' . $d;
				}
				elseif (!copy($from, $to))
				{
					$errors[] = 'Failed to copy file: ' . $from;
				}
				else
				{
					touch($to, filemtime($from));
				}
			}
		}

		return $errors;
	}
}
