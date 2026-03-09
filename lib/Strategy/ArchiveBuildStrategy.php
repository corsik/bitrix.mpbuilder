<?php

namespace Bitrix\MpBuilder\Strategy;

use Bitrix\Main\Error;
use Bitrix\MpBuilder\Contract\BuildStrategyInterface;
use Bitrix\MpBuilder\Dto\BuildContext;
use Bitrix\MpBuilder\Dto\BuildResult;
use Bitrix\MpBuilder\Util\ExcludedFiles;
use Bitrix\MpBuilder\Util\Filesystem;

class ArchiveBuildStrategy implements BuildStrategyInterface
{
	public function build(BuildContext $context): BuildResult
	{
		$result = new BuildResult();
		$module = $context->module;

		if (is_dir($module->getRootTmpDirPath()))
		{
			Filesystem::rmDir($module->getRootTmpDirPath());
		}

		$versionDir = $module->getRootDirVersionPath($context->version);

		if (!mkdir($versionDir, BX_DIR_PERMISSIONS, true) && !is_dir($versionDir))
		{
			throw new \RuntimeException(sprintf('Directory "%s" was not created', $versionDir));
		}

		$originalModuleFiles = Filesystem::getFiles($module->getRootDirPath(), [], true);
		$timeFrom = $context->getTimeFrom();
		$tmpDirStrLen = strlen($module->getRootTmpDirPath());

		foreach ($originalModuleFiles as $file)
		{
			$fromFile = $module->getRootDirPath() . $file;
			$toFile = $module->getRootDirVersionPath($context->version) . $file;

			if (ExcludedFiles::matches($file))
			{
				continue;
			}

			if ($file === '/install/version.php')
			{
				if (!file_exists($dir = dirname($toFile)) && !mkdir($dir, BX_DIR_PERMISSIONS, true) && !is_dir($dir))
				{
					throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
				}

				if (!file_put_contents($toFile, $context->versionContent))
				{
					$result->addError(new Error('Failed to write version: ' . $toFile));
				}
				else
				{
					$result->addFile(substr($toFile, $tmpDirStrLen));
				}

				continue;
			}

			if (filemtime($fromFile) < $timeFrom)
			{
				continue;
			}

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
					$result->addFile(substr($toFile, $tmpDirStrLen));
				}
			}
		}

		if ($result->isSuccess())
		{
			$descriptionFilePath = $module->getRootDirVersionPath($context->version) . '/description.ru';

			if (!file_put_contents($descriptionFilePath, Filesystem::toCP1251($context->description)))
			{
				$result->addError(new Error('Failed to write description: ' . $descriptionFilePath));
			}
			else
			{
				$result->addFile(substr($descriptionFilePath, $tmpDirStrLen));
			}
		}

		if ($result->isSuccess() && ($str = trim($context->updater)))
		{
			$updaterFilePath = $module->getRootDirVersionPath($context->version) . '/updater.php';

			if (!file_put_contents($updaterFilePath, $str))
			{
				$result->addError(new Error('Failed to save updater: ' . $updaterFilePath));
			}
			else
			{
				$result->addFile(substr($updaterFilePath, $tmpDirStrLen));
			}
		}

		if ($result->isSuccess())
		{
			Filesystem::packFolder($module->getRootDirVersionPath($context->version), $module->getRootTmpDirPath());
		}

		return $result;
	}
}
