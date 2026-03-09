<?php

namespace Bitrix\MpBuilder\Dto;

use Bitrix\Main\Result;

class BuildResult extends Result
{
	private array $fileList = [];

	public function addFile(string $file): void
	{
		$this->fileList[] = $file;
	}

	public function getFileList(): array
	{
		return $this->fileList;
	}
}
