<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Error;
use Bitrix\MpBuilder\ExcludedFiles;
use Bitrix\MpBuilder\Filesystem;
use Bitrix\MpBuilder\Links;
use Bitrix\MpBuilder\Module;

class BuilderArchiveComponent extends \Bitrix\MpBuilder\BaseBuilderComponent
{
	public function configureActions(): array
	{
		return [
			'getModules' => [
				'prefilters' => [
					new ActionFilter\Csrf(),
				],
			],
			'getModuleInfo' => [
				'prefilters' => [
					new ActionFilter\Csrf(),
				],
			],
			'previewArchive' => [
				'prefilters' => [
					new ActionFilter\Csrf(),
				],
			],
			'buildArchive' => [
				'prefilters' => [
					new ActionFilter\Csrf(),
					new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
				],
			],
			'deleteTemp' => [
				'prefilters' => [
					new ActionFilter\Csrf(),
					new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
				],
			],
		];
	}

	public function getModulesAction(): ?array
	{
		if (!$this->checkAdmin())
		{
			return null;
		}

		return ['modules' => Module::getThirdPartyModules()];
	}

	public function getModuleInfoAction(string $moduleId): ?array
	{
		if (!$this->checkAdmin())
		{
			return null;
		}

		$moduleId = Module::sanitizeId($moduleId);

		if (!$moduleId || !Module::exists($moduleId))
		{
			$this->errorCollection->setError(new Error('Module not found'));

			return null;
		}

		$_SESSION['mpbuilder']['module_id'] = $moduleId;

		$moduleBuilder = new Module($moduleId);
		$arModuleVersion = $moduleBuilder->loadVersion();

		$nextVersion = VersionUp($arModuleVersion['VERSION'] ?? '');

		return [
			'moduleId' => $moduleId,
			'version' => $arModuleVersion['VERSION'] ?? '',
			'versionDate' => $arModuleVersion['VERSION_DATE'] ?? '',
			'nextVersion' => $nextVersion,
		];
	}

	public function previewArchiveAction(string $moduleId): ?array
	{
		if (!$this->checkAdmin())
		{
			return null;
		}

		$moduleId = Module::sanitizeId($moduleId);

		if (!$moduleId || !Module::exists($moduleId))
		{
			$this->errorCollection->setError(new Error('Module not found'));

			return null;
		}

		$moduleBuilder = new Module($moduleId);
		$originalModuleFiles = Filesystem::getFiles($moduleBuilder->getRootDirPath(), [], true);

		$includedFiles = [];
		$excludedFiles = [];

		foreach ($originalModuleFiles as $file)
		{
			if (ExcludedFiles::matches($file))
			{
				$excludedFiles[] = $file;
			}
			else
			{
				$includedFiles[] = $file;
			}
		}

		return [
			'includedFiles' => $includedFiles,
			'excludedFiles' => $excludedFiles,
			'includedCount' => count($includedFiles),
			'excludedCount' => count($excludedFiles),
		];
	}

	public function buildArchiveAction(string $moduleId, string $version = ''): ?array
	{
		if (!$this->checkAdmin())
		{
			return null;
		}

		$moduleId = Module::sanitizeId($moduleId);

		if (!$moduleId || !Module::exists($moduleId))
		{
			$this->errorCollection->setError(new Error('Module not found'));

			return null;
		}

		$errors = [];
		$fileList = [];

		$moduleBuilder = new Module($moduleId);

		$versionContent = $moduleBuilder->getContextVersion($version ?: '');

		if ($version && !file_put_contents($moduleBuilder->getRootFileVersionPath(), $versionContent))
		{
			$errors[] = 'Failed to write version file: ' . $moduleBuilder->getRootFileVersionPath();
		}

		if (empty($errors))
		{
			$tmpLastVersion = $moduleBuilder->getRootTmpDirPath() . '/.last_version';

			if (is_dir($moduleBuilder->getRootTmpDirPath()))
			{
				Filesystem::rmDir($moduleBuilder->getRootTmpDirPath());
			}

			if (!mkdir($tmpLastVersion, BX_DIR_PERMISSIONS, true) && !is_dir($tmpLastVersion))
			{
				throw new \RuntimeException(sprintf('Directory "%s" was not created', $tmpLastVersion));
			}

			Filesystem::prepareEncoding();

			$originalModuleFiles = Filesystem::getFiles($moduleBuilder->getRootDirPath(), [], true);

			foreach ($originalModuleFiles as $file)
			{
				if (ExcludedFiles::matches($file))
				{
					continue;
				}

				$fromFile = $moduleBuilder->getRootDirPath() . $file;
				$toFile = $tmpLastVersion . $file;
				$fileContents = file_get_contents($fromFile);

				if (!$fileContents)
				{
					$errors[] = 'Failed to read file: ' . $fromFile;
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
						$errors[] = 'Failed to save file: ' . $toFile;
					}
					else
					{
						$fileList[] = $file;
					}
				}
			}

			if (empty($errors))
			{
				Filesystem::packFolder($tmpLastVersion, $moduleBuilder->getRootTmpDirPath());
			}
		}

		if (!empty($errors))
		{
			foreach ($errors as $error)
			{
				$this->errorCollection->setError(new Error($error));
			}

			return null;
		}

		$archivePath = $moduleBuilder->getTmpDirPath() . '/.last_version.tar.gz';

		return [
			'filemanLink' => Links::filemanFolder($moduleBuilder->getTmpDirPath()),
			'downloadLink' => Links::downloadFile($archivePath),
			'archivePath' => $archivePath,
			'marketplaceLink' => Links::marketplaceEdit($moduleId),
			'fileList' => $fileList,
		];
	}

	public function deleteTempAction(string $moduleId): ?array
	{
		if (!$this->checkAdmin())
		{
			return null;
		}

		$moduleId = Module::sanitizeId($moduleId);
		$moduleBuilder = new Module($moduleId);

		if (is_dir($moduleBuilder->getRootTmpDirPath()))
		{
			Filesystem::rmDir($moduleBuilder->getRootTmpDirPath());
		}

		return ['success' => true];
	}
}
