<?php

namespace Bitrix\MpBuilder;

class Module
{
    private string $moduleId = "";

    final public function __construct(string $moduleId)
    {
        $this->moduleId = $moduleId;
    }

    public function getModuleId(): string
    {
        return $this->moduleId;
    }

    public function getDirPath(): string
    {
        return BX_ROOT . "/modules/$this->moduleId";
    }

    public function getRootDirPath(): string
    {
        return $_SERVER['DOCUMENT_ROOT'] . $this->getDirPath();
    }

    public function getTmpDirPath(): string
    {
        return BX_ROOT . "/tmp/$this->moduleId";
    }

    public function getRootTmpDirPath(): string
    {
        return $_SERVER['DOCUMENT_ROOT'] . $this->getTmpDirPath();
    }

    public function getRootFileVersionPath(): string
    {
        return $this->getRootDirPath() . "/install/version.php";
    }

    public function getRootDirVersionPath(string $version): string
    {
        return $this->getRootTmpDirPath() . '/' . $version;
    }

    public function getRootDirComponentPath(): string
    {
        return $this->getRootDirPath() . '/install/components';
    }

    public function getContextVersion(string $version): string
    {
        return '<?' . "\n" .
            '$arModuleVersion = [' . "\n" .
            '	"VERSION" => "' . EscapePHPString($version) . '",' . "\n" .
            '	"VERSION_DATE" => "' . date('Y-m-d H:i:s') . '"' . "\n" .
            '];' . "\n" .
            '?>';
    }

}
