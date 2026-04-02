<?php

namespace Bitrix\MpBuilder\Tests\Service;

use Bitrix\MpBuilder\Service\StructureAnalyzer;
use PHPUnit\Framework\TestCase;

class StructureAnalyzerTest extends TestCase
{
	private const MODULE_ID = 'corsik.suggestions';

	public function testLegacyFormatWithBitrixPrefixPaths(): void
	{
		$previousStructure = [
			'version' => '26.0.1',
			'files' => [
				'bitrix/modules/corsik.suggestions/lib/Handler.php',
				'bitrix/modules/corsik.suggestions/install/css/style.css',
				'bitrix/modules/corsik.suggestions/install/js/app.js',
				'bitrix/modules/corsik.suggestions/install/db/mysql/install.sql',
			],
		];

		$currentFiles = [
			'/lib/Handler.php' => 'hash1',
			'/install/css/style.css' => 'hash2',
			'/install/js/app.js' => 'hash3',
			'/install/db/mysql/install.sql' => 'hash4',
			'/install/version.php' => 'hash5',
		];

		$result = StructureAnalyzer::analyze($previousStructure, $currentFiles, self::MODULE_ID);

		$this->assertEmpty($result['deletedFiles'], 'No files should be detected as deleted');
		$this->assertEmpty($result['changedInstallDirs'], 'No install dirs should be detected as changed');
		$this->assertSame('26.0.1', $result['prevVersion']);
	}

	public function testLegacyFormatDetectsNewFiles(): void
	{
		$previousStructure = [
			'version' => '26.0.1',
			'files' => [
				'bitrix/modules/corsik.suggestions/install/css/style.css',
			],
		];

		$currentFiles = [
			'/install/css/style.css' => 'hash1',
			'/install/css/new.css' => 'hash2',
			'/install/js/app.js' => 'hash3',
			'/install/version.php' => 'hash4',
		];

		$result = StructureAnalyzer::analyze($previousStructure, $currentFiles, self::MODULE_ID);

		$this->assertContains('css', $result['changedInstallDirs']);
		$this->assertContains('js', $result['changedInstallDirs']);
	}

	public function testLegacyFormatDetectsRenamedFileAsChanged(): void
	{
		$previousStructure = [
			'version' => '1.0.0',
			'files' => [
				'bitrix/modules/corsik.suggestions/install/css/style.css',
				'bitrix/modules/corsik.suggestions/install/db/mysql/install.sql',
				'bitrix/modules/corsik.suggestions/install/js/old.js',
			],
		];

		$currentFiles = [
			'/install/css/style.css' => 'hash1',
			'/install/db/mysql/install.sql' => 'hash2',
			'/install/js/new.js' => 'hash3',
			'/install/version.php' => 'hash4',
		];

		$result = StructureAnalyzer::analyze($previousStructure, $currentFiles, self::MODULE_ID);

		$this->assertContains('js', $result['changedInstallDirs'], 'js should be changed (new file)');
		$this->assertNotContains('css', $result['changedInstallDirs'], 'css should not be changed');
		$this->assertNotContains('db', $result['changedInstallDirs'], 'db should not be changed');
	}

	public function testDetectsDeletedFiles(): void
	{
		$previousStructure = [
			'version' => '1.0.0',
			'files' => [
				'bitrix/modules/corsik.suggestions/lib/OldClass.php',
				'bitrix/modules/corsik.suggestions/lib/Handler.php',
			],
		];

		$currentFiles = [
			'/lib/Handler.php' => 'hash1',
		];

		$result = StructureAnalyzer::analyze($previousStructure, $currentFiles, self::MODULE_ID);

		$this->assertSame(['/lib/OldClass.php'], $result['deletedFiles']);
	}

