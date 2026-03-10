<?php

namespace Bitrix\MpBuilder;

use Bitrix\Main\Web\Json;
use Bitrix\MpBuilder\Util\ExcludedFiles;

class Updates
{
	private const EXCLUSIONS_FILE = 'exclusions.json';
	private const DESCRIPTION_FILE = 'description.ru';
	private const UPDATER_FILE = 'updater.php';
	private const VERSION_FILE = 'version.php';
	private const STRUCTURE_FILE = 'module-structure.json';

	private string $moduleId;
	private string $version;
	private string $rootPath;

	public function __construct(string $moduleId, string $version = '')
	{
		$this->moduleId = $moduleId;
		$this->version = $version;
		$this->updateRootPath();
	}

	public function setVersion(string $version): void
	{
		$this->version = $version;
		$this->updateRootPath();
	}

	public function getModuleId(): string
	{
		return $this->moduleId;
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

	public function isDevStrategyActive(): bool
	{
		return is_dir($_SERVER['DOCUMENT_ROOT'] . "/dev/updates/$this->moduleId");
	}

	public static function getAvailableVersions(string $moduleId): array
	{
		$baseDir = $_SERVER['DOCUMENT_ROOT'] . "/dev/updates/$moduleId";

		if (!is_dir($baseDir))
		{
			return [];
		}

		$versions = [];
		$dir = opendir($baseDir);

		if (!$dir)
		{
			return [];
		}

		while (false !== $item = readdir($dir))
		{
			if ($item === '.' || $item === '..')
			{
				continue;
			}

			if (is_dir("$baseDir/$item") && file_exists("$baseDir/$item/" . self::VERSION_FILE))
			{
				$versions[] = $item;
			}
		}

		closedir($dir);

		usort($versions, static fn(string $a, string $b) => version_compare($b, $a));

		return $versions;
	}

	public function loadExclusions(): void
	{
		$exclusions = [];

		$baseExclusions = $this->loadExclusionsFromFile($this->getBaseExclusionsPath());
		if (!empty($baseExclusions))
		{
			$exclusions = array_merge($exclusions, $baseExclusions);
		}

		$versionExclusions = $this->loadExclusionsFromFile($this->getFilePath(self::EXCLUSIONS_FILE));
		if (!empty($versionExclusions))
		{
			$exclusions = array_merge($exclusions, $versionExclusions);
		}

		if (empty($exclusions))
		{
			return;
		}

		$exclusions = array_unique($exclusions);
		ExcludedFiles::addExclusions($exclusions);
	}

	public function getDescription(): string
	{
		if (!$this->hasDescription())
		{
			return '';
		}

		return file_get_contents($this->getFilePath(self::DESCRIPTION_FILE)) ?: '';
	}

	public function getUpdater(): string
	{
		if (!$this->hasUpdater())
		{
			return '';
		}

		return file_get_contents($this->getFilePath(self::UPDATER_FILE)) ?: '';
	}

	public function hasDescription(): bool
	{
		return file_exists($this->getFilePath(self::DESCRIPTION_FILE));
	}

	public function hasUpdater(): bool
	{
		return file_exists($this->getFilePath(self::UPDATER_FILE));
	}

	public function hasExclusions(): bool
	{
		return file_exists($this->getFilePath(self::EXCLUSIONS_FILE));
	}

	public function saveVersionFile(string $content): bool
	{
		$this->ensureDir();

		return (bool)file_put_contents($this->getFilePath(self::VERSION_FILE), $content);
	}

	public function saveDescription(string $content): bool
	{
		$this->ensureDir();

		return (bool)file_put_contents($this->getFilePath(self::DESCRIPTION_FILE), $content);
	}

	public function saveUpdater(string $content): bool
	{
		$this->ensureDir();

		return (bool)file_put_contents($this->getFilePath(self::UPDATER_FILE), $content);
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
		return file_exists($this->getFilePath(self::STRUCTURE_FILE));
	}

	public function loadStructure(): ?array
	{
		if (!$this->hasStructure())
		{
			return null;
		}

		$content = file_get_contents($this->getFilePath(self::STRUCTURE_FILE));

		if (!$content)
		{
			return null;
		}

		try
		{
			return Json::decode($content);
		}
		catch (\Exception $e)
		{
			return null;
		}
	}

	public function loadPreviousStructure(): ?array
	{
		$baseDir = $_SERVER['DOCUMENT_ROOT'] . "/dev/updates/$this->moduleId";

		if (!is_dir($baseDir))
		{
			return null;
		}

		$versions = [];
		$dir = opendir($baseDir);

		if (!$dir)
		{
			return null;
		}

		while (false !== $item = readdir($dir))
		{
			if ($item === '.' || $item === '..')
			{
				continue;
			}

			if (is_dir("$baseDir/$item") && file_exists("$baseDir/$item/" . self::STRUCTURE_FILE))
			{
				$versions[] = $item;
			}
		}

		closedir($dir);

		if (empty($versions))
		{
			return null;
		}

		usort($versions, fn($a, $b) => version_compare($b, $a));

		foreach ($versions as $ver)
		{
			if (!$this->version || version_compare($ver, $this->version, '<'))
			{
				$content = file_get_contents("$baseDir/$ver/" . self::STRUCTURE_FILE);

				if ($content)
				{
					try
					{
						return Json::decode($content);
					}
					catch (\Exception $e)
					{
						continue;
					}
				}
			}
		}

		return null;
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

	private function getBaseExclusionsPath(): string
	{
		return $_SERVER['DOCUMENT_ROOT'] . "/dev/updates/$this->moduleId/" . self::EXCLUSIONS_FILE;
	}

	private function loadExclusionsFromFile(string $filePath): array
	{
		if (!file_exists($filePath))
		{
			return [];
		}

		$content = file_get_contents($filePath);
		if (!$content)
		{
			return [];
		}

		try
		{
			$data = Json::decode($content);
		}
		catch (\Exception $e)
		{
			return [];
		}

		if (!is_array($data) || !isset($data['exclude']) || !is_array($data['exclude']))
		{
			return [];
		}

		return $data['exclude'];
	}

	private function updateRootPath(): void
	{
		$this->rootPath = $_SERVER['DOCUMENT_ROOT'] . "/dev/updates/$this->moduleId/$this->version";
	}
}
