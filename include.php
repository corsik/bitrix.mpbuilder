<?php

use Bitrix\MpBuilder\Filesystem;

IncludeModuleLangFile(__FILE__);

class CBitrixMpBuilder
{
    public static function OnBuildGlobalMenu(&$aGlobalMenu, &$aModuleMenu)
    {
        global $APPLICATION;

        if ($APPLICATION->GetGroupRight("main") < "R") {
            return;
        }

        $MODULE_ID = basename(dirname(__FILE__));
        $aMenu = [
            //"parent_menu" => "global_menu_services",
            "parent_menu" => "global_menu_settings",
            "section" => $MODULE_ID,
            "sort" => 50,
            "text" => $MODULE_ID,
            "title" => '',
//			"url" => "partner_modules.php?module=".$MODULE_ID,
            "icon" => "",
            "page_icon" => "",
            "items_id" => $MODULE_ID . "_items",
            "more_url" => [],
            "items" => [],
        ];

        if (file_exists($path = dirname(__FILE__) . '/admin')) {
            if ($dir = opendir($path)) {
                $arFiles = [];

                while (false !== $item = readdir($dir)) {
                    if (in_array($item, ['.', '..', 'menu.php'])) {
                        continue;
                    }

                    if (!file_exists($file = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin/' . $MODULE_ID . '_' . $item)) {
                        file_put_contents($file, '<' . '? require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/' . $MODULE_ID . '/admin/' . $item . '");?' . '>');
                    }

                    $arFiles[] = $item;
                }

                sort($arFiles);
                $arTitles = [
                    'step1.php' => GetMessage("BITRIX_MPBUILDER_STRUKTURA_MODULA"),
                    'step2.php' => GetMessage("BITRIX_MPBUILDER_VYDELENIE_FRAZ"),
                    'step3.php' => GetMessage("BITRIX_MPBUILDER_REDAKTOR_KLUCEY"),
                    'step4.php' => GetMessage("BITRIX_MPBUILDER_SOZDANIE_ARHIVA"),
                    'step5.php' => GetMessage("BITRIX_MPBUILDER_SBORKA_OBNOVLENIY"),
                ];

                foreach ($arFiles as $item)
                    $aMenu['items'][] = [
                        'text' => $arTitles[$item],
                        'url' => $MODULE_ID . '_' . $item,
                        'module_id' => $MODULE_ID,
                        "title" => "",
                    ];
            }
        }
        $aModuleMenu[] = $aMenu;
    }
}

class CBuilderLang
{
    private array $MESS;
    public string $strLangPrefix;
    private string $m_dir;
    private string $file;
    private string $lang_file;
    private string $str;
    private string $InPhp = '';
    private string $InJs = '';
    private string $strResultScript = '';
    private string $strQuoted = '';
    private bool $bSiteUTF;
    private string $InHtml = 'InText';

    function __construct(string $m_dir, string $file, string $lang_file)
    {
        $this->m_dir = $m_dir;
        $this->file = $file;
        $str = file_get_contents($m_dir . $this->file);

        if ($str) {
            if (Filesystem::getStringCharset($str) == 'utf8') {
                $str = $GLOBALS['APPLICATION']->ConvertCharset($str, 'utf8', 'cp1251');
            }

            $this->str = $str;
            $this->lang_file = $lang_file;
            $this->bSiteUTF = defined('BX_UTF') && BX_UTF;

            if (file_exists($m_dir . $lang_file)) {
                $str = file_get_contents($m_dir . $lang_file);

                if (Filesystem::getStringCharset($str) == 'utf8') {
                    $str = $GLOBALS['APPLICATION']->ConvertCharset($str, 'utf8', 'cp1251');
                    file_put_contents($m_dir . $lang_file, $str);
                }

                include($m_dir . $lang_file);
                if (isset($MESS)) {
                    $this->MESS = $MESS;
                }

            } else {
                if (!defined('BX_DIR_PERMISSIONS'))
                    define('BX_DIR_PERMISSIONS', 0755);
                if (!file_exists($dir = dirname($m_dir . $lang_file)))
                    mkdir($dir, BX_DIR_PERMISSIONS, true);
                $this->MESS = [];
            }
        }
    }

