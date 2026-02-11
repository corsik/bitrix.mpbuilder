<?php

namespace Bitrix\MpBuilder;

use Bitrix\Main\Web\Json;

class Updates
{
	private const EXCLUSIONS_FILE = 'exclusions.json';
	private const DESCRIPTION_FILE = 'description.ru';
	private const UPDATER_FILE = 'updater.php';

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
