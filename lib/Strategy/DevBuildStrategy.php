<?php

namespace Bitrix\MpBuilder\Strategy;

use Bitrix\Main\Error;
use Bitrix\MpBuilder\Contract\BuildStrategyInterface;
use Bitrix\MpBuilder\Dto\BuildContext;
use Bitrix\MpBuilder\Dto\BuildResult;
use Bitrix\MpBuilder\Updates;

class DevBuildStrategy implements BuildStrategyInterface
{
	private ArchiveBuildStrategy $archiveStrategy;
	private Updates $updatesManager;

	public function __construct(ArchiveBuildStrategy $archiveStrategy, Updates $updatesManager)
	{
		$this->archiveStrategy = $archiveStrategy;
		$this->updatesManager = $updatesManager;
	}

	public function build(BuildContext $context): BuildResult
	{
		$result = $this->archiveStrategy->build($context);

		if (!$result->isSuccess())
		{
			return $result;
		}

		if (!$this->updatesManager->saveVersionFile($context->versionContent))
		{
			$result->addError(new Error('Failed to write version.php to dev folder'));
		}

		if (!$this->updatesManager->saveDescription($context->description))
		{
			$result->addError(new Error('Failed to write description.ru to dev folder'));
		}

		if (($str = trim($context->updater)) && !$this->updatesManager->saveUpdater($str))
		{
			$result->addError(new Error('Failed to write updater.php to dev folder'));
		}

		return $result;
	}
}
