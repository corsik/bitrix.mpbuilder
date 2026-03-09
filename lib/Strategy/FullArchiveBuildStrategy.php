<?php

namespace Bitrix\MpBuilder\Strategy;

use Bitrix\Main\Error;
use Bitrix\MpBuilder\Contract\BuildStrategyInterface;
use Bitrix\MpBuilder\Dto\BuildContext;
use Bitrix\MpBuilder\Dto\BuildResult;
use Bitrix\MpBuilder\Util\ExcludedFiles;
use Bitrix\MpBuilder\Util\Filesystem;

class FullArchiveBuildStrategy implements BuildStrategyInterface
{
	public function build(BuildContext $context): BuildResult
	{
		$result = new BuildResult();
		$module = $context->module;

		$tmpLastVersion = $module->getRootTmpDirPath() . '/.last_version';

		if (is_dir($module->getRootTmpDirPath()))
		{
			Filesystem::rmDir($module->getRootTmpDirPath());
		}

		if (!mkdir($tmpLastVersion, BX_DIR_PERMISSIONS, true) && !is_dir($tmpLastVersion))
		{
			throw new \RuntimeException(sprintf('Directory "%s" was not created', $tmpLastVersion));
		}

		$originalModuleFiles = Filesystem::getFiles($module->getRootDirPath(), [], true);

		foreach ($originalModuleFiles as $file)
		{
			if (ExcludedFiles::matches($file))
			{
				continue;
			}

			$fromFile = $module->getRootDirPath() . $file;
			$toFile = $tmpLastVersion . $file;
			$fileContents = file_get_contents($fromFile);

			if (!$fileContents)
			{
				$result->addError(new Error('Failed to read file: ' . $fromFile));
			}
			else
			{
				if (str_ends_with($file, '.php'))
				{
					$fileContents = Filesystem::toCP1251($fileContents);
				}

				if (!file_exists($dir = dirname($toFile)) && !mkdir($dir, BX_DIR_PERMISSIONS, true) && !is_dir($dir))
				{
					throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
				}

				if (!file_put_contents($toFile, $fileContents))
				{
					$result->addError(new Error('Failed to save file: ' . $toFile));
				}
				else
				{
					$result->addFile($file);
				}
			}
		}

		if ($result->isSuccess())
		{
			Filesystem::packFolder($tmpLastVersion, $module->getRootTmpDirPath());
		}

		return $result;
	}
}