    public function Parse(): void
    {
        if (function_exists('mb_orig_strlen'))
            $l = mb_orig_strlen($this->str);
        elseif (function_exists('mb_strlen'))
            $l = mb_strlen($this->str, 'latin1');
        else
            $l = strlen($this->str);

        for ($i = 0; $i < $l; $i++) {
            $this->pos = $i;
            if (function_exists('mb_orig_substr'))
                $c = mb_orig_substr($this->str, $i, 1);
            elseif (function_exists('mb_substr'))
                $c = mb_substr($this->str, $i, 1, 'latin1');
            else
                $c = substr($this->str, $i, 1);

            if ($this->InPhp) // PHP
            {
                if ($Esc)
                    $Esc = 0;
                elseif ($this->InPhp == 'InDoubleQuotes' && $c == '"') {
                    $bSkipNext = $this->EndQuotedString();
                    $this->InPhp = 'InCode';
                } elseif ($this->InPhp == 'InSingleQuotes' && $c == "'") {
                    $bSkipNext = $this->EndQuotedString();
                    $this->InPhp = 'InCode';
                } elseif ($this->InPhp == 'InMultiLineComment') {
                    if ($prev_c . $c == '*/')
                        $this->InPhp = 'InCode';
                } elseif (($this->InPhp == 'InCode' || $this->InPhp == 'InLineComment') && $prev_c . $c == '?' . '>')
                    $this->InPhp = '';
                elseif ($this->InPhp == 'InLineComment') {
                    if ($c == "\n")
                        $this->InPhp = 'InCode';
                } elseif ($this->InPhp == 'InCode') {
                    if ($c == '#' || $prev_c . $c == '//')
                        $this->InPhp = 'InLineComment';
                    elseif ($prev_c . $c == '/*')
                        $this->InPhp = 'InMultiLineComment';
                    elseif ($c == '"')
                        $this->InPhp = 'InDoubleQuotes';
                    elseif ($c == "'")
                        $this->InPhp = 'InSingleQuotes';
                } elseif ($this->InPhp == 'InSingleQuotes' || $this->InPhp == 'InDoubleQuotes') {
                    if ($c == '\\')
                        $Esc = 1;
                }
            } else // HTML
            {
                if ($prev_c . $c == '<?') {
                    $this->InPhp = 'InCode';
                    $this->strResultScript .= $this->strLowPrefix;
                    $this->strLowPrefix = '';
                    $this->InHtml = $this->InHtmlLast;
                } elseif ($this->InJs) // JavaScript || CSS
                {
                    if ($this->InJs == 'InStyle') {
                        if ($prev_c . $c == '</')
                            $this->InJs = '';
                    } elseif ($this->InJs == 'InLineComment') {
                        if ($c == "\n")
                            $this->InJs = 'InCode';
                    } elseif ($this->InJs == 'InMultiLineComment') {
                        if ($prev_c . $c == '*/')
                            $this->InJs = 'InCode';
                    } elseif ($this->InJs == 'InCode') {
                        if ($prev_c . $c == '</')
                            $this->InJs = '';
                        elseif ($c == '"')
                            $this->InJs = 'InDoubleQuotes';
                        elseif ($c == "'")
                            $this->InJs = 'InSingleQuotes';
                        elseif ($prev_c . $c == '//')
                            $this->InJs = 'InLineComment';
                        elseif ($prev_c . $c == '/*')
                            $this->InJs = 'InMultiLineComment';
                    } else // InQuotes
                    {
                        if ($Esc)
                            $Esc = 0;
                        elseif ($c == '\\')
                            $Esc = 1;
                        elseif ($this->InJs == 'InSingleQuotes') {
                            if ($c == "'") {
                                $this->EndQuotedString();
                                $this->InJs = 'InCode';
                            }
                        } elseif ($this->InJs == 'InDoubleQuotes') {
                            if ($c == '"') {
                                $this->EndQuotedString();
                                $this->InJs = 'InCode';
                            }
                        }
                    }
                } else // Pure HTML
                {
                    if ($this->InHtml == 'InTagName') {
                        if ($c == ' ' || $c == "\t" || $c == '>') {
                            if ($tag == 'script') {
                                $this->InJs = 'InCode';
                                $this->InHtml = 'InText';
                            } elseif ($tag == 'style') {
                                $this->InJs = 'InStyle';
                                $this->InHtml = 'InText';
                            } elseif ($c == '>')
                                $this->InHtml = 'InText';
                            else
                                $this->InHtml = 'InTag';
                        } else
                            $tag .= strtolower($c);
                    } elseif ($this->InHtml == 'InTag' && $c == '>')
                        $this->InHtml = 'InText';
                    elseif ($this->InHtml == 'InTag' && $c == "'")
                        $this->InHtml = 'InSingleQuotes';
                    elseif ($this->InHtml == 'InTag' && $c == '"')
                        $this->InHtml = 'InDoubleQuotes';
                    elseif ($this->InHtml == 'InSingleQuotes' && $c == "'") {
                        $this->EndQuotedString();
                        $this->InHtml = 'InTag';
                    } elseif ($this->InHtml == 'InDoubleQuotes' && $c == '"') {
                        $this->EndQuotedString();
                        $this->InHtml = 'InTag';
                    } elseif ($this->InHtml == 'InText' && $c == '<') {
                        $this->EndQuotedString();
                        $this->InHtmlLast = $this->InHtml;
                        $this->InHtml = 'InTagName';
                        $tag = '';
                    }
                }
            }
            $prev_c = $c;

            if (!$bSkipNext && !$this->Collect($c)) {
                $this->strResultScript .= $c;
            }
            $bSkipNext = 0;
        }
        $this->strResultScript .= $this->strLowPrefix;
    }

