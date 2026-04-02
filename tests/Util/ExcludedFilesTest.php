<?php

namespace Bitrix\MpBuilder\Tests\Util;

use Bitrix\MpBuilder\Util\ExcludedFiles;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ExcludedFilesTest extends TestCase
{
	protected function setUp(): void
	{
		ExcludedFiles::resetCustom();
	}

	public function testGetAllContainsSystemAndCustom(): void
	{
		$all = ExcludedFiles::getAll();

		$this->assertContains('.git', $all);
		$this->assertContains('.DS_Store', $all);
		$this->assertContains('install/_version.php', $all);
		$this->assertContains('.gitignore', $all);
	}

	public function testAddExclusion(): void
	{
		ExcludedFiles::addExclusion('custom.txt');

		$this->assertContains('custom.txt', ExcludedFiles::getCustom());
		$this->assertContains('custom.txt', ExcludedFiles::getAll());
	}

	public function testAddExclusionNoDuplicates(): void
	{
		ExcludedFiles::addExclusion('dup.txt');
		ExcludedFiles::addExclusion('dup.txt');

		$count = array_count_values(ExcludedFiles::getCustom())['dup.txt'] ?? 0;
		$this->assertSame(1, $count);
	}

	public function testAddExclusions(): void
	{
		ExcludedFiles::addExclusions(['a.txt', 'b.txt']);

		$custom = ExcludedFiles::getCustom();
		$this->assertContains('a.txt', $custom);
		$this->assertContains('b.txt', $custom);
	}

	public function testRemoveExclusion(): void
	{
		ExcludedFiles::addExclusion('to_remove.txt');
		ExcludedFiles::removeExclusion('to_remove.txt');

		$this->assertNotContains('to_remove.txt', ExcludedFiles::getCustom());
		$this->assertNotContains('to_remove.txt', ExcludedFiles::getAll());
	}

	public function testResetCustom(): void
	{
		ExcludedFiles::addExclusion('temporary.txt');
		ExcludedFiles::resetCustom();

		$this->assertNotContains('temporary.txt', ExcludedFiles::getCustom());
	}

	public function testIsExcluded(): void
	{
		$this->assertTrue(ExcludedFiles::isExcluded('.git'));
		$this->assertTrue(ExcludedFiles::isExcluded('.DS_Store'));
		$this->assertFalse(ExcludedFiles::isExcluded('lib/Handler.php'));
	}

	public static function matchesProvider(): array
	{
		return [
			'system file .git' => ['.git', true],
			'system file .DS_Store' => ['.DS_Store', true],
			'system file .svn' => ['.svn', true],
			'default custom _version' => ['install/_version.php', true],
			'gitignore' => ['.gitignore', true],
			'README' => ['README.md', true],
			'LICENSE' => ['LICENSE', true],
			'normal php file' => ['lib/Handler.php', false],
			'normal js file' => ['install/js/app.js', false],
			'version.php is not excluded' => ['install/version.php', false],
		];
	}

	#[DataProvider('matchesProvider')]
	public function testMatches(string $filename, bool $expected): void
	{
		$this->assertSame($expected, ExcludedFiles::matches($filename));
	}

	public function testMatchesWithLeadingSlash(): void
	{
		$this->assertTrue(ExcludedFiles::matches('/install/_version.php'));
		$this->assertTrue(ExcludedFiles::matches('/.gitignore'));
	}

	public function testMatchesBasename(): void
	{
		$this->assertTrue(ExcludedFiles::matches('/some/deep/path/.DS_Store'));
		$this->assertTrue(ExcludedFiles::matches('/any/path/.gitignore'));
	}

	public function testMatchesWildcardPattern(): void
	{
		ExcludedFiles::addExclusion('*.log');

		$this->assertTrue(ExcludedFiles::matches('error.log'));
		$this->assertTrue(ExcludedFiles::matches('debug.log'));
		$this->assertFalse(ExcludedFiles::matches('error.txt'));
	}

	public function testMatchesQuestionMarkWildcard(): void
	{
		ExcludedFiles::addExclusion('file?.txt');

		$this->assertTrue(ExcludedFiles::matches('file1.txt'));
		$this->assertTrue(ExcludedFiles::matches('fileA.txt'));
		$this->assertFalse(ExcludedFiles::matches('file12.txt'));
	}

	public function testMatchesCustomExclusion(): void
	{
		ExcludedFiles::addExclusion('custom/path.php');

		$this->assertTrue(ExcludedFiles::matches('custom/path.php'));
		$this->assertTrue(ExcludedFiles::matches('/custom/path.php'));
	}

	public function testCacheInvalidatedOnAdd(): void
	{
		$before = ExcludedFiles::getAll();
		ExcludedFiles::addExclusion('new_file.txt');
		$after = ExcludedFiles::getAll();

		$this->assertNotContains('new_file.txt', $before);
		$this->assertContains('new_file.txt', $after);
	}

	public function testCacheInvalidatedOnRemove(): void
	{
		ExcludedFiles::addExclusion('temp.txt');
		$this->assertContains('temp.txt', ExcludedFiles::getAll());

		ExcludedFiles::removeExclusion('temp.txt');
		$this->assertNotContains('temp.txt', ExcludedFiles::getAll());
	}

	public function testCacheInvalidatedOnReset(): void
	{
		ExcludedFiles::addExclusion('will_be_reset.txt');
		$this->assertContains('will_be_reset.txt', ExcludedFiles::getAll());

		ExcludedFiles::resetCustom();
		$this->assertNotContains('will_be_reset.txt', ExcludedFiles::getAll());
	}

	public function testGetSystemReturnsExpectedFiles(): void
	{
		$system = ExcludedFiles::getSystem();

		$this->assertContains('.', $system);
		$this->assertContains('..', $system);
		$this->assertContains('.git', $system);
	}

	public function testMatchesCaseInsensitiveForWildcards(): void
	{
		ExcludedFiles::addExclusion('*.LOG');

		$this->assertTrue(ExcludedFiles::matches('error.log'));
		$this->assertTrue(ExcludedFiles::matches('error.LOG'));
	}
}
