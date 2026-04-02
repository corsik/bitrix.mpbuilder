<?php

namespace Bitrix\MpBuilder\Tests;

use Bitrix\MpBuilder\Module;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ModuleSanitizeTest extends TestCase
{
	public static function sanitizeProvider(): array
	{
		return [
			'normal module id' => ['corsik.suggestions', 'corsik.suggestions'],
			'path traversal dots' => ['../../../etc/passwd', 'etcpasswd'],
			'forward slashes' => ['path/to/module', 'pathtomodule'],
			'backslashes' => ['path\\to\\module', 'pathtomodule'],
			'mixed dangerous chars' => ['../../module\\hack', 'modulehack'],
			'empty string' => ['', ''],
			'only dots' => ['....', ''],
			'dots and slashes' => ['../..', ''],
			'valid with dots' => ['my.module', 'my.module'],
			'single dot preserved' => ['a.b', 'a.b'],
		];
	}

	#[DataProvider('sanitizeProvider')]
	public function testSanitizeId(string $input, string $expected): void
	{
		$this->assertSame($expected, Module::sanitizeId($input));
	}
}
