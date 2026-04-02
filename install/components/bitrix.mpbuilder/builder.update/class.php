<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\MpBuilder\BaseBuilderComponent;
use Bitrix\MpBuilder\Dto\BuildContext;
use Bitrix\MpBuilder\Factory\BuildStrategyFactory;
use Bitrix\MpBuilder\Module;
use Bitrix\MpBuilder\Service\ComponentSyncer;
use Bitrix\MpBuilder\Service\FileCollector;
use Bitrix\MpBuilder\Service\UpdaterGenerator;
use Bitrix\MpBuilder\DevVersionStorage;
use Bitrix\MpBuilder\Util\ExcludedFiles;
use Bitrix\MpBuilder\Util\Filesystem;
use Bitrix\MpBuilder\Util\Links;

Loader::includeModule('bitrix.mpbuilder');

class BuilderUpdateComponent extends BaseBuilderComponent
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
			'generateStructure' => [
				'prefilters' => [
					new ActionFilter\Csrf(),
					new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
				],
			],
			'analyzeStructure' => [
				'prefilters' => [
					new ActionFilter\Csrf(),
					new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
				],
			],
			'loadDevVersion' => [
				'prefilters' => [
					new ActionFilter\Csrf(),
				],
			],
			'saveDescription' => [
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
		$hasCustomNamespace = $moduleBuilder->hasCustomNamespace();

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

		$storage = new DevVersionStorage($moduleId, $nextVersion);

		$description = '';
		if ($storage->hasDescription())
		{
			$description = Filesystem::toUTF8($storage->getDescription());
		}
		elseif (file_exists($f = $moduleBuilder->getRootDirVersionPath($nextVersion) . '/description.ru'))
		{
			$description = Filesystem::toUTF8(file_get_contents($f));
		}

		$updater = '';
		if ($storage->hasUpdater())
		{
			$updater = $storage->getUpdater();
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

		$isDevActive = DevVersionStorage::isActive($moduleId);

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
			'isDevStrategyActive' => $isDevActive,
			'devVersions' => $isDevActive
				? DevVersionStorage::getAvailableVersions($moduleId)
				: [],
		];
	}

	public function prepareUpdateAction(
		string $moduleId,
		string $version,
		string $components = '',
		string $namespace = '',
		string $customDateFrom = '',
	): ?array
	{
		if (!$this->checkAdmin())
		{
			return null;
		}

		$components = filter_var($components, FILTER_VALIDATE_BOOLEAN);
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

		$storage = new DevVersionStorage($moduleId, $version);
		$storage->loadExclusions();

		if ($customDateFrom !== '')
		{
			$timeFrom = strtotime($customDateFrom);
		}
		else
		{
			$arVersionForContext = DevVersionStorage::isActive($moduleId)
				? ($storage->loadPreviousVersionData() ?? $storage->loadVersionData() ?? $arModuleVersion)
				: $arModuleVersion;
			$timeFrom = strtotime($arVersionForContext['VERSION_DATE'] ?? '');
		}
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
			'versionDate' => $customDateFrom !== '' ? $customDateFrom : ($arVersionForContext['VERSION_DATE'] ?? ''),
		];
	}

	public function buildUpdateAction(
		string $moduleId,
		string $version,
		string $description,
		string $updater = '',
		string $storeVersion = '',
		string $components = '',
		string $namespace = '',
		string $customDateFrom = '',
		string $excludedFiles = '',
	): ?array
	{
		if (!$this->checkAdmin())
		{
			return null;
		}

		$storeVersion = filter_var($storeVersion, FILTER_VALIDATE_BOOLEAN);
		$components = filter_var($components, FILTER_VALIDATE_BOOLEAN);
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

		$moduleBuilder = new Module($moduleId);
		$arModuleVersion = $moduleBuilder->loadVersion();

		$configVersion = $version ?: VersionUp($arModuleVersion['VERSION'] ?? '');
		$storage = new DevVersionStorage($moduleId, $configVersion);
		$storage->loadExclusions();

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
				$this->errorCollection->setError(new Error('Failed to write version file: ' . $moduleBuilder->getRootFileVersionPath()));

				return null;
			}
		}

		Filesystem::prepareEncoding();

		if ($components && file_exists($moduleBuilder->getRootDirComponentPath()))
		{
			$syncErrors = ComponentSyncer::sync($moduleBuilder, $bCustomNameSpace, $namespace);

			foreach ($syncErrors as $error)
			{
				$this->errorCollection->setError(new Error($error));
			}

			if (!empty($syncErrors))
			{
				return null;
			}
		}

		$strategy = BuildStrategyFactory::createForUpdate($storage);
		$isDevActive = DevVersionStorage::isActive($moduleId);

		if ($customDateFrom !== '')
		{
			$arVersionForContext = ['VERSION_DATE' => $customDateFrom];
		}
		else
		{
			$arVersionForContext = $isDevActive
				? ($storage->loadPreviousVersionData() ?? $storage->loadVersionData() ?? $arModuleVersion)
				: $arModuleVersion;
		}

		if ($excludedFiles !== '')
		{
			$userExcluded = json_decode($excludedFiles, true);

			if (is_array($userExcluded))
			{
				ExcludedFiles::addExclusions(
					array_map(static fn(string $f) => ltrim($f, '/'), $userExcluded)
				);
			}
		}

		$context = new BuildContext($moduleBuilder, $version, $versionContent, $description, $updater, $arVersionForContext);
		$result = $strategy->build($context);

		if (!$result->isSuccess())
		{
			foreach ($result->getErrors() as $error)
			{
				$this->errorCollection->setError($error);
			}

			return null;
		}

		$archivePath = $moduleBuilder->getTmpDirPath() . '/' . $version . '.tar.gz';
		$isDevStrategy = $isDevActive;

		$returnResult = [
			'strategy' => $isDevStrategy ? 'dev' : 'archive',
			'filemanLink' => Links::filemanFolder($moduleBuilder->getTmpDirPath()),
			'downloadLink' => Links::downloadFile($archivePath),
			'archivePath' => $archivePath,
			'marketplaceLink' => Links::marketplaceDeploy($moduleId),
			'fileList' => $result->getFileList(),
		];

		if ($isDevStrategy)
		{
			$returnResult['devPath'] = "/dev/updates/$moduleId/$version";
		}

		return $returnResult;
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

	public function generateStructureAction(string $moduleId, string $version): ?array
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
		$storage = new DevVersionStorage($moduleId, $version);
		$storage->loadExclusions();

		if (!DevVersionStorage::isActive($moduleId))
		{
			$this->errorCollection->setError(new Error('Dev strategy is not active'));

			return null;
		}

		$files = FileCollector::getAll($moduleBuilder);

		if (!$storage->saveStructure($files))
		{
			$this->errorCollection->setError(new Error('Failed to save module-structure.json'));

			return null;
		}

		return [
			'count' => count($files),
			'path' => "/dev/updates/$moduleId/$version/module-structure.json",
		];
	}

	public function analyzeStructureAction(string $moduleId, string $version, string $updater = '', string $baseVersion = ''): ?array
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
		$storage = new DevVersionStorage($moduleId, $version);
		$storage->loadExclusions();

		if (!DevVersionStorage::isActive($moduleId))
		{
			$this->errorCollection->setError(new Error('Dev strategy is not active'));

			return null;
		}

		if ($baseVersion !== '')
		{
			$baseStorage = new DevVersionStorage($moduleId, $baseVersion);
			$previousStructure = $baseStorage->loadStructure();
		}
		else
		{
			$previousStructure = $storage->loadPreviousStructure();
		}

		if ($previousStructure === null)
		{
			$errorVersion = $baseVersion !== '' ? $baseVersion : 'previous';
			$this->errorCollection->setError(new Error("No module-structure.json found for version $errorVersion. Generate structure first."));

			return null;
		}

		$prevVersion = $previousStructure['version'] ?? '';
		$prevFilesRaw = $previousStructure['files'] ?? [];
		$isLegacyFormat = array_is_list($prevFilesRaw);

		if ($isLegacyFormat)
		{
			$prevFiles = [];

			foreach ($prevFilesRaw as $path)
			{
				if (!ExcludedFiles::matches($path))
				{
					$prevFiles[$path] = null;
				}
			}
		}
		else
		{
			$prevFiles = array_filter(
				$prevFilesRaw,
				static fn(string $hash, string $path) => !ExcludedFiles::matches($path),
				ARRAY_FILTER_USE_BOTH,
			);
		}

		$currentFiles = FileCollector::getAll($moduleBuilder);

		$deletedFiles = array_keys(array_diff_key($prevFiles, $currentFiles));

		$changedInstallDirs = [];

		foreach ($currentFiles as $file => $hash)
		{
			if (!str_starts_with($file, '/install/') || $file === '/install/version.php')
			{
				continue;
			}

			$isNew = !isset($prevFiles[$file]);
			$isModified = !$isNew && !$isLegacyFormat && $prevFiles[$file] !== $hash;

			if ($isNew || $isModified)
			{
				$parts = explode('/', ltrim($file, '/'));

				if (count($parts) >= 3)
				{
					$changedInstallDirs[$parts[1]] = true;
				}
			}
		}

		$resultUpdater = UpdaterGenerator::applyAutoBlock(
			$updater,
			$moduleId,
			array_keys($changedInstallDirs),
			$deletedFiles,
			$prevVersion,
		);

		return [
			'updater' => $resultUpdater,
		];
	}

	public function saveDescriptionAction(string $moduleId, string $version, string $description): ?array
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

		if (!DevVersionStorage::isActive($moduleId))
		{
			$this->errorCollection->setError(new Error('Dev strategy is not active'));

			return null;
		}

		$storage = new DevVersionStorage($moduleId, $version);

		if (!$storage->saveDescription($description))
		{
			$this->errorCollection->setError(new Error('Failed to save description'));

			return null;
		}

		return [
			'success' => true,
			'path' => "/dev/updates/$moduleId/$version/description.ru",
		];
	}

	public function loadDevVersionAction(string $moduleId, string $version): ?array
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

		$storage = new DevVersionStorage($moduleId, $version);

		if (!DevVersionStorage::isActive($moduleId))
		{
			$this->errorCollection->setError(new Error('Dev strategy is not active'));

			return null;
		}

		if (!$storage->exists())
		{
			$this->errorCollection->setError(new Error('Version folder not found'));

			return null;
		}

		$description = '';
		if ($storage->hasDescription())
		{
			$description = Filesystem::toUTF8($storage->getDescription());
		}

		$updater = '';
		if ($storage->hasUpdater())
		{
			$updater = $storage->getUpdater();
		}

		$versionData = $storage->loadVersionData();

		return [
			'version' => $version,
			'description' => $description,
			'updater' => $updater,
			'hasStructure' => $storage->hasStructure(),
			'versionDate' => $versionData['VERSION_DATE'] ?? '',
		];
	}
}
