<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Error;
use Bitrix\MpBuilder\ExcludedFiles;
use Bitrix\MpBuilder\Filesystem;
use Bitrix\MpBuilder\Links;
use Bitrix\MpBuilder\Module;
use Bitrix\MpBuilder\Updates;

class BuilderUpdateComponent extends \Bitrix\MpBuilder\BaseBuilderComponent
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
			'prepareUpdate' => [
				'prefilters' => [
					new ActionFilter\Csrf(),
				],
			],
			'buildUpdate' => [
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
			'restoreVersion' => [
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
		$namespace = Option::get($moduleId, 'NAMESPACE', '');

		$hasComponents = file_exists($moduleBuilder->getRootDirComponentPath());
		$hasCustomNamespace = false;

		if ($hasComponents)
		{
			$componentDir = opendir($moduleBuilder->getRootDirComponentPath());
			if ($componentDir)
			{
				while (false !== $item = readdir($componentDir))
				{
					if (Filesystem::isDot($item) || !is_dir($moduleBuilder->getRootDirComponentPath() . '/' . $item))
					{
						continue;
					}

					if (file_exists($moduleBuilder->getRootDirComponentPath() . '/' . $item . '/component.php'))
					{
						$hasCustomNamespace = true;
						break;
					}
				}
				closedir($componentDir);
			}
		}

		$backupVersion = null;
		$backupVersionPath = $moduleBuilder->getBackupVersionPath();

		if (file_exists($backupVersionPath))
		{
			include($backupVersionPath);
			if (!empty($arModuleVersion['VERSION']) && $arModuleVersion['VERSION'] !== $nextVersion)
			{
				$backupVersion = $arModuleVersion['VERSION'];
			}
			$arModuleVersion = $moduleBuilder->loadVersion();
		}

		$updatesManager = new Updates($moduleId, $nextVersion);

		$description = '';
		if ($updatesManager->hasDescription())
		{
			$description = $updatesManager->getDescription();
		}
		elseif (file_exists($f = $moduleBuilder->getRootDirVersionPath($nextVersion) . '/description.ru'))
		{
			$description = Filesystem::toUTF8(file_get_contents($f));
		}

		$updater = '';
		if ($updatesManager->hasUpdater())
		{
			$updater = $updatesManager->getUpdater();
		}
		else
		{
			$samplePath = $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/bitrix.mpbuilder/samples/_updater.php';
			if (file_exists($samplePath))
			{
				$updater = file_get_contents($samplePath);
				$updater = str_replace(['{MODULE_ID}', '{NAMESPACE}'], [
					$moduleId,
					$hasCustomNamespace ? $namespace : ''
				], $updater);
			}
		}

		return [
			'moduleId' => $moduleId,
			'version' => $arModuleVersion['VERSION'] ?? '',
			'versionDate' => $arModuleVersion['VERSION_DATE'] ?? '',
			'nextVersion' => $nextVersion,
			'namespace' => $namespace,
			'hasComponents' => $hasComponents,
			'hasCustomNamespace' => $hasCustomNamespace,
			'backupVersion' => $backupVersion,
			'description' => $description,
			'updater' => $updater,
		];
	}

	public function prepareUpdateAction(
		string $moduleId,
		string $version,
		bool $components = false,
		string $namespace = ''
	): ?array
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

		if (!$version)
		{
			$this->errorCollection->setError(new Error('Version is required'));

			return null;
		}

		$moduleBuilder = new Module($moduleId);
		$arModuleVersion = $moduleBuilder->loadVersion();

		$updatesManager = new Updates($moduleId, $version);
		$updatesManager->loadExclusions();

		$timeFrom = strtotime($arModuleVersion['VERSION_DATE'] ?? '');
		$originalModuleFiles = Filesystem::getFiles($moduleBuilder->getRootDirPath(), [], true);

		$includedFiles = [];
		$excludedFiles = [];
		$skippedByDate = 0;

		foreach ($originalModuleFiles as $file)
		{
			$fromFile = $moduleBuilder->getRootDirPath() . $file;

			if (ExcludedFiles::matches($file))
			{
				$excludedFiles[] = $file;
				continue;
			}

			if ($file === '/install/version.php')
			{
				$includedFiles[] = $file;
				continue;
			}

			if (filemtime($fromFile) < $timeFrom)
			{
				$skippedByDate++;
				continue;
			}

			$includedFiles[] = $file;
		}

		$hasComponentSync = $components && file_exists($moduleBuilder->getRootDirComponentPath());

		return [
			'includedFiles' => $includedFiles,
			'excludedFiles' => $excludedFiles,
			'skippedByDate' => $skippedByDate,
			'includedCount' => count($includedFiles),
			'excludedCount' => count($excludedFiles),
			'hasComponentSync' => $hasComponentSync,
			'versionDate' => $arModuleVersion['VERSION_DATE'] ?? '',
		];
	}

	public function buildUpdateAction(
		string $moduleId,
		string $version,
		string $description,
		string $updater = '',
		bool $storeVersion = false,
		bool $components = false,
		string $namespace = ''
	): ?array
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

		if (!$version)
		{
			$this->errorCollection->setError(new Error('Version is required'));

			return null;
		}

		if (!$description)
		{
			$this->errorCollection->setError(new Error('Description is required'));

			return null;
		}

		$errors = [];
		$fileList = [];

		$moduleBuilder = new Module($moduleId);
		$arModuleVersion = $moduleBuilder->loadVersion();
		$bitrixComponentRootPath = $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/components';

		$configVersion = $version ?: VersionUp($arModuleVersion['VERSION'] ?? '');
		$updatesManager = new Updates($moduleId, $configVersion);
		$updatesManager->loadExclusions();

		$bCustomNameSpace = !empty($namespace);
		if ($bCustomNameSpace)
		{
			$namespace = str_replace(['/', '\\', ' '], '', $namespace);
			Option::set($moduleId, 'NAMESPACE', $namespace);
		}

		$versionContent = $moduleBuilder->getContextVersion($version);

		if ($storeVersion)
		{
			rename($moduleBuilder->getRootFileVersionPath(), $moduleBuilder->getBackupVersionPath());

			if (!file_put_contents($moduleBuilder->getRootFileVersionPath(), $versionContent))
			{
				$errors[] = 'Failed to write version file: ' . $moduleBuilder->getRootFileVersionPath();
			}
		}

		if (empty($errors))
		{
			if (is_dir($moduleBuilder->getRootTmpDirPath()))
			{
				Filesystem::rmDir($moduleBuilder->getRootTmpDirPath());
			}

			$versionDir = $moduleBuilder->getRootDirVersionPath($version);
			if (!mkdir($versionDir, BX_DIR_PERMISSIONS, true) && !is_dir($versionDir))
			{
				throw new \RuntimeException(sprintf('Directory "%s" was not created', $versionDir));
			}

			Filesystem::prepareEncoding();

			if ($components && file_exists($moduleBuilder->getRootDirComponentPath()))
			{
				$this->copyComponents($moduleBuilder, $bitrixComponentRootPath, $bCustomNameSpace, $namespace, $errors);
			}

			$originalModuleFiles = Filesystem::getFiles($moduleBuilder->getRootDirPath(), [], true);
			$timeFrom = strtotime($arModuleVersion['VERSION_DATE'] ?? '');
			$tmpDirStrLen = strlen($moduleBuilder->getRootTmpDirPath());

			foreach ($originalModuleFiles as $file)
			{
				$fromFile = $moduleBuilder->getRootDirPath() . $file;
				$toFile = $moduleBuilder->getRootDirVersionPath($version) . $file;

				if (ExcludedFiles::matches($file))
				{
					continue;
				}

				if ($file === '/install/version.php')
				{
					if ($storeVersion && !file_put_contents($fromFile, $versionContent))
					{
						$errors[] = 'Failed to update version: ' . $fromFile;
					}

					if (!file_exists($dir = dirname($toFile)) && !mkdir($dir, BX_DIR_PERMISSIONS, true) && !is_dir($dir))
					{
						throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
					}

					if (!file_put_contents($toFile, $versionContent))
					{
						$errors[] = 'Failed to write version: ' . $toFile;
					}
					else
					{
						$fileList[] = substr($toFile, $tmpDirStrLen);
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
						$fileList[] = substr($toFile, $tmpDirStrLen);
					}
				}
			}

			if (empty($errors))
			{
				$descriptionFilePath = $moduleBuilder->getRootDirVersionPath($version) . '/description.ru';
				$descriptionContent = Filesystem::toCP1251($description);

				if (!file_put_contents($descriptionFilePath, $descriptionContent))
				{
					$errors[] = 'Failed to write description: ' . $descriptionFilePath;
				}
				else
				{
					$fileList[] = substr($descriptionFilePath, $tmpDirStrLen);
				}
			}

			if (empty($errors) && ($str = trim($updater)))
			{
				$updaterFilePath = $moduleBuilder->getRootDirVersionPath($version) . '/updater.php';

				if (!file_put_contents($updaterFilePath, $str))
				{
					$errors[] = 'Failed to save updater: ' . $updaterFilePath;
				}
				else
				{
					$fileList[] = substr($updaterFilePath, $tmpDirStrLen);
				}
			}

			if (empty($errors))
			{
				Filesystem::packFolder($moduleBuilder->getRootDirVersionPath($version), $moduleBuilder->getRootTmpDirPath());
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

		$archivePath = $moduleBuilder->getTmpDirPath() . '/' . $version . '.tar.gz';

		return [
			'filemanLink' => Links::filemanFolder($moduleBuilder->getTmpDirPath()),
			'downloadLink' => Links::downloadFile($archivePath),
			'archivePath' => $archivePath,
			'marketplaceLink' => Links::marketplaceDeploy($moduleId),
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

	public function restoreVersionAction(string $moduleId): ?array
	{
		if (!$this->checkAdmin())
		{
			return null;
		}

		$moduleId = Module::sanitizeId($moduleId);
		$moduleBuilder = new Module($moduleId);
		$backupPath = $moduleBuilder->getBackupVersionPath();

		if (!file_exists($backupPath))
		{
			$this->errorCollection->setError(new Error('Backup version not found'));

			return null;
		}

		rename($backupPath, $moduleBuilder->getRootFileVersionPath());

		$arModuleVersion = $moduleBuilder->loadVersion();

		return [
			'success' => true,
			'version' => $arModuleVersion['VERSION'] ?? '',
			'versionDate' => $arModuleVersion['VERSION_DATE'] ?? '',
		];
	}

	private function copyComponents(
		Module $moduleBuilder,
		string $bitrixComponentRootPath,
		bool $bCustomNameSpace,
		string $namespace,
		array &$errors
	): void
	{
		$ar = [];
		$componentDir = opendir($moduleBuilder->getRootDirComponentPath());

		if (!$componentDir)
		{
			return;
		}

		if ($bCustomNameSpace)
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
				if (Filesystem::isDot($item) || !is_dir($path0 = $moduleBuilder->getRootDirComponentPath() . '/' . $item))
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
			$to = $moduleBuilder->getRootDirComponentPath() . ($bCustomNameSpace ? preg_replace('#^/[^/]+#', '', $file) : $file);

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
	}
}
