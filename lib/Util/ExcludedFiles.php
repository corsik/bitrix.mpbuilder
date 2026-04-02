<?php

namespace Bitrix\MpBuilder\Util;

class ExcludedFiles
{
	private const DEFAULT_CUSTOM_EXCLUSIONS = [
		'install/_version.php',
		'.gitignore',
		'.gitkeep',
		'README.md',
		'LICENSE',
	];

	private static array $systemFiles = [
		'.',
		'..',
		'.svn',
		'.hg',
		'.git',
		'.gitignore',
		'.DS_Store',
	];

	private static array $customExclusions = self::DEFAULT_CUSTOM_EXCLUSIONS;

	private static ?array $mergedCache = null;

	public static function getAll(): array
	{
		return self::$mergedCache ??= array_merge(self::$systemFiles, self::$customExclusions);
	}

	public static function getSystem(): array
	{
		return self::$systemFiles;
	}

	public static function getCustom(): array
	{
		return self::$customExclusions;
	}

	public static function addExclusion(string $file): void
	{
		if (!in_array($file, self::$customExclusions, true))
		{
			self::$customExclusions[] = $file;
			self::$mergedCache = null;
		}
	}

	public static function addExclusions(array $files): void
	{
		foreach ($files as $file)
		{
			self::addExclusion($file);
		}
	}

	public static function removeExclusion(string $file): void
	{
		$key = array_search($file, self::$customExclusions, true);
		if ($key !== false)
		{
			unset(self::$customExclusions[$key]);
			self::$mergedCache = null;
		}
	}

	public static function resetCustom(): void
	{
		self::$customExclusions = self::DEFAULT_CUSTOM_EXCLUSIONS;
		self::$mergedCache = null;
	}

	public static function isExcluded(string $filename): bool
	{
		return in_array($filename, self::getAll(), true);
	}

	public static function matches(string $filename): bool
	{
		$filename = trim($filename);
		$normalized = ltrim($filename, '/');
		$basename = basename($filename);

		foreach (self::getAll() as $pattern)
		{
			$pattern = trim($pattern);

			if ($normalized === $pattern || $basename === $pattern)
			{
				return true;
			}

			if (str_contains($pattern, '*') || str_contains($pattern, '?'))
			{
				if (
					self::matchesPattern($normalized, $pattern)
					|| self::matchesPattern($basename, $pattern)
				)
				{
					return true;
				}
			}
		}

		return false;
	}

	private static function matchesPattern(string $filename, string $pattern): bool
	{
		$parts = preg_split('/([*?])/', $pattern, -1, PREG_SPLIT_DELIM_CAPTURE);
		$regex = '#^';

		foreach ($parts as $part)
		{
			$regex .= match ($part)
			{
				'*' => '.*',
				'?' => '.',
				default => preg_quote($part, '#'),
			};
		}

		$regex .= '$#i';

		return (bool)preg_match($regex, $filename);
	}
}
