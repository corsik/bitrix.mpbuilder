<?php

namespace Bitrix\MpBuilder\Contract;

use Bitrix\MpBuilder\Dto\BuildContext;
use Bitrix\MpBuilder\Dto\BuildResult;

interface BuildStrategyInterface
{
	public function build(BuildContext $context): BuildResult;
}
