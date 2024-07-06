<?php

namespace Bitrix\MpBuilder;

use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Config\Option;

class Filesystem
{
	private static array $excludedFiles = ['.', '..', '.svn', '.hg', '.git', '.DS_Store'];

	public static function packFolder(string $archivePath, string $removePath): void
	{
		global $USER;

		$packarc = \CBXArchive::GetArchive("$archivePath.tar.gz");
		$packarc->SetOptions([
				"COMPRESS" => true,
				"STEP_TIME" => Option::get("fileman", 'archive_step_time', 30),
				"ADD_PATH" => false,
				"REMOVE_PATH" => $removePath,
				"CHECK_PERMISSIONS" => !$USER->IsAdmin(),
			]
		);
		$packarc->Pack([$archivePath]);
	}

	public static function getFiles(
		string $path,
		array  $arFilter = [],
		bool   $bAllFiles = false,
		bool   $recursive = false
	): array
	{
		static $len;
		if (!$recursive || !$len)
		{
			$len = strlen($path);
		}

		$retVal = [];
		if ($dir = opendir($path))
		{
			while (false !== $item = readdir($dir))
			{
				if (in_array($item, array_merge(self:: $excludedFiles, $arFilter), true))
				{
					continue;
				}

				$dirFiles = $path . '/' . $item;
				if (is_dir($dirFiles))
				{
					$retVal = [...$retVal, ...Filesystem::getFiles($dirFiles, $arFilter, $bAllFiles, true)];
				}
				else
				{
					if ($bAllFiles || substr($dirFiles, -4) === '.php')
					{
						$retVal[] = str_replace('\\', '/', substr($dirFiles, $len));
					}
				}
			}

			closedir($dir);
		}

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
			if ($item === '.' || $item === '..')
			{
				continue;
			}

			$f = $path . '/' . $item;
			if (is_dir($path . '/' . $item))
			{
				Filesystem::rmDir($f);
			}
			else
			{
				unlink($f);
			}
		}
		closedir($dir);
		rmdir($path);
	}

	public static function getStringCharset($str): string
	{
		if (preg_match("/[\xe0\xe1\xe3-\xff]/", $str))
		{
			return 'cp1251';
		}

		$str0 = Encoding::convertEncoding($str, 'utf8', 'cp1251');
		if (preg_match("/[\xe0\xe1\xe3-\xff]/", $str0, $regs))
		{
			return 'utf8';
		}

		return 'ascii';
	}
}
