<?php

namespace Bitrix\MpBuilder;

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/prolog.php");

use Bitrix\Main\Text\Encoding;

global $USER;
global $APPLICATION;

if (!$USER->IsAdmin()) {
    $APPLICATION->AuthForm();
}

IncludeModuleLangFile(__FILE__);
$APPLICATION->SetTitle(GetMessage("BITRIX_MPBUILDER_SAG_TRETIY_SOZDANIE"));
require($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/include/prolog_admin_after.php");

$aTabs = [
    ["DIV" => "tab1", "TAB" => GetMessage("BITRIX_MPBUILDER_SAG"), "ICON" => "main_user_edit", "TITLE" => GetMessage("BITRIX_MPBUILDER_SOZDANIE_ARHIVA")],
];
$editTab = new \CAdminTabControl("editTab", $aTabs, true, true);

$module_id = '';
$arModuleVersion = [];

$_REQUEST['module_id'] = str_replace(['..', '/', '\\'], '', $_REQUEST['module_id']);
if ($_REQUEST['module_id'] && is_dir($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $_REQUEST['module_id'])) {
    $module_id = $_SESSION['mpbuilder']['module_id'] = $_REQUEST['module_id'];
} else {
    $module_id = $_SESSION['mpbuilder']['module_id'];
}

echo BeginNote() .
    GetMessage("BITRIX_MPBUILDER_VSE_SKRIPTY_MODULA_B") . ' cp1251, ' . GetMessage("BITRIX_MPBUILDER_ZATEM_BUDET_SOZDAN_A") . ' .last_version.tar.gz, ' . GetMessage("BITRIX_MPBUILDER_KOTORYY_NADO_OTPRAVI") . ' <a href="https://partners.1c-bitrix.ru/personal/modules/edit_module.php?ID=' . $module_id . '" target="_blank">marketplace</a>.' .
    EndNote();


if ($module_id && check_bitrix_sessid()) {

    $moduleBuilder = new Module($module_id);
    $tmpModulePathLastVersion = $moduleBuilder->getTmpDirPath() . "/.last_version";
    $tmpModuleRootPathLastVersion = $moduleBuilder->getRootTmpDirPath() . "/.last_version";

    if ($_REQUEST['action'] == 'delete') {
        Filesystem::rmDir($moduleBuilder->getRootTmpDirPath());
    } elseif ($_POST['save']) {
        $strError = '';
        $version = $_REQUEST['version'];

        if ($version && !file_put_contents($moduleBuilder->getRootFileVersionPath(), $moduleBuilder->getContextVersion($version))) {
            $strError .= GetMessage("BITRIX_MPBUILDER_NE_UDALOSQ_ZAPISATQ") . $moduleBuilder->getRootFileVersionPath() . '<br>';
        }

        if (is_dir($moduleBuilder->getRootTmpDirPath())) {
            Filesystem::rmDir($moduleBuilder->getRootTmpDirPath());
        }

        mkdir($tmpModulePathLastVersion, BX_DIR_PERMISSIONS, true);

        if (function_exists('mb_internal_encoding')) {
            mb_internal_encoding('ISO-8859-1');
        }


        $originalModuleFiles = Filesystem::getFiles($moduleBuilder->getRootDirPath(), ['.svn', '.hg', '.git'], true);

        foreach ($originalModuleFiles as $file) {
            $fromFile = $moduleBuilder->getRootDirPath() . $file;
            $toFile = $tmpModuleRootPathLastVersion . $file;
            $fileContents = file_get_contents($fromFile);

            if (!$fileContents) {
                $strError .= GetMessage("BITRIX_MPBUILDER_NE_UDALOSQ_PROCITATQ") . $fromFile . '<br>';
            } else {
                if (substr($file, -4) == '.php' && Filesystem::getStringCharset($fileContents) == 'utf8') {
                    $fileContents = Encoding::convertEncoding($fileContents, 'utf8', 'cp1251');
                }

                $dir = dirname($toFile);
                if (!file_exists($dir)) {
                    mkdir($dir, BX_DIR_PERMISSIONS, true);
                }

                if (!file_put_contents($toFile, $fileContents)) {
                    $strError .= GetMessage("BITRIX_MPBUILDER_NE_UDALOSQ_SOHRANITQ") . $toFile . '<br>';
                }
            }
        }

        Filesystem::packFolder($tmpModuleRootPathLastVersion, $moduleBuilder->getRootTmpDirPath());

        if (!$strError) {
            $link = "$tmpModulePathLastVersion.tar.gz";
            $href = "/bitrix/admin/fileman_file_download.php?path=" . UrlEncode("$tmpModulePathLastVersion.tar.gz");
            \CAdminMessage::ShowMessage([
                "MESSAGE" => GetMessage("BITRIX_MPBUILDER_ARHIV_SOZDAN_USPESNO"),
                "DETAILS" => GetMessage("BITRIX_MPBUILDER_GOTOVYY_VARIANT_MOJN") .
                    ': <a href="' . $href . '">' . $link . '</a>' .
                    '<br><input type=button value="' . GetMessage("BITRIX_MPBUILDER_UDALITQ_VREMENNYE_FA") . '" onclick="if (confirm(\'' . GetMessage("BITRIX_MPBUILDER_UDALITQ_PAPKU") . ' &quot;/bitrix/tmp/' . $module_id . '&quot; ' . GetMessage("BITRIX_MPBUILDER_I_EE_SODERJIMOE") . '?\'))document.location=\'?action=delete&' . bitrix_sessid_get() . '\'">',
                "TYPE" => "OK",
                "HTML" => true]);
        } else {
            \CAdminMessage::ShowMessage([
                "MESSAGE" => GetMessage("BITRIX_MPBUILDER_OSIBKA_OBRABOTKI_FAY"),
                "DETAILS" => $strError,
                "TYPE" => "ERROR",
                "HTML" => true]);
        }
    }
}


?>
<form action="<?php echo $APPLICATION->GetCurPage() ?>?lang=<?= LANG ?>" method="POST"
      enctype="multipart/form-data">
    <?php
    $editTab->Begin();
    $editTab->BeginNextTab();
    echo bitrix_sessid_post();
    ?>
    <tr class=heading>
        <td colspan=2><?= GetMessage("BITRIX_MPBUILDER_VYBOR_MODULA") ?></td>
    </tr>
    <tr>
        <td><?= GetMessage("BITRIX_MPBUILDER_TEKUSIY_MODULQ") ?></td>
        <td>
            <select name=module_id onchange="document.location='?module_id='+this.value">
                <option></option>
                <?php
                $arModules = [];
                $dir = opendir($path = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules');
                while (false !== $item = readdir($dir)) {
                    if ($item == '.' || $item == '..' || !is_dir($path . '/' . $item) || !strpos($item, '.'))
                        continue;
                    $arModules[$item] = '<option value="' . $item . '" ' . ($module_id == $item ? 'selected' : '') . '>' . $item . '</option>';
                }
                closedir($dir);
                asort($arModules);
                echo implode("\n", $arModules);
                ?>
            </select>
        </td>
    </tr>
    <?php
    if (isset($module_id) && isset($moduleBuilder)) {
        include($moduleBuilder->getRootFileVersionPath());
        ?>
        <tr>
            <td><?= GetMessage("BITRIX_MPBUILDER_VERSIA_MODULA") ?></td>
            <td><input name="version"
                       value="<?= htmlspecialcharsbx(VersionUp($arModuleVersion['VERSION'])) ?>"
                       id='version_field' disabled> <label><input type=checkbox
                                                                  onchange="document.getElementById('version_field').disabled=!this.checked"> <?= GetMessage("BITRIX_MPBUILDER_OBNOVITQ_VERSIU") ?>
                </label></td>

        </tr>
        <?php
    }

    $editTab->Buttons();
    ?>
    <input type="submit" name=save value="<?= GetMessage("BITRIX_MPBUILDER_PRODOLJITQ") ?>">
</form>
<?php
$editTab->End();

require($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/include/epilog_admin.php");
?>
