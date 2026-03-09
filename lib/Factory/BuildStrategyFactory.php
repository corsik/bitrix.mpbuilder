<?php

namespace Bitrix\MpBuilder\Factory;

use Bitrix\MpBuilder\Contract\BuildStrategyInterface;
use Bitrix\MpBuilder\Strategy\ArchiveBuildStrategy;
use Bitrix\MpBuilder\Strategy\DevBuildStrategy;
use Bitrix\MpBuilder\Strategy\FullArchiveBuildStrategy;
use Bitrix\MpBuilder\Updates;

class BuildStrategyFactory
{
	public static function createForUpdate(Updates $updatesManager): BuildStrategyInterface
	{
		if ($updatesManager->isDevStrategyActive())
		{
			return new DevBuildStrategy(new ArchiveBuildStrategy(), $updatesManager);
		}

		return new ArchiveBuildStrategy();
	}

	public static function createForArchive(): BuildStrategyInterface
	{
		return new FullArchiveBuildStrategy();
	}
}
