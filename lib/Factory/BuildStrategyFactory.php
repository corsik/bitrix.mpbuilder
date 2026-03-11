<?php

namespace Bitrix\MpBuilder\Factory;

use Bitrix\MpBuilder\Contract\BuildStrategyInterface;
use Bitrix\MpBuilder\DevVersionStorage;
use Bitrix\MpBuilder\Strategy\ArchiveBuildStrategy;
use Bitrix\MpBuilder\Strategy\DevBuildStrategy;
use Bitrix\MpBuilder\Strategy\FullArchiveBuildStrategy;

class BuildStrategyFactory
{
	public static function createForUpdate(DevVersionStorage $storage): BuildStrategyInterface
	{
		if (DevVersionStorage::isActive($storage->moduleId))
		{
			return new DevBuildStrategy(new ArchiveBuildStrategy(), $storage);
		}

		return new ArchiveBuildStrategy();
	}

	public static function createForArchive(): BuildStrategyInterface
	{
		return new FullArchiveBuildStrategy();
	}
}
