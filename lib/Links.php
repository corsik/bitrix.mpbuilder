<?php

namespace Bitrix\MpBuilder;

class Links
{
	public static function filemanFolder(string $tmpDirPath): string
	{
		return '/bitrix/admin/fileman_admin.php?lang=ru&site=s1&path=' . urlencode($tmpDirPath . '/');
	}

	public static function downloadFile(string $filePath): string
	{
		return '/bitrix/admin/fileman_file_download.php?path=' . urlencode($filePath);
	}

	public static function marketplaceDeploy(string $moduleId): string
	{
		return 'https://partners.1c-bitrix.ru/personal/modules/deploy.php?ID=' . urlencode($moduleId);
	}

	public static function marketplaceEdit(string $moduleId): string
	{
		return 'https://partners.1c-bitrix.ru/personal/modules/edit_module.php?ID=' . urlencode($moduleId);
	}
}
