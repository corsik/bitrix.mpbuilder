<?php

namespace Bitrix\MpBuilder;

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

	public static function getAll(): array
	{
		return array_merge(self::$systemFiles, self::$customExclusions);
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
		}
	}

	public static function resetCustom(): void
	{
		self::$customExclusions = self::DEFAULT_CUSTOM_EXCLUSIONS;
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

			if (
				self::matchesPattern($normalized, $pattern)
				|| self::matchesPattern($basename, $pattern)
			)
			{
				return true;
			}
		}

		return false;
	}

	private static function matchesPattern(string $filename, string $pattern): bool
	{
		$pattern = str_replace(
			['*', '?'],
			['.*', '.'],
			preg_quote($pattern, '#')
		);

		return (bool)preg_match('#^' . $pattern . '$#i', $filename);
	}
}

