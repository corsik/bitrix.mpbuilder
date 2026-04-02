<?php

namespace Bitrix\MpBuilder\Tests\Service;

use Bitrix\MpBuilder\Service\UpdaterGenerator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UpdaterGeneratorTest extends TestCase
{
	private const MODULE_ID = 'corsik.suggestions';

	public static function buildDeleteFilePathsProvider(): array
	{
		return [
			'kernel file' => [
				['/lib/Handler.php'],
				['modules/corsik.suggestions/lib/Handler.php'],
			],
			'install public dir file' => [
				['/install/js/app.js'],
				['modules/corsik.suggestions/install/js/app.js', 'js/app.js'],
			],
			'install non-public dir file' => [
				['/install/version.php'],
				['modules/corsik.suggestions/install/version.php'],
			],
			'install admin file' => [
				['/install/admin/page.php'],
				['modules/corsik.suggestions/install/admin/page.php', 'admin/page.php'],
			],
		];
	}

	public function testEmptyUpdaterWithNoChanges(): void
	{
		$result = UpdaterGenerator::applyAutoBlock("<?php\n?>", self::MODULE_ID, [], []);

		$this->assertStringContainsString('// @mpbuilder-auto-start', $result);
		$this->assertStringContainsString('// @mpbuilder-auto-end', $result);
		$this->assertStringNotContainsString('CopyFiles', $result);
		$this->assertStringNotContainsString('filesToDelete', $result);
	}

	public function testCopyFilesGenerated(): void
	{
		$result = UpdaterGenerator::applyAutoBlock(
			"<?php\n?>",
			self::MODULE_ID,
			['js', 'css'],
			[],
		);

		$this->assertStringContainsString('$updater->CopyFiles("install/js", "js")', $result);
		$this->assertStringContainsString('$updater->CopyFiles("install/css", "css")', $result);
		$this->assertStringContainsString("IsModuleInstalled('corsik.suggestions')", $result);
	}

	public function testDeleteFilesGenerated(): void
	{
		$result = UpdaterGenerator::applyAutoBlock(
			"<?php\n?>",
			self::MODULE_ID,
			[],
			['/lib/OldClass.php', '/install/js/old.js'],
			'1.0.0',
		);

		$this->assertStringContainsString('modules/corsik.suggestions/lib/OldClass.php', $result);
		$this->assertStringContainsString('modules/corsik.suggestions/install/js/old.js', $result);
		$this->assertStringContainsString('js/old.js', $result);
		$this->assertStringContainsString('Files removed since 1.0.0', $result);
		$this->assertStringContainsString('canUpdateKernel', $result);
	}

	public function testPublicDirCopiesForDeletedInstallFiles(): void
	{
		$result = UpdaterGenerator::applyAutoBlock(
			"<?php\n?>",
			self::MODULE_ID,
			[],
			['/install/admin/page.php', '/install/components/comp/class.php', '/lib/Internal.php'],
		);

		// admin is a PUBLIC_DIR — should generate public copy
		$this->assertStringContainsString("'admin/page.php'", $result);
		// components is a PUBLIC_DIR
		$this->assertStringContainsString("'components/comp/class.php'", $result);
		// lib is NOT under /install/ — no public copy
		$this->assertStringNotContainsString("'lib/Internal.php'", $result);
		// but module path should exist
		$this->assertStringContainsString("'modules/corsik.suggestions/lib/Internal.php'", $result);
	}

	public function testMergesWithExistingCopyDirs(): void
	{
		$existingUpdater = <<<'PHP'
<?php
if (IsModuleInstalled('corsik.suggestions'))
{
	$updater->CopyFiles("install/admin", "admin");
}
?>
PHP;

		$result = UpdaterGenerator::applyAutoBlock(
			$existingUpdater,
			self::MODULE_ID,
			['js'],
			[],
		);

		// Both old and new dirs should be present
		$this->assertStringContainsString('CopyFiles("install/admin", "admin")', $result);
		$this->assertStringContainsString('CopyFiles("install/js", "js")', $result);
	}

	public function testMergesWithExistingDeleteFiles(): void
	{
		$existingUpdater = <<<'PHP'
<?php
if ($updater->canUpdateKernel())
{
	$filesToDelete = [
		'modules/corsik.suggestions/lib/Legacy.php',
	];

	foreach ($filesToDelete as $fileName)
	{
		CUpdateSystem::deleteDirFilesEx(
			$_SERVER['DOCUMENT_ROOT'] . $updater->kernelPath . '/' . $fileName
		);
	}
}
?>
PHP;

		$result = UpdaterGenerator::applyAutoBlock(
			$existingUpdater,
			self::MODULE_ID,
			[],
			['/lib/Another.php'],
		);

		// Both old and new deleted files
		$this->assertStringContainsString("'modules/corsik.suggestions/lib/Legacy.php'", $result);
		$this->assertStringContainsString("'modules/corsik.suggestions/lib/Another.php'", $result);
	}

	public function testReplacesExistingAutoBlock(): void
	{
		$existingUpdater = <<<'PHP'
<?php
// some manual code

// @mpbuilder-auto-start
if (IsModuleInstalled('corsik.suggestions'))
{
	$updater->CopyFiles("install/old", "old");
}
// @mpbuilder-auto-end
?>
PHP;

		$result = UpdaterGenerator::applyAutoBlock(
			$existingUpdater,
			self::MODULE_ID,
			['js'],
			[],
		);

		// Old auto-block content removed
		$this->assertStringNotContainsString('install/old', $result);
		// New content present
		$this->assertStringContainsString('install/js', $result);
		// Manual code preserved
		$this->assertStringContainsString('// some manual code', $result);
		// Only one auto-block
		$this->assertSame(1, substr_count($result, '// @mpbuilder-auto-start'));
	}

	public function testAutoBlockInsertedBeforeClosingTag(): void
	{
		$result = UpdaterGenerator::applyAutoBlock(
			"<?php\n\$x = 1;\n?>",
			self::MODULE_ID,
			['js'],
			[],
		);

		$autoStart = strpos($result, '// @mpbuilder-auto-start');
		$closeTag = strpos($result, '?>');

		$this->assertNotFalse($autoStart);
		$this->assertNotFalse($closeTag);
		$this->assertLessThan($closeTag, $autoStart);
	}

	#[DataProvider('buildDeleteFilePathsProvider')]
	public function testBuildDeleteFilePaths(array $deletedFiles, array $expectedPaths): void
	{
		$result = UpdaterGenerator::applyAutoBlock(
			"<?php\n?>",
			self::MODULE_ID,
			[],
			$deletedFiles,
		);

		foreach ($expectedPaths as $path)
		{
			$this->assertStringContainsString("'$path'", $result, "Expected path '$path' not found in updater");
		}
	}

	public function testCopyDirsSorted(): void
	{
		$result = UpdaterGenerator::applyAutoBlock(
			"<?php\n?>",
			self::MODULE_ID,
			['js', 'admin', 'css'],
			[],
		);

		$adminPos = strpos($result, 'install/admin');
		$cssPos = strpos($result, 'install/css');
		$jsPos = strpos($result, 'install/js');

		$this->assertLessThan($cssPos, $adminPos);
		$this->assertLessThan($jsPos, $cssPos);
	}
}
