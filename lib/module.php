<?php

namespace Bitrix\MpBuilder;

class Module
{
	private string $moduleId;

	final public function __construct(string $moduleId)
	{
		$this->moduleId = $moduleId;
	}

	public static function sanitizeId(string $moduleId): string
	{
		return str_replace(['..', '/', '\\'], '', $moduleId);
	}

	public static function exists(string $moduleId): bool
	{
		return is_dir($_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/' . $moduleId);
	}

	public static function getThirdPartyModules(): array
	{
		$modules = [];
		$path = $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules';
		$dir = opendir($path);

		if ($dir)
		{
			while (false !== $item = readdir($dir))
			{
				if (Filesystem::isDot($item) || !is_dir($path . '/' . $item) || !str_contains($item, '.'))
				{
					continue;
				}

				$modules[] = $item;
			}
			closedir($dir);
		}

		sort($modules);

		return $modules;
	}

	public function getModuleId(): string
	{
		return $this->moduleId;
	}

	public function getDirPath(): string
	{
		return BX_ROOT . "/modules/$this->moduleId";
	}

	public function getRootDirPath(): string
	{
		return $_SERVER['DOCUMENT_ROOT'] . $this->getDirPath();
	}

	public function getTmpDirPath(): string
	{
		return BX_ROOT . "/tmp/$this->moduleId";
	}

	public function getRootTmpDirPath(): string
	{
		return $_SERVER['DOCUMENT_ROOT'] . $this->getTmpDirPath();
	}

	public function getRootFileVersionPath(): string
	{
		return $this->getRootDirPath() . "/install/version.php";
	}

	public function getRootDirVersionPath(string $version): string
	{
		return $this->getRootTmpDirPath() . '/' . $version;
	}

	public function getRootDirComponentPath(): string
	{
		return $this->getRootDirPath() . '/install/components';
	}

	public function getBackupVersionPath(): string
	{
		return $this->getRootDirPath() . '/install/_version.php';
	}

	public function loadVersion(): array
	{
		$arModuleVersion = [];

		if (file_exists($this->getRootFileVersionPath()))
		{
			include($this->getRootFileVersionPath());
		}

		return $arModuleVersion;
	}

	public function getContextVersion(string $version): string
	{
		return '<?php' . "\n" .
			'$arModuleVersion = [' . "\n" .
			'	"VERSION" => "' . EscapePHPString($version) . '",' . "\n" .
			'	"VERSION_DATE" => "' . date('Y-m-d H:i:s') . '"' . "\n" .
			'];' . "\n" .
			'?>';
	}
}
