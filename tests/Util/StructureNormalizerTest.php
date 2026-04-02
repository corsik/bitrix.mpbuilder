<?php

namespace Bitrix\MpBuilder\Tests\Util;

use Bitrix\MpBuilder\Util\StructureNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class StructureNormalizerTest extends TestCase
{
	private const MODULE_ID = 'corsik.suggestions';

	public static function hashMapProvider(): array
	{
		return [
			'already normalized' => [
				['/lib/Handler.php' => 'abc123', '/install/version.php' => 'def456'],
				['/lib/Handler.php' => 'abc123', '/install/version.php' => 'def456'],
			],
			'full bitrix paths' => [
				[
					'bitrix/modules/corsik.suggestions/lib/Handler.php' => 'abc123',
					'bitrix/modules/corsik.suggestions/install/version.php' => 'def456',
				],
				['/lib/Handler.php' => 'abc123', '/install/version.php' => 'def456'],
			],
			'without leading slash' => [
				['lib/Handler.php' => 'abc123', 'install/version.php' => 'def456'],
				['/lib/Handler.php' => 'abc123', '/install/version.php' => 'def456'],
			],
			'mixed formats' => [
				[
					'/lib/Handler.php' => 'aaa',
					'bitrix/modules/corsik.suggestions/install/js/app.js' => 'bbb',
					'options.php' => 'ccc',
				],
				[
					'/lib/Handler.php' => 'aaa',
					'/install/js/app.js' => 'bbb',
					'/options.php' => 'ccc',
				],
			],
			'empty array' => [
				[],
				[],
			],
		];
	}

	#[DataProvider('hashMapProvider')]
	public function testNormalizeHashMap(array $input, array $expected): void
	{
		$this->assertSame($expected, StructureNormalizer::normalizePaths($input, self::MODULE_ID));
	}

	public static function listProvider(): array
	{
		return [
			'already normalized list' => [
				['/lib/Handler.php', '/install/version.php'],
				['/lib/Handler.php', '/install/version.php'],
			],
			'full bitrix paths list' => [
				[
					'bitrix/modules/corsik.suggestions/lib/Handler.php',
					'bitrix/modules/corsik.suggestions/install/version.php',
				],
				['/lib/Handler.php', '/install/version.php'],
			],
			'without leading slash list' => [
				['lib/Handler.php', 'install/version.php'],
				['/lib/Handler.php', '/install/version.php'],
			],
			'empty list' => [
				[],
				[],
			],
		];
	}

	#[DataProvider('listProvider')]
	public function testNormalizeList(array $input, array $expected): void
	{
		$this->assertSame($expected, StructureNormalizer::normalizePaths($input, self::MODULE_ID));
	}

	public function testDoesNotStripOtherModulePrefix(): void
	{
		$input = ['bitrix/modules/other.module/lib/File.php' => 'hash1'];
		$result = StructureNormalizer::normalizePaths($input, self::MODULE_ID);

		$this->assertSame(['/bitrix/modules/other.module/lib/File.php' => 'hash1'], $result);
	}
}
