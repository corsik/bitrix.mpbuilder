<?php

namespace Bitrix\MpBuilder\Strategy;

use Bitrix\Main\Error;
use Bitrix\MpBuilder\Contract\BuildStrategyInterface;
use Bitrix\MpBuilder\DevVersionStorage;
use Bitrix\MpBuilder\Dto\BuildContext;
use Bitrix\MpBuilder\Dto\BuildResult;

class DevBuildStrategy implements BuildStrategyInterface
{
	public function __construct(
		private ArchiveBuildStrategy $archiveStrategy,
		private DevVersionStorage $storage,
	)
	{
	}

	public function build(BuildContext $context): BuildResult
	{
		$result = $this->archiveStrategy->build($context);

		if (!$result->isSuccess())
		{
			return $result;
		}

		if (!$this->storage->saveVersionFile($context->versionContent))
		{
			$result->addError(new Error('Failed to write version.php to dev folder'));
		}

		if (!$this->storage->saveDescription($context->description))
		{
			$result->addError(new Error('Failed to write description.ru to dev folder'));
		}

		if (($str = trim($context->updater)) && !$this->storage->saveUpdater($str))
		{
			$result->addError(new Error('Failed to write updater.php to dev folder'));
		}

		return $result;
	}
}
