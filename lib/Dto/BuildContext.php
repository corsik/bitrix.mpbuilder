<?php

namespace Bitrix\MpBuilder\Dto;

use Bitrix\MpBuilder\Module;

readonly class BuildContext
{
	public function __construct(
		public Module $module,
		public string $version,
		public string $versionContent,
		public string $description = '',
		public string $updater = '',
		public array $arModuleVersion = [],
	)
	{
	}

	public function getTimeFrom(): int
	{
		return (int) strtotime($this->arModuleVersion['VERSION_DATE'] ?? '');
	}
}
