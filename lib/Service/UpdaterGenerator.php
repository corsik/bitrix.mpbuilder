<?php

namespace Bitrix\MpBuilder\Service;

class UpdaterGenerator
{
	private const AUTO_START = '// @mpbuilder-auto-start';
	private const AUTO_END = '// @mpbuilder-auto-end';

	private const PUBLIC_DIRS = [
		'components', 'js', 'css', 'admin', 'themes', 'images', 'tools',
	];

	public static function applyAutoBlock(
		string $currentUpdater,
		string $moduleId,
		array $changedInstallDirs,
		array $deletedFiles,
		string $prevVersion = '',
	): string
	{
		$codeForParsing = self::getCodeOutsideAutoBlock($currentUpdater);
		$existingDirs = self::parseExistingCopyDirs($codeForParsing);
		$existingDeleteFiles = self::parseExistingDeleteFiles($codeForParsing);

		$mergedDirs = array_unique(array_merge($existingDirs, $changedInstallDirs));
		sort($mergedDirs);

		$deleteFilePaths = self::buildDeleteFilePaths($deletedFiles, $moduleId);
		$mergedDeleteFiles = array_unique(array_merge($existingDeleteFiles, $deleteFilePaths));
		sort($mergedDeleteFiles);

		$cleanUpdater = self::removeOldBlocks($currentUpdater);
		$autoBlock = self::generateAutoBlock($moduleId, $mergedDirs, $mergedDeleteFiles, $prevVersion);

		return self::insertBeforeClose($cleanUpdater, $autoBlock);
	}

	private static function parseExistingCopyDirs(string $code): array
	{
		$dirs = [];

		if (preg_match_all(
			'/\$updater->CopyFiles\(\s*["\']install\/([^"\']+)["\']\s*,\s*["\'][^"\']*["\']\s*\)/s',
			$code,
			$matches
		))
		{
			$dirs = $matches[1];
		}

		return $dirs;
	}

	private static function parseExistingDeleteFiles(string $code): array
	{
		$files = [];

		if (preg_match_all('/\$filesToDelete\s*=\s*\[([\s\S]*?)]/s', $code, $matches))
		{
			foreach ($matches[1] as $block)
			{
				if (preg_match_all("/['\"]([^'\"]+)['\"]/", $block, $entryMatches))
				{
					array_push($files, ...$entryMatches[1]);
				}
			}
		}

		return $files;
	}

	private static function removeOldBlocks(string $code): string
	{
		$startPos = strpos($code, self::AUTO_START);
		$endPos = strpos($code, self::AUTO_END);

		if ($startPos !== false && $endPos !== false && $endPos > $startPos)
		{
			$code = rtrim(substr($code, 0, $startPos))
				. "\n"
				. substr($code, $endPos + strlen(self::AUTO_END));
		}

		$code = preg_replace(
			'/\n*if\s*\(\s*IsModuleInstalled\([^)]+\)\s*\)\s*\{[^}]*\$updater->CopyFiles[^}]*\}\n*/s',
			"\n",
			$code
		);

		$code = preg_replace(
			'/\n*if\s*\(\s*\$updater->canUpdateKernel\(\)\s*\)\s*\{[\s\S]*?\$filesToDelete[\s\S]*?^\}\n*/m',
			"\n",
			$code
		);

		return $code;
	}

	private static function generateAutoBlock(
		string $moduleId,
		array $copyDirs,
		array $deleteFiles,
		string $prevVersion,
	): string
	{
		$lines = [self::AUTO_START];

		if (!empty($copyDirs))
		{
			$lines[] = '';
			$lines[] = "if (IsModuleInstalled('$moduleId'))";
			$lines[] = '{';

			foreach ($copyDirs as $dir)
			{
				$lines[] = "\t\$updater->CopyFiles(\"install/$dir\", \"$dir\");";
			}

			$lines[] = '}';
		}

		if (!empty($deleteFiles))
		{
			$lines[] = '';
			$lines[] = 'if ($updater->canUpdateKernel())';
			$lines[] = '{';

			if ($prevVersion !== '')
			{
				$lines[] = "\t// Files removed since $prevVersion";
			}

			$lines[] = "\t\$filesToDelete = [";

			foreach ($deleteFiles as $file)
			{
				$lines[] = "\t\t'$file',";
			}

			$lines[] = "\t];";
			$lines[] = '';
			$lines[] = "\tforeach (\$filesToDelete as \$fileName)";
			$lines[] = "\t{";
			$lines[] = "\t\tCUpdateSystem::deleteDirFilesEx(";
			$lines[] = "\t\t\t\$_SERVER['DOCUMENT_ROOT'] . \$updater->kernelPath . '/' . \$fileName";
			$lines[] = "\t\t);";
			$lines[] = "\t}";
			$lines[] = '}';
		}

		$lines[] = self::AUTO_END;

		return implode("\n", $lines);
	}

	private static function buildDeleteFilePaths(array $deletedFiles, string $moduleId): array
	{
		$publicDirs = array_flip(self::PUBLIC_DIRS);
		$paths = [];

		foreach ($deletedFiles as $file)
		{
			$paths[] = "modules/$moduleId$file";

			if (str_starts_with($file, '/install/'))
			{
				$relPath = substr($file, strlen('/install/'));
				$topDir = explode('/', $relPath, 2)[0];

				if (isset($publicDirs[$topDir]))
				{
					$paths[] = $relPath;
				}
			}
		}

		return $paths;
	}

	private static function getCodeOutsideAutoBlock(string $code): string
	{
		$startPos = strpos($code, self::AUTO_START);
		$endPos = strpos($code, self::AUTO_END);

		if ($startPos !== false && $endPos !== false && $endPos > $startPos)
		{
			return substr($code, 0, $startPos) . substr($code, $endPos + strlen(self::AUTO_END));
		}

		return $code;
	}

	private static function insertBeforeClose(string $code, string $block): string
	{
		$code = rtrim($code);
		$closePos = strrpos($code, '?>');

		if ($closePos !== false)
		{
			return rtrim(substr($code, 0, $closePos))
				. "\n\n" . $block . "\n\n"
				. substr($code, $closePos);
		}

		return $code . "\n\n" . $block;
	}
}
