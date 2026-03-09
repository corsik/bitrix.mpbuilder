<?php

namespace Bitrix\MpBuilder;

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;

abstract class BaseBuilderComponent extends \CBitrixComponent implements Controllerable
{
	protected ErrorCollection $errorCollection;

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->errorCollection = new ErrorCollection();
	}

	public function executeComponent(): void
	{
		global $USER;

		if (!$USER->IsAdmin())
		{
			ShowError('Access denied');

			return;
		}

		$this->arResult['SESSION_MODULE_ID'] = $_SESSION['mpbuilder']['module_id'] ?? '';

		$this->includeComponentTemplate();
	}

	public function getErrors(): array
	{
		return $this->errorCollection->toArray();
	}

	public function getErrorByCode($code): ?Error
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	protected function checkAdmin(): bool
	{
		global $USER;

		if (!$USER->IsAdmin())
		{
			$this->errorCollection->setError(new Error('Access denied'));

			return false;
		}

		return true;
	}
}
