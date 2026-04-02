<?php

namespace Bitrix\MpBuilder;

use Bitrix\Main\Web\Json;
use Bitrix\MpBuilder\Util\ExcludedFiles;

class DevVersionStorage
{
	private const EXCLUSIONS_FILE = 'exclusions.json';
	private const DESCRIPTION_FILE = 'description.ru';
	private const UPDATER_FILE = 'updater.php';
	private const VERSION_FILE = 'version.php';
	private const STRUCTURE_FILE = 'module-structure.json';

	private string $rootPath;

	public function __construct(
		public readonly string $moduleId,
		private string $version = '',
	)
	{
		$this->updateRootPath();
	}

	public static function isActive(string $moduleId): bool
	{
		return is_dir(self::buildBaseDirPath($moduleId));
	}

	public static function getAvailableVersions(string $moduleId): array
	{
		return self::getVersionsWithFile($moduleId, self::VERSION_FILE);
	}

	public function getVersion(): string
	{
		return $this->version;
	}

	public function getUpdatesPath(): string
	{
		return $this->rootPath;
	}

	public function exists(): bool
	{
		return is_dir($this->rootPath);
	}

	public function loadExclusions(): void
	{
		$exclusions = array_unique(array_merge(
			$this->loadExclusionsFromFile($this->getBaseDirPath() . '/' . self::EXCLUSIONS_FILE),
			$this->loadExclusionsFromFile($this->getFilePath(self::EXCLUSIONS_FILE)),
		));

		if (!empty($exclusions))
		{
			ExcludedFiles::addExclusions($exclusions);
		}
	}

	public function getDescription(): string
	{
		return $this->readFile(self::DESCRIPTION_FILE);
	}

	public function getUpdater(): string
	{
		return $this->readFile(self::UPDATER_FILE);
	}

	public function hasDescription(): bool
	{
		return $this->hasFile(self::DESCRIPTION_FILE);
	}

	public function hasUpdater(): bool
	{
		return $this->hasFile(self::UPDATER_FILE);
	}

	public function saveVersionFile(string $content): bool
	{
		return $this->saveFile(self::VERSION_FILE, $content);
	}

	public function saveDescription(string $content): bool
	{
		return $this->saveFile(self::DESCRIPTION_FILE, $content);
	}

	public function saveUpdater(string $content): bool
	{
		return $this->saveFile(self::UPDATER_FILE, $content);
	}

	public function saveStructure(array $files): bool
	{
		$this->ensureDir();

		$data = [
			'version' => $this->version,
			'files' => $files,
		];

		return (bool)file_put_contents(
			$this->getFilePath(self::STRUCTURE_FILE),
			json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
		);
	}

	public function hasStructure(): bool
	{
		return $this->hasFile(self::STRUCTURE_FILE);
	}

	public function loadStructure(): ?array
	{
		return $this->decodeJsonFile($this->getFilePath(self::STRUCTURE_FILE));
	}

	public function loadVersionData(): ?array
	{
		return self::includeVersionFile($this->getFilePath(self::VERSION_FILE));
	}

	public function loadPreviousVersionData(): ?array
	{
		foreach (self::getAvailableVersions($this->moduleId) as $ver)
		{
			if ($this->version && version_compare($ver, $this->version, '<'))
			{
				return self::includeVersionFile(
					$this->getBaseDirPath() . "/$ver/" . self::VERSION_FILE
				);
			}
		}

		return null;
	}

	private static function includeVersionFile(string $filePath): ?array
	{
		if (!file_exists($filePath))
		{
			return null;
		}

		$arModuleVersion = [];
		include $filePath;

		return $arModuleVersion;
	}

	public function loadPreviousStructure(): ?array
	{
		foreach (self::getVersionsWithFile($this->moduleId, self::STRUCTURE_FILE) as $ver)
		{
			if (!$this->version || version_compare($ver, $this->version, '<'))
			{
				$data = $this->decodeJsonFile($this->getBaseDirPath() . "/$ver/" . self::STRUCTURE_FILE);

				if ($data !== null)
				{
					return $data;
				}
			}
		}

		return null;
	}

	private static function getVersionsWithFile(string $moduleId, string $filename): array
	{
		$baseDir = self::buildBaseDirPath($moduleId);

		if (!is_dir($baseDir))
		{
			return [];
		}

		$dir = opendir($baseDir);

		if (!$dir)
		{
			return [];
		}

		$versions = [];

		while (false !== $item = readdir($dir))
		{
			if ($item !== '.' && $item !== '..' && is_dir("$baseDir/$item") && file_exists("$baseDir/$item/$filename"))
			{
				$versions[] = $item;
			}
		}

		closedir($dir);

		usort($versions, static fn(string $a, string $b) => version_compare($b, $a));

		return $versions;
	}

	private static function buildBaseDirPath(string $moduleId): string
	{
		return $_SERVER['DOCUMENT_ROOT'] . "/dev/updates/$moduleId";
	}

	private function saveFile(string $filename, string $content): bool
	{
		$this->ensureDir();

		return (bool)file_put_contents($this->getFilePath($filename), $content);
	}

	private function readFile(string $filename): string
	{
		return $this->hasFile($filename)
			? (file_get_contents($this->getFilePath($filename)) ?: '')
			: '';
	}

	private function hasFile(string $filename): bool
	{
		return file_exists($this->getFilePath($filename));
	}

	private function ensureDir(): void
	{
		if (!is_dir($this->rootPath) && !mkdir($this->rootPath, BX_DIR_PERMISSIONS, true) && !is_dir($this->rootPath))
		{
			throw new \RuntimeException(sprintf('Directory "%s" was not created', $this->rootPath));
		}
	}

	private function getFilePath(string $filename): string
	{
		return $this->rootPath . '/' . $filename;
	}

	private function getBaseDirPath(): string
	{
		return self::buildBaseDirPath($this->moduleId);
	}

	private function updateRootPath(): void
	{
		$this->rootPath = $this->getBaseDirPath() . "/$this->version";
	}

	private function decodeJsonFile(string $filePath): ?array
	{
		$content = is_file($filePath) ? file_get_contents($filePath) : false;

		if (!$content)
		{
			return null;
		}

		try
		{
			return Json::decode($content);
		}
		catch (\Exception)
		{
			return null;
		}
	}

	private function loadExclusionsFromFile(string $filePath): array
	{
		$data = $this->decodeJsonFile($filePath);

		return (is_array($data) && isset($data['exclude']) && is_array($data['exclude']))
			? $data['exclude']
			: [];
	}
}
