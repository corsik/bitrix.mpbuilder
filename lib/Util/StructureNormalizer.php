<?php

namespace Bitrix\MpBuilder\Util;

class StructureNormalizer
{
	public static function normalizePaths(array $files, string $moduleId): array
	{
		$prefix = "bitrix/modules/$moduleId/";
		$prefixLen = strlen($prefix);

		$normalize = static function (string $path) use ($prefix, $prefixLen): string
		{
			if (str_starts_with($path, $prefix))
			{
				$path = substr($path, $prefixLen);
			}

			if ($path !== '' && $path[0] !== '/')
			{
				$path = '/' . $path;
			}

			return $path;
		};

		if (array_is_list($files))
		{
			return array_map($normalize, $files);
		}

		$normalized = [];

		foreach ($files as $path => $hash)
		{
			$normalized[$normalize($path)] = $hash;
		}

		return $normalized;
	}
}
