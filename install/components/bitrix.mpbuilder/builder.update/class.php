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
use Bitrix\MpBuilder\Updates;
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
				],
			],
			'loadDevVersion' => [
				'prefilters' => [
					new ActionFilter\Csrf(),
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

		$updatesManager = new Updates($moduleId, $nextVersion);

		$description = '';
		if ($updatesManager->hasDescription())
		{
			$description = Filesystem::toUTF8($updatesManager->getDescription());
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
			'isDevStrategyActive' => $updatesManager->isDevStrategyActive(),
			'devVersions' => $updatesManager->isDevStrategyActive()
				? Updates::getAvailableVersions($moduleId)
				: [],
		];
	}

	public function prepareUpdateAction(
		string $moduleId,
		string $version,
		string $components = '',
		string $namespace = ''
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
		string $storeVersion = '',
		string $components = '',
		string $namespace = ''
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

		$strategy = BuildStrategyFactory::createForUpdate($updatesManager);
		$context = new BuildContext($moduleBuilder, $version, $versionContent, $description, $updater, $arModuleVersion);
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
		$isDevStrategy = $updatesManager->isDevStrategyActive();

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
		$updatesManager = new Updates($moduleId, $version);

		if (!$updatesManager->isDevStrategyActive())
		{
			$this->errorCollection->setError(new Error('Dev strategy is not active'));

			return null;
		}

		$files = FileCollector::getAll($moduleBuilder);

		if (!$updatesManager->saveStructure($files))
		{
			$this->errorCollection->setError(new Error('Failed to save module-structure.json'));

			return null;
		}

		return [
			'count' => count($files),
			'path' => "/dev/updates/$moduleId/$version/module-structure.json",
		];
	}

	public function analyzeStructureAction(string $moduleId, string $version): ?array
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
		$updatesManager = new Updates($moduleId, $version);
		$updatesManager->loadExclusions();

		if (!$updatesManager->isDevStrategyActive())
		{
			$this->errorCollection->setError(new Error('Dev strategy is not active'));

			return null;
		}

		$previousStructure = $updatesManager->loadPreviousStructure();

		if ($previousStructure === null)
		{
			$this->errorCollection->setError(new Error('No previous module-structure.json found. Generate structure for the current version first.'));

			return null;
		}

		$prevVersion = $previousStructure['version'] ?? '';
		$prevFiles = $previousStructure['files'] ?? [];

		$allFiles = FileCollector::getAll($moduleBuilder);

		$currentLookup = array_flip($allFiles);
		$deletedFiles = array_values(array_filter($prevFiles, fn($f) => !isset($currentLookup[$f])));

		$arModuleVersion = $moduleBuilder->loadVersion();
		$timeFrom = strtotime($arModuleVersion['VERSION_DATE'] ?? '');

		$changedInstallDirs = [];

		foreach ($allFiles as $file)
		{
			if (!str_starts_with($file, '/install/') || $file === '/install/version.php')
			{
				continue;
			}

			if (filemtime($moduleBuilder->getRootDirPath() . $file) < $timeFrom)
			{
				continue;
			}

			$parts = explode('/', ltrim($file, '/'));

			if (count($parts) >= 3)
			{
				$changedInstallDirs[$parts[1]] = true;
			}
		}

		return [
			'prevVersion' => $prevVersion,
			'deletedFiles' => $deletedFiles,
			'changedInstallDirs' => array_keys($changedInstallDirs),
			'moduleId' => $moduleId,
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

		$updatesManager = new Updates($moduleId, $version);

		if (!$updatesManager->isDevStrategyActive())
		{
			$this->errorCollection->setError(new Error('Dev strategy is not active'));

			return null;
		}

		if (!$updatesManager->exists())
		{
			$this->errorCollection->setError(new Error('Version folder not found'));

			return null;
		}

		$description = '';
		if ($updatesManager->hasDescription())
		{
			$description = Filesystem::toUTF8($updatesManager->getDescription());
		}

		$updater = '';
		if ($updatesManager->hasUpdater())
		{
			$updater = $updatesManager->getUpdater();
		}

		return [
			'version' => $version,
			'description' => $description,
			'updater' => $updater,
			'hasStructure' => $updatesManager->hasStructure(),
		];
	}
}
