<?php

namespace Bitrix\MpBuilder\Tests\Util;

use Bitrix\MpBuilder\Util\Links;
use PHPUnit\Framework\TestCase;

class LinksTest extends TestCase
{
	public function testFilemanFolder(): void
	{
		$result = Links::filemanFolder('/bitrix/tmp/module');

		$this->assertStringStartsWith('/bitrix/admin/fileman_admin.php?', $result);
		$this->assertStringContainsString('lang=ru', $result);
		$this->assertStringContainsString('site=s1', $result);
		$this->assertStringContainsString('path=', $result);
	}

	public function testFilemanFolderEncodesPath(): void
	{
		$result = Links::filemanFolder('/path with spaces');

		$this->assertStringContainsString(urlencode('/path with spaces/'), $result);
	}

	public function testDownloadFile(): void
	{
		$result = Links::downloadFile('/bitrix/tmp/archive.tar.gz');

		$this->assertStringStartsWith('/bitrix/admin/fileman_file_download.php?path=', $result);
		$this->assertStringContainsString(urlencode('/bitrix/tmp/archive.tar.gz'), $result);
	}

	public function testMarketplaceDeploy(): void
	{
		$result = Links::marketplaceDeploy('corsik.suggestions');

		$this->assertSame(
			'https://partners.1c-bitrix.ru/personal/modules/deploy.php?ID=corsik.suggestions',
			$result
		);
	}

	public function testMarketplaceEdit(): void
	{
		$result = Links::marketplaceEdit('corsik.suggestions');

		$this->assertSame(
			'https://partners.1c-bitrix.ru/personal/modules/edit_module.php?ID=corsik.suggestions',
			$result
		);
	}

	public function testMarketplaceDeployEncodesSpecialChars(): void
	{
		$result = Links::marketplaceDeploy('module&id=hack');

		$this->assertStringContainsString(urlencode('module&id=hack'), $result);
		$this->assertStringNotContainsString('&id=hack', $result);
	}
}
