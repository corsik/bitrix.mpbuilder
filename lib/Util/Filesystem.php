<?php

namespace Bitrix\MpBuilder\Util;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Text\Encoding;

class Filesystem
{
	public static function isDot(string $item): bool
	{
		return $item === '.' || $item === '..';
	}

	public static function packFolder(string $archivePath, string $removePath): void
	{
		global $USER;

		$packarc = \CBXArchive::GetArchive("$archivePath.tar.gz");
		$packarc->SetOptions([
				"COMPRESS" => true,
				"STEP_TIME" => Option::get('fileman', 'archive_step_time', 30),
				"ADD_PATH" => false,
				"REMOVE_PATH" => $removePath,
				"CHECK_PERMISSIONS" => !$USER->IsAdmin(),
			]
		);
		$packarc->Pack([$archivePath]);
	}

	public static function getFiles(
		string $path,
		array $arFilter = [],
		bool $bAllFiles = false
	): array
	{
		$skipSet = array_flip(array_merge(['.', '..', '.svn', '.hg', '.git'], $arFilter));

		return self::collectFiles($path, strlen($path), $skipSet, $bAllFiles);
	}

	private static function collectFiles(string $path, int $baseLen, array $skipSet, bool $bAllFiles): array
	{
		$retVal = [];
		$dir = opendir($path);

		if (!$dir)
		{
			return $retVal;
		}

		while (false !== $item = readdir($dir))
		{
			if (isset($skipSet[$item]))
			{
				continue;
			}

			$f = $path . '/' . $item;

			if (is_dir($f))
			{
				array_push($retVal, ...self::collectFiles($f, $baseLen, $skipSet, $bAllFiles));
			}
			elseif ($bAllFiles || str_ends_with($f, '.php'))
			{
				$retVal[] = str_replace('\\', '/', substr($f, $baseLen));
			}
		}

		closedir($dir);

		return $retVal;
	}

	public static function rmDir(string $path): void
	{
		if (!is_dir($path))
		{
			return;
		}

		$dir = opendir($path);
		while (false !== $item = readdir($dir))
		{
			if (self::isDot($item))
			{
				continue;
			}

			$f = $path . '/' . $item;
			if (is_dir($path . '/' . $item))
			{
				self::rmDir($f);
			}
			else
			{
				unlink($f);
			}
		}
		closedir($dir);
		rmdir($path);
	}

	public static function getStringCharset(string $str): string
	{
		if (preg_match("/[\xe0\xe1\xe3-\xff]/", $str))
		{
			return 'cp1251';
		}

		$str0 = Encoding::convertEncoding($str, 'utf8', 'cp1251');
		if (preg_match("/[\xe0\xe1\xe3-\xff]/", $str0))
		{
			return 'utf8';
		}

		return 'ascii';
	}

	public static function prepareEncoding(): void
	{
		if (function_exists('mb_internal_encoding'))
		{
			mb_internal_encoding('ISO-8859-1');
		}
	}

	public static function toCP1251(string $content): string
	{
		if (self::getStringCharset($content) === 'utf8')
		{
			return Encoding::convertEncoding($content, 'utf8', 'cp1251');
		}

		return $content;
	}

	public static function toUTF8(string $content): string
	{
		if (self::getStringCharset($content) === 'cp1251')
		{
			return Encoding::convertEncoding($content, 'cp1251', 'utf8');
		}

		return $content;
	}
}
