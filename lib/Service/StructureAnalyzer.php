<?php

namespace Bitrix\MpBuilder\Service;

use Bitrix\MpBuilder\Util\ExcludedFiles;
use Bitrix\MpBuilder\Util\StructureNormalizer;

class StructureAnalyzer
{
	/**
	 * @return array{deletedFiles: string[], changedInstallDirs: string[], prevVersion: string}
	 */
	public static function analyze(
		array $previousStructure,
		array $currentFiles,
		string $moduleId,
	): array
	{
		$prevVersion = $previousStructure['version'] ?? '';
		$prevFilesRaw = $previousStructure['files'] ?? [];
		$prevFilesRaw = StructureNormalizer::normalizePaths($prevFilesRaw, $moduleId);

		if (array_is_list($prevFilesRaw))
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
				static fn(string $_, string $path) => !ExcludedFiles::matches($path),
				ARRAY_FILTER_USE_BOTH,
			);
		}

		$deletedFiles = array_keys(array_diff_key($prevFiles, $currentFiles));

		$changedInstallDirs = [];

		foreach ($currentFiles as $file => $hash)
		{
			if (!str_starts_with($file, '/install/') || $file === '/install/version.php')
			{
				continue;
			}

			$isNew = !array_key_exists($file, $prevFiles);
			$isModified = !$isNew && $prevFiles[$file] !== null && $prevFiles[$file] !== $hash;

			if ($isNew || $isModified)
			{
				$parts = explode('/', ltrim($file, '/'));

				if (count($parts) >= 3)
				{
					$changedInstallDirs[$parts[1]] = true;
				}
			}
		}

		return [
			'deletedFiles' => $deletedFiles,
			'changedInstallDirs' => array_keys($changedInstallDirs),
			'prevVersion' => $prevVersion,
		];
	}
}
