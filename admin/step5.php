<?php

namespace Bitrix\MpBuilder;

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/prolog.php");

use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Text\Encoding;

global $USER;
global $APPLICATION;

if (!$USER->IsAdmin()) {
    $APPLICATION->AuthForm();
}

IncludeModuleLangFile(__FILE__);
$MODULE_ID = 'bitrix.mpbuilder';

$APPLICATION->SetTitle(GetMessage("BITRIX_MPBUILDER_SAG_CETVERTYY_SBORK"));
require($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/include/prolog_admin_after.php");

$aTabs = [
    [
        "DIV" => "tab1",
        "TAB" => GetMessage("BITRIX_MPBUILDER_SAG"),
        "ICON" => "main_user_edit",
        "TITLE" => GetMessage("BITRIX_MPBUILDER_SBORKA_OBNOVLENIA"),
    ],
];
$editTab = new \CAdminTabControl("editTab", $aTabs);

echo BeginNote()
    . GetMessage("BITRIX_MPBUILDER_V_ARHIV_POPADUT_FAYL")
    . ' install/version.php. '
    . GetMessage(
        "BITRIX_MPBUILDER_OBNOVLENIE_NEOBHODIM"
    )
    . ' <a href="https://partners.1c-bitrix.ru/personal/modules/modules.php?ACTIVE=Y" target="_blank">marketplace</a>.'
    . EndNote();

$moduleId = '';
$arModuleVersion = [];
$_REQUEST['module_id'] = str_replace(['..', '/', '\\'], '', $_REQUEST['module_id']);

if ($_REQUEST['module_id'] && is_dir($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $_REQUEST['module_id'])) {
    $moduleId = $_SESSION['mpbuilder']['module_id'] = $_REQUEST['module_id'];
} else {
    $moduleId = $_SESSION['mpbuilder']['module_id'];
}

if ($moduleId) {

    $moduleBuilder = new Module($moduleId);
    $bitrixComponentRootPath = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/components';

    if ($_REQUEST['action'] == 'version_restore' && check_bitrix_sessid()) {
        rename($moduleBuilder->getRootDirPath() . '/install/_version.php', $moduleBuilder->getRootFileVersionPath());
    }

    if (file_exists($moduleBuilder->getRootFileVersionPath())) {
        include($moduleBuilder->getRootFileVersionPath());
    }

    $NAMESPACE = \COption::GetOptionString($moduleId, 'NAMESPACE', '');

    if ($_REQUEST['action'] == 'delete' && check_bitrix_sessid()) {
        Filesystem::rmDir($moduleBuilder->getRootTmpDirPath());
    } elseif ($_POST['save'] && check_bitrix_sessid()) {
        $strError = '';
        $strFileList = '<br><br> <b>' . GetMessage("BITRIX_MPBUILDER_SPISOK_FAYLOV_V_ARHI") . ':</b><br>';
        $version = $_REQUEST['version'];

        if ($bCustomNameSpace = array_key_exists('NAMESPACE', $_REQUEST)) {
            \COption::SetOptionString(
                $moduleId,
                'NAMESPACE',
                $NAMESPACE = str_replace(['/', '\\', ' '], '', $_REQUEST['NAMESPACE'])
            );
        }

        if (!$version) {
            $strError .= GetMessage("BITRIX_MPBUILDER_VERSIA_MODULA_NE_UKA") . '<br>';
        }

        if (!$_REQUEST['description']) {
            $strError .= GetMessage("BITRIX_MPBUILDER_NE_UKAZANO_OPISANIE") . '<br>';
        }

        if (!$strError && $_REQUEST['store_version']) {

            rename(
                $moduleBuilder->getRootFileVersionPath(),
                $moduleBuilder->getRootDirPath() . '/install/_version.php'
            );

            if (
                !file_put_contents(
                    $moduleBuilder->getRootFileVersionPath(),
                    $moduleBuilder->getContextVersion($version)
                )
            ) {
                $strError .= GetMessage("BITRIX_MPBUILDER_NE_UDALOSQ_ZAPISATQ")
                    . $moduleBuilder->getRootFileVersionPath()
                    . '<br>';
            }
        }

        if (!$strError) {
            if (is_dir($moduleBuilder->getRootTmpDirPath())) {
                Filesystem::rmDir($moduleBuilder->getRootTmpDirPath());
            }

            mkdir($moduleBuilder->getRootDirVersionPath($version), BX_DIR_PERMISSIONS, true);

            if (function_exists('mb_internal_encoding')) {
                mb_internal_encoding('ISO-8859-1');
            }

            if (!$strError && $_REQUEST['components']) {
                $ar = [];
                $componentDir = opendir($moduleBuilder->getRootDirComponentPath()); // let's get components list
                if ($bCustomNameSpace) {
                    while (false !== $item = readdir($componentDir)) {
                        if ($item == '.' || $item == '..') {
                            continue;
                        }

                        if (is_dir($f = $bitrixComponentRootPath . '/' . $NAMESPACE . '/' . $item)) {
                            $arTmp = Filesystem::getFiles($f, [], true);
                            foreach ($arTmp as $file) {
                                $ar[] = '/' . $NAMESPACE . '/' . $item . $file;
                            }
                        }
                    }
                    closedir($componentDir);
                } else {
                    while (false !== $item = readdir($componentDir)) {
                        if (
                            $item == '.' || $item == '..'
                            || !is_dir(
                                $path0 = $moduleBuilder->getRootDirComponentPath() . '/' . $item
                            )
                        ) {
                            continue;
                        }

                        $dir0 = opendir($path0);
                        while (false !== $item0 = readdir($dir0)) {
                            if ($item0 == '.' || $item0 == '..' || !is_dir($f = $path0 . '/' . $item0)) {
                                continue;
                            }

                            $arTmp = Filesystem::getFiles(
                                $bitrixComponentRootPath . '/' . $item . '/' . $item0,
                                [],
                                true
                            );

                            foreach ($arTmp as $file) {
                                $ar[] = '/' . $item . '/' . $item0 . $file;
                            }
                        }
                        closedir($dir0);
                    }
                    closedir($componentDir);
                }

                foreach ($ar as $file) {
                    $from = $bitrixComponentRootPath . $file;
                    $to = $moduleBuilder->getRootDirComponentPath() . ($bCustomNameSpace ? preg_replace(
                            '#^/[^/]+#',
                            '',
                            $file
                        ) : $file);

                    if (!file_exists($to) || filemtime($from) > filemtime($to)) {
                        if (!is_dir($d = dirname($to)) && !mkdir($d, BX_DIR_PERMISSIONS, true)) {
                            $strError .= GetMessage("BITRIX_MPBUILDER_NE_SOZDATQ_PAPKU") . $d . '<br>';
                        } elseif (!copy($from, $to)) {
                            $strError .= GetMessage("BITRIX_MPBUILDER_NE_UDALOSQ_SKOPIROVA") . $from . '<br>';
                        } else {
                            touch($to, filemtime($from));
                        }
                    }
                }
            }

            $originalModuleFiles = Filesystem::getFiles($moduleBuilder->getRootDirPath(), [], true);
            $time_from = strtotime($arModuleVersion['VERSION_DATE']);
            $tmpDirStrLen = strlen($moduleBuilder->getRootTmpDirPath());
            foreach ($originalModuleFiles as $file) {
                $fromFile = $moduleBuilder->getRootDirPath() . $file;
                $toFile = $moduleBuilder->getRootDirVersionPath($version) . $file;

                if ($file === '/install/_version.php') {
                    continue;
                }

                if ($file === '/install/version.php') {

                    if (
                        $_REQUEST['store_version']
                        && !file_put_contents(
                            $fromFile,
                            $moduleBuilder->getContextVersion($version)
                        )
                    ) {
                        $strError .= GetMessage("BITRIX_MPBUILDER_NOT_WRITE_NEW_VERSION") . $fromFile . '<br>';
                    }

                    if (!file_exists($dir = dirname($toFile))) {
                        mkdir($dir, BX_DIR_PERMISSIONS, true);
                    }

                    if (!file_put_contents($toFile, $moduleBuilder->getContextVersion($version))) {
                        $strError .= GetMessage("BITRIX_MPBUILDER_NOT_WRITE_NEW_VERSION") . $toFile . '<br>';
                    } else {
                        $strFileList .= substr($toFile, $tmpDirStrLen) . '<br>';
                    }

                    continue;
                }

                if (filemtime($fromFile) < $time_from) {
                    continue;
                }

                $fileContents = file_get_contents($fromFile);
                if (!$fileContents) {
                    $strError .= GetMessage("BITRIX_MPBUILDER_NE_UDALOSQ_PROCITATQ") . $fromFile . '<br>';
                } else {
                    if (substr($file, -4) == '.php' && Filesystem::getStringCharset($fileContents) == 'utf8') {
                        $fileContents = Encoding::convertEncoding($fileContents, 'utf8', 'cp1251');
                    }

                    if (!file_exists($dir = dirname($toFile))) {
                        mkdir($dir, BX_DIR_PERMISSIONS, true);
                    }

                    if (!file_put_contents($toFile, $fileContents)) {
                        $strError .= GetMessage("BITRIX_MPBUILDER_NE_UDALOSQ_SOHRANITQ") . $toFile . '<br>';
                    } else {
                        $strFileList .= substr($toFile, $tmpDirStrLen) . '<br>';
                    }
                }
            }

            if (!$strError) {
                $descriptionFilePath = $moduleBuilder->getRootDirVersionPath($version) . '/description.ru';
                $description = $_REQUEST['description'];
                if (defined('BX_UTF') && BX_UTF) {
                    $description = Encoding::convertEncoding($description, 'utf8', 'cp1251');
                }
                if (!file_put_contents($descriptionFilePath, $description)) {
                    $strError .= GetMessage("BITRIX_MPBUILDER_NE_UDALOSQ_ZAPISATQ") . $descriptionFilePath . '<br>';
                } else {
                    $strFileList .= substr($descriptionFilePath, $tmpDirStrLen) . '<br>';
                }
            }

            if (!$strError && ($str = trim($_REQUEST['updater']))) {
                /*
                  $str = file_get_contents($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/bitrix.mpbuilder/samples/_updater.php');
                  $str = str_replace('{MODULE_ID}', $moduleId, $str);
                */
                $updaterFilePath = $moduleBuilder->getRootDirVersionPath($version) . '/updater.php';
                if (!file_put_contents($updaterFilePath, $str)) {
                    $strError .= GetMessage("BITRIX_MPBUILDER_NE_UDALOSQ_SOHRANITQ") . $updaterFilePath;
                } else {
                    $strFileList .= substr($updaterFilePath, $tmpDirStrLen) . '<br>';
                }
            }

            Filesystem::packFolder(
                $moduleBuilder->getRootDirVersionPath($version),
                $moduleBuilder->getRootTmpDirPath()
            );
        }

        if (!$strError) {
            $linkFolder = $moduleBuilder->getTmpDirPath() . '/';
            $filemanLink = "/bitrix/admin/fileman_admin.php?lang=ru&site=s1&path=" . UrlEncode($linkFolder);
            $link = $linkFolder . $version . '.tar.gz';
            $href = "/bitrix/admin/fileman_file_download.php?path=" . UrlEncode($link);
            \CAdminMessage::ShowMessage([
                "MESSAGE" => GetMessage("BITRIX_MPBUILDER_OBNOVLENIE_SOBRANO"),
                "DETAILS" => '<a target="_blank" href="'
                    . $filemanLink
                    . '">'
                    . GetMessage(
                        "BITRIX_MPBUILDER_FOLDER_OBNOVLENIA_MOJ"
                    )
                    . '</a>.'
                    . '<br>'
                    . GetMessage(
                        "BITRIX_MPBUILDER_ARHIV_OBNOVLENIA_MOJ"
                    )
                    . ': <a href="'
                    . $href
                    . '">'
                    . $link
                    . '</a>.'
                    . '<br><a target="_blank" href="https://partners.1c-bitrix.ru/personal/modules/deploy.php?ID='
                    . urlencode($moduleId)
                    . '">'
                    . GetMessage("BITRIX_MPBUILDER_ZAGRUZITQ_V")
                    . ' marketplace</a> '
                    . '<br><input type=button value="'
                    . GetMessage("BITRIX_MPBUILDER_UDALITQ_VREMENNYE_FA")
                    . '" onclick="if(confirm(\''
                    . GetMessage("BITRIX_MPBUILDER_UDALITQ_PAPKU")
                    . ' &quot;/bitrix/tmp/'
                    . $moduleId
                    . '&quot; '
                    . GetMessage("BITRIX_MPBUILDER_I_EE_SODERJIMOE")
                    . '?\'))document.location=\'?action=delete&'
                    . bitrix_sessid_get()
                    . '\'">'
                    . $strFileList,
                "TYPE" => "OK",
                "HTML" => true,
            ]);
        } else {
            \CAdminMessage::ShowMessage([
                "MESSAGE" => GetMessage("BITRIX_MPBUILDER_OSIBKA_OBRABOTKI_FAY"),
                "DETAILS" => $strError,
                "TYPE" => "ERROR",
                "HTML" => true,
            ]);
        }
    }
}

?>
<form action="<?
echo $APPLICATION->GetCurPage() ?>?lang=<?= LANG ?>" method="POST"
      enctype="multipart/form-data" name="builder_form">
    <?
    echo bitrix_sessid_post();
    $editTab->Begin();
    $editTab->BeginNextTab();
    ?>
    <tr class=heading>
        <td colspan=2><?= GetMessage("BITRIX_MPBUILDER_VYBOR_MODULA") ?></td>
    </tr>
    <tr>
        <td><?= GetMessage("BITRIX_MPBUILDER_TEKUSIY_MODULQ") ?></td>
        <td>
            <select name=module_id onchange="document.location='?module_id='+this.value">
                <option></option>
                <?
                $arModules = [];
                $modulesDer = opendir($path = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules');
                while (false !== $item = readdir($modulesDer)) {
                    if ($item == '.' || $item == '..' || !is_dir($path . '/' . $item) || !strpos($item, '.')) {
                        continue;
                    }

                    $arModules[$item] = '<option value="'
                        . $item
                        . '" '
                        . ($moduleId == $item ? 'selected' : '')
                        . '>'
                        . $item
                        . '</option>';
                }

                closedir($modulesDer);
                asort($arModules);

                echo implode("\n", $arModules);
                ?>
            </select>
        </td>
    </tr>
    <?
    if ($moduleId) {
        ?>
        <tr>
            <td valign=top><?= GetMessage("BITRIX_MPBUILDER_VERSIA_OBNOVLENIA") ?></td>
            <td>
                <input name="version"
                       value="<?= ($version = $_REQUEST['version'] ? htmlspecialcharsbx($_REQUEST['version'])
                           : VersionUp($arModuleVersion['VERSION'])) ?>">
                <label><input type=checkbox
                              name=store_version <?= $_REQUEST['store_version'] ? 'checked' : '' ?>> <?= GetMessage(
                        "BITRIX_MPBUILDER_OBNOVITQ_DATU_I_VERS"
                    ) ?>
                </label>
                <?
                if (file_exists($f = $moduleBuilder->getRootDirPath() . '/install/_version.php')) {
                    include($f);
                    if ($arModuleVersion['VERSION'] != $version) {
                        echo '<br>'
                            . GetMessage("BITRIX_MPBUILDER_DOSTUPNA_VERSIA")
                            . htmlspecialcharsbx(
                                $arModuleVersion['VERSION']
                            )
                            . '</b> '
                            . GetMessage("BITRIX_MPBUILDER_FILE")
                            . ' version.php. <a href="javascript:if(confirm(\''
                            . GetMessage("BITRIX_MPBUILDER_VOSSTANOVITQ_STARUU")
                            . '?\'))document.location=\'?action=version_restore&'
                            . bitrix_sessid_get()
                            . '\'">'
                            . GetMessage("BITRIX_MPBUILDER_VOSSTANOVITQ")
                            . '</a>.';
                    }
                }
                ?>
            </td>
        </tr>
        <tr>
            <td valign=top><?= GetMessage("BITRIX_MPBUILDER_OBRABOTKA_USTANOVLEN") ?></td>
            <td>
                <?
                $bCustomNameSpace = false;
                if (!file_exists($moduleBuilder->getRootDirComponentPath())) {
                    echo GetMessage("BITRIX_MPBUILDER_MODULQ_NE_SODERJIT_K") . ' /install';
                } else {
                    echo '<label><input type=checkbox onchange="if(ob=document.getElementById(\'NAMESPACE\'))ob.disabled=!this.checked;" name=components '
                        . ($_REQUEST['components'] ? 'checked' : '')
                        . '> '
                        . GetMessage("BITRIX_MPBUILDER_SKOPIROVATQ_IZMENENN")
                        . ' /bitrix/components/ '
                        . GetMessage("BITRIX_MPBUILDER_V_ADRO_MODULA")
                        . '</label>';

                    $componentDir = opendir($moduleBuilder->getRootDirComponentPath());
                    while (false !== $item = readdir($componentDir)) {
                        $path0 = $moduleBuilder->getRootDirComponentPath();

                        if ($item == '.' || $item == '..' || !is_dir($path0 . '/' . $item)) {
                            continue;
                        }

                        if ($bCustomNameSpace = file_exists($path0 . '/component.php')) {
                            break;
                        }
                    }
                    closedir($componentDir);
                }
                ?>
            </td>
        </tr>
        <?
        if ($bCustomNameSpace) {
            ?>
            <tr>
                <td><?= GetMessage("BITRIX_MPBUILDER_PROSTRANSTVO_IMEN_KO") ?></td>
                <td>/bitrix/components/<input name=NAMESPACE id=NAMESPACE value="<?= htmlspecialcharsbx($NAMESPACE) ?>" <?= $_REQUEST['components']
                        ? '' : 'disabled' ?>>
                </td>
            </tr>
            <tr>
                <td></td>
                <td class=smalltext><?= GetMessage("BITRIX_MPBUILDER_KOMPONENTY_MODULA_LE") ?>
                    <i>install</i>, <?= GetMessage("BITRIX_MPBUILDER_CTOBY_PROVERITQ_IZME") ?><br>
                    <?= GetMessage("BITRIX_MPBUILDER_TAKJE_UBEDITESQ_CTO") ?>
                    <i>updater.php</i> <?= GetMessage("BITRIX_MPBUILDER_PRAVILQNO_KOPIRUET_K") ?></td>
            </tr>
            <?
        }
        ?>
        <tr>
            <td valign=top><?= GetMessage("BITRIX_MPBUILDER_OPISANIE_OBNOVLENIA") ?></td>
            <td>
                <?
                if (!$description = $_REQUEST['description']) {
                    if (
                        file_exists(
                            $f = $_SERVER['DOCUMENT_ROOT']
                                . BX_ROOT
                                . '/tmp/'
                                . $moduleId
                                . '/'
                                . $version
                                . '/description.ru'
                        )
                    ) {
                        $description = file_get_contents($f);
                        if (defined('BX_UTF') && BX_UTF) {
                            $description = $APPLICATION->ConvertCharset($description, 'cp1251', 'utf8');
                        }
                    }
                }

                if (!$description) {
                    $description = '<ul>
  <li></li>
</ul>';
                }

                ?>
                <textarea id="description__editor" name=description style="width:100%" rows=10><?= htmlspecialcharsbx(
                        $description
                    ) ?></textarea>
                <?
                if (Option::get('fileman', "use_code_editor", "Y") == "Y" && Loader::includeModule('fileman')) {
                    \CCodeEditor::Show([
                        'textareaId' => 'description__editor',
                        'height' => 350,
                        'forceSyntax' => 'php',
                    ]);
                }
                ?>
            </td>
        </tr>
        <tr>
            <td></td>
            <td class=smalltext><?= GetMessage("BITRIX_MPBUILDER_PRI_PEREDACE_ISPOLNA") ?></td>
        </tr>
        <tr>
            <td valign=top><?= GetMessage("BITRIX_MPBUILDER_SKRIPT_OBNOVLENIA") ?> updater.php:</td>
            <td>
                <?
                if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                    $updater = $_REQUEST['updater'];
                } else {
                    $updater = file_get_contents(
                        $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/bitrix.mpbuilder/samples/_updater.php'
                    );
                    $updater = str_replace('{MODULE_ID}', $moduleId, $updater);
                    $updater = str_replace('{NAMESPACE}', $bCustomNameSpace ? $NAMESPACE : '', $updater);
                }
                ?>
                <textarea id="updater__editor" name=updater style="width:100%" rows=10><?= htmlspecialcharsbx(
                        $updater
                    ) ?></textarea>
                <?
                if (Option::get('fileman', "use_code_editor", "Y") === "Y" && Loader::includeModule('fileman')) {
                    \CCodeEditor::Show([
                        'textareaId' => 'updater__editor',
                        'height' => 350,
                        'forceSyntax' => 'php',
                    ]);
                }
                ?>
            </td>
        </tr>
        <?
    }

    $editTab->Buttons();
    ?>
    <input type="submit" name=save value="<?= GetMessage("BITRIX_MPBUILDER_PRODOLJITQ") ?>">
</form>
<?
$editTab->End();

require($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/include/epilog_admin.php");
?>