    function Collect($c): bool
    {
        $bCollect = strpos($this->InHtml . $this->InJs . $this->InPhp, 'Quotes') || ($this->InHtml == 'InText' && !$this->InJs && !$this->InPhp);
        if ($bCollect) {
            if (($o = ord($c)) > 127)
                $this->bTranslate = 1;
            if ($this->bTranslate) {
                if ($c == '<' || $o <= 127 && $this->strLow) {
                    $this->strLow .= $c;
                } else {
                    $this->strQuoted .= $this->strLow . $c;
                    $this->strLow = '';
                }
            } else
                $this->strLowPrefix .= $c;
            return true;
        }
        return false;
    }

    private function EndQuotedString(): ?bool
    {
        $bCutRight = strlen($this->strLow);

        if ($strMess = $this->strQuoted . ($bCutRight ? '' : $this->strLow)) {
            $key = $this->GetLangKey($strMess);
            $this->MESS[$key] = $strMess;
            $prefix = '<' . '?=';
            $postfix = '?' . '>';
            if ($this->InPhp) {
                $quote = $this->InPhp == 'InSingleQuotes' ? "'" : '"';
                if ($this->strLowPrefix == "'" || $this->strLowPrefix == '"') // delete quotes
                {
                    $prefix = '';
                    $this->strLowPrefix = '';
                } else
                    $prefix = $quote . '.';
                $postfix = $bCutRight ? "." . $quote : "";
            }
            $this->strResultScript .= $this->strLowPrefix . $prefix . 'GetMessage' . ($this->InJs ? 'JS' : '') . '("' . $key . '")' . $postfix . ($bCutRight ? $this->strLow : '');

            $this->bTranslate = 0;
            $this->strQuoted = '';
            $this->strLow = '';
            $this->strLowPrefix = '';

            return !$bCutRight; // true => skip next quote
        }

        $this->strResultScript .= $this->strLowPrefix;
        $this->strLowPrefix = '';

        return false;
    }

