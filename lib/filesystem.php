<?php

namespace Bitrix\MpBuilder;

use Bitrix\Main\Text\Encoding;

class Filesystem
{
    public static function packFolder(string $archivePath, string $removePath): void
    {
        global $USER;

        $packarc = \CBXArchive::GetArchive("$archivePath.tar.gz");
        $packarc->SetOptions([
                "COMPRESS" => true,
                "STEP_TIME" => \COption::GetOptionString("fileman", "archive_step_time", 30),
                "ADD_PATH" => false,
                "REMOVE_PATH" => $removePath,
                "CHECK_PERMISSIONS" => $USER->IsAdmin() ? false : true,
            ]
        );
        $packarc->Pack([$archivePath]);
    }

    public static function getFiles(string $path, array $arFilter = [], bool $bAllFiles = false, bool $recursive = false): array
    {
        static $len;
        if (!$recursive || !$len) {
            $len = strlen($path);
        }

        $retVal = [];
        if ($dir = opendir($path)) {
            while (false !== $item = readdir($dir)) {

                if (in_array($item, array_merge(['.', '..', '.svn', '.hg', '.git'], $arFilter))) {
                    continue;
                }

                if (is_dir($f = $path . '/' . $item)) {
                    $retVal = array_merge($retVal, Filesystem::getFiles($f, $arFilter, $bAllFiles, true));
                } else {
                    if ($bAllFiles || substr($f, -4) == '.php')
                        $retVal[] = str_replace('\\', '/', substr($f, $len));
                }
            }

            closedir($dir);
        }

        return $retVal;
    }

    public static function rmDir(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $dir = opendir($path);
        while (false !== $item = readdir($dir)) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            $f = $path . '/' . $item;
            if (is_dir($path . '/' . $item)) {
                Filesystem::rmDir($f);
            } else {
                unlink($f);
            }
        }
        closedir($dir);
        rmdir($path);
    }

    public static function getStringCharset($str): string
    {
        if (preg_match("/[\xe0\xe1\xe3-\xff]/", $str)) {
            return 'cp1251';
        }

        $str0 = Encoding::convertEncoding($str, 'utf8', 'cp1251');
        if (preg_match("/[\xe0\xe1\xe3-\xff]/", $str0, $regs)) {
            return 'utf8';
        }

        return 'ascii';
    }
}
