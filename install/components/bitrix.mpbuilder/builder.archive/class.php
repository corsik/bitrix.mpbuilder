<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\MpBuilder\BaseBuilderComponent;
use Bitrix\MpBuilder\Dto\BuildContext;
use Bitrix\MpBuilder\Factory\BuildStrategyFactory;
use Bitrix\MpBuilder\Module;
use Bitrix\MpBuilder\Util\ExcludedFiles;
use Bitrix\MpBuilder\Util\Filesystem;
use Bitrix\MpBuilder\Util\Links;

Loader::includeModule('bitrix.mpbuilder');

class BuilderArchiveComponent extends BaseBuilderComponent
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

		$moduleBuilder = new Module($moduleId);
		$versionContent = $moduleBuilder->getContextVersion($version ?: '');

		if ($version && !file_put_contents($moduleBuilder->getRootFileVersionPath(), $versionContent))
		{
			$this->errorCollection->setError(new Error('Failed to write version file: ' . $moduleBuilder->getRootFileVersionPath()));

			return null;
		}

		Filesystem::prepareEncoding();

		$strategy = BuildStrategyFactory::createForArchive();
		$context = new BuildContext($moduleBuilder, $version, $versionContent);
		$result = $strategy->build($context);

		if (!$result->isSuccess())
		{
			foreach ($result->getErrors() as $error)
			{
				$this->errorCollection->setError($error);
			}

			return null;
		}

		$archivePath = $moduleBuilder->getTmpDirPath() . '/.last_version.tar.gz';

		return [
			'filemanLink' => Links::filemanFolder($moduleBuilder->getTmpDirPath()),
			'downloadLink' => Links::downloadFile($archivePath),
			'archivePath' => $archivePath,
			'marketplaceLink' => Links::marketplaceEdit($moduleId),
			'fileList' => $result->getFileList(),
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