    private function GetLangKey($strMess): string
    {
        if (is_array($this->MESS))
            foreach ($this->MESS as $key => $val)
                if ($val == $strMess)
                    return $key;

        if (function_exists('mb_orig_substr'))
            $key = mb_orig_substr($strMess, 0, 20);
        elseif (function_exists('mb_substr'))
            $key = mb_substr($strMess, 0, 20, 'latin1');
        else
            $key = substr($strMess, 0, 20);

        $key = preg_replace("/[^\xa8\xb8\xc0-\xdf\xe0-\xff]/", ' ', $key);
        $key = trim($key);

        $from_u = GetMessage("BITRIX_MPBUILDER_YCUKENGSSZHQFYVAPROL");
        $to = 'YCUKENGSSZHQFYVAPROLDJEACSMITQBUEEYCUKENGSSZHQFYVAPROLDJEACSMITQBU';

        static $from;
        if (!$from) {
            if ($this->bSiteUTF)
                $from = $GLOBALS['APPLICATION']->ConvertCharset($from_u, 'utf8', 'cp1251');
            else
                $from = $from_u;
        }

        $key = strtr($key, $from, $to);
        $key = preg_replace('/ +/', '_', $key);
        $new_key = $this->strLangPrefix . $key;
        $i = 0;
        while ($this->MESS[$new_key] && $this->MESS[$new_key] != $strMess) {
            $new_key = $this->strLangPrefix . $key . (++$i);
        }


        return $new_key;
    }

    public function Save(): bool
    {
        $str = "<" . "?\n";
        foreach ($this->MESS as $key => $val)
            $str .= '$MESS["' . $key . '"] = "' . str_replace('"', '\\"', str_replace('\\', '\\\\', $val)) . '";' . "\n";
        $str .= "?" . ">";

        if ($this->bSiteUTF)
            $str = $GLOBALS['APPLICATION']->ConvertCharset($str, 'cp1251', 'utf8');

        if (!file_put_contents($this->m_dir . $this->lang_file, $str))
            return false;

        $prefix = '';
        if (preg_match('#^/admin#', $this->file) && !preg_match('/(require|include).+prolog_admin/', $this->strResultScript))
            $prefix = '<' . '?php' . "\n" .
                'require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");' . "\n" .
                'IncludeModuleLangFile(__FILE__);' . "\n" .
                '?' . '>';

        if ($this->bSiteUTF)
            $this->strResultScript = $GLOBALS['APPLICATION']->ConvertCharset($this->strResultScript, 'cp1251', 'utf8');

        if (!file_put_contents($this->m_dir . $this->file, $prefix . $this->strResultScript))
            return false;

        return true;
    }
}

function GetLangPath($file, $m_dir)
{
    $lang = '/lang/ru';
    if (preg_match('#^(/install/components/[^/]+)(/[^/]+)(/?.*)$#', $file, $regs)) {
        if (file_exists($m_dir . $regs[1] . $regs[2] . '/component.php')) // with namespace
        {
            $c_dir = $regs[1] . $regs[2];
            $c_path = $regs[3];
        } else {
            $c_dir = $regs[1];
            $c_path = $regs[2] . $regs[3];
        }

        if (preg_match('#^(/templates/[^/]+/[^/]+/[^/]+/[^/]+)(/.+)$#', $c_path, $regs)) // complex
            return $c_dir . $regs[1] . $lang . $regs[2];
        elseif (preg_match('#^(/templates/[^/]+)(/.+)$#', $c_path, $regs)) // template
            return $c_dir . $regs[1] . $lang . $regs[2];
        else // component
            return $c_dir . $lang . $c_path;

        if (preg_match('#^(/install/components/[^/]+/[^/]+/templates/[^/]+/[^/]+/[^/]+/[^/]+)(/.+)$#', $file, $regs)) {
            $lang_file = $regs[1] . '/lang/ru' . $regs[2];
        } elseif (preg_match('#^(/install/components/[^/]+/[^/]+/templates/[^/]+)(/.+)$#', $file, $regs)) {
            $lang_file = $regs[1] . '/lang/ru' . $regs[2];
        } elseif (preg_match('#^(/install/components/[^/]+/[^/]+)(/.+)$#', $file, $regs)) {
            $lang_file = $regs[1] . '/lang/ru' . $regs[2];
        } elseif (preg_match('#^(/install/components/[^/]+)(/.+)$#', $file, $regs)) {
            $lang_file = $regs[1] . '/lang/ru' . $regs[2];
        }
    } else {
        $lang_file = '/lang/ru' . $file;
    }

    return $lang_file;
}

function VersionUp($num): string
{
    $ar = explode('.', $num);
    if (count($ar) == 3) {
        return $ar[0] . '.' . $ar[1] . '.' . (++$ar[2]);
    }

    return $num;
}

function GetMess($f): string
{
    $MESS = false;

    if (is_file($f)) {
        include($f);
    }

    return $MESS;
}

?>