	public function testHashMapFormatDetectsModified(): void
	{
		$previousStructure = [
			'version' => '2.0.0',
			'files' => [
				'/install/css/style.css' => 'old_hash',
				'/install/js/app.js' => 'same_hash',
			],
		];

		$currentFiles = [
			'/install/css/style.css' => 'new_hash',
			'/install/js/app.js' => 'same_hash',
			'/install/version.php' => 'hash',
		];

		$result = StructureAnalyzer::analyze($previousStructure, $currentFiles, self::MODULE_ID);

		$this->assertContains('css', $result['changedInstallDirs'], 'css modified');
		$this->assertNotContains('js', $result['changedInstallDirs'], 'js unchanged');
	}

	public function testHashMapFormatWithBitrixPrefix(): void
	{
		$previousStructure = [
			'version' => '2.0.0',
			'files' => [
				'bitrix/modules/corsik.suggestions/install/css/style.css' => 'same_hash',
				'bitrix/modules/corsik.suggestions/install/js/app.js' => 'same_hash',
			],
		];

		$currentFiles = [
			'/install/css/style.css' => 'same_hash',
			'/install/js/app.js' => 'same_hash',
			'/install/version.php' => 'hash',
		];

		$result = StructureAnalyzer::analyze($previousStructure, $currentFiles, self::MODULE_ID);

		$this->assertEmpty($result['changedInstallDirs']);
		$this->assertEmpty($result['deletedFiles']);
	}

	public function testVersionPhpAlwaysSkipped(): void
	{
		$previousStructure = [
			'version' => '1.0.0',
			'files' => [],
		];

		$currentFiles = [
			'/install/version.php' => 'hash',
			'/install/js/app.js' => 'hash2',
		];

		$result = StructureAnalyzer::analyze($previousStructure, $currentFiles, self::MODULE_ID);

		$this->assertSame(['js'], $result['changedInstallDirs']);
	}

	public function testRealWorldLegacyScenario(): void
	{
		$previousStructure = [
			'version' => '26.0.1',
			'files' => [
				'bitrix/modules/corsik.suggestions/install/admin/corsik_suggestions_feedback.php',
				'bitrix/modules/corsik.suggestions/install/css/admin/admin.setup.css',
				'bitrix/modules/corsik.suggestions/install/css/corsik.suggestions.css',
				'bitrix/modules/corsik.suggestions/install/css/suggestions.min.css',
				'bitrix/modules/corsik.suggestions/install/db/mysql/install.sql',
				'bitrix/modules/corsik.suggestions/install/db/mysql/uninstall.sql',
				'bitrix/modules/corsik.suggestions/install/js/corsik.suggestions.js',
				'bitrix/modules/corsik.suggestions/install/js/suggestions.bundle.js',
				'bitrix/modules/corsik.suggestions/install/themes/.default/corsik.suggestions.css',
			],
		];

		$currentFiles = [
			'/install/admin/corsik_suggestions_index.php' => 'h1',
			'/install/css/admin/admin.setup.css' => 'h2',
			'/install/css/corsik.suggestions.css' => 'h3',
			'/install/css/suggestions.min.css' => 'h4',
			'/install/db/mysql/install.sql' => 'h5',
			'/install/db/mysql/uninstall.sql' => 'h6',
			'/install/js/suggestions.bundle.js' => 'h7',
			'/install/themes/.default/corsik.suggestions.css' => 'h8',
			'/install/version.php' => 'h9',
		];

		$result = StructureAnalyzer::analyze($previousStructure, $currentFiles, self::MODULE_ID);

		// admin: old file deleted, new file added → changed
		$this->assertContains('admin', $result['changedInstallDirs']);
		// css: all files same → NOT changed
		$this->assertNotContains('css', $result['changedInstallDirs']);
		// db: all files same → NOT changed
		$this->assertNotContains('db', $result['changedInstallDirs']);
		// js: corsik.suggestions.js deleted, suggestions.bundle.js unchanged → no new files in js/
		$this->assertNotContains('js', $result['changedInstallDirs']);
		// themes: same → NOT changed
		$this->assertNotContains('themes', $result['changedInstallDirs']);

		// Deleted files
		$this->assertContains('/install/admin/corsik_suggestions_feedback.php', $result['deletedFiles']);
		$this->assertContains('/install/js/corsik.suggestions.js', $result['deletedFiles']);
	}
}
