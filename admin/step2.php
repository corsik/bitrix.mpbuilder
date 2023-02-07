<?php

namespace Bitrix\MpBuilder;

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/prolog.php");

global $USER;
global $APPLICATION;

if (!$USER->IsAdmin()) {
    $APPLICATION->AuthForm();
}

IncludeModuleLangFile(__FILE__);
$APPLICATION->SetTitle(GetMessage("BITRIX_MPBUILDER_SAG_VTOROY_VYDELENI"));
require($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/include/prolog_admin_after.php");

$aTabs = [
    ["DIV" => "tab1", "TAB" => GetMessage("BITRIX_MPBUILDER_SAG"), "ICON" => "main_user_edit", "TITLE" => GetMessage("BITRIX_MPBUILDER_VYDELENIE_AZYKOVYH_F")],
];
$editTab = new \CAdminTabControl("editTab", $aTabs, true, true);

echo BeginNote() .
    GetMessage("BITRIX_MPBUILDER_PERED_PUBLIKACIEY_MO") .
    "<br>" . GetMessage("BITRIX_MPBUILDER_ISHODNYE_FAYLY_BUDUT") .
    EndNote();

$module_id = '';
$_REQUEST['module_id'] = str_replace(['..', '/', '\\'], '', $_REQUEST['module_id']);
if ($_REQUEST['module_id'] && is_dir($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $_REQUEST['module_id']))
    $module_id = $_SESSION['mpbuilder']['module_id'] = $_REQUEST['module_id'];
else
    $module_id = $_SESSION['mpbuilder']['module_id'];
$m_dir = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $module_id;


if ($_POST['save'] && $module_id && check_bitrix_sessid()) {
    $strError = '';
    foreach ($_REQUEST['work'] as $file => $val) {
        if (!$val) {
            continue;
        }

        $lang_file = GetLangPath($file, $m_dir);
        if ($CBL = new CBuilderLang($m_dir, $file, $lang_file)) {
            $CBL->strLangPrefix = strtoupper(str_replace('.', '_', $module_id)) . '_';
            $CBL->Parse();
            if (!$CBL->Save()) {
                $strError .= GetMessage("BITRIX_MPBUILDER_OSIBKA_SOHRANENIA_FA") . htmlspecialcharsbx($m_dir . $file) . '<br>';
            }
        } else {
            $strError .= GetMessage("BITRIX_MPBUILDER_OSIBKA_OTKRYTIA_FAYL") . htmlspecialcharsbx($m_dir . $file) . '<br>';
        }
    }

    if ($strError)
        CAdminMessage::ShowMessage([
            "MESSAGE" => GetMessage("BITRIX_MPBUILDER_OSIBKA_OBRABOTKI_FAY"),
            "DETAILS" => $strError,
            "TYPE" => "ERROR",
            "HTML" => true]);
    else
        LocalRedirect('/bitrix/admin/bitrix.mpbuilder_step3.php?module_id=' . $module_id . '&lang=' . LANGUAGE_ID);
}

?>
<form action="<? echo $APPLICATION->GetCurPage() ?>?lang=<?= LANG ?>" method="POST" name="file_form">
    <?
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
                <?
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
    <?
    if ($module_id) {
        ?>
        <tr class=heading>
            <td colspan=2><?= GetMessage("BITRIX_MPBUILDER_VYBOR_FAYLOV") ?></td>
        </tr>
        <tr>
            <td colspan=2 align=center>
                <table border="0" cellpadding="0" cellspacing="0" class="internal">
                    <tr class="heading">
                        <td class="align-left"><input type=checkbox onclick="SwitchAll(this)" checked></td>
                        <td align="center"><?= GetMessage("BITRIX_MPBUILDER_VKLUCITQ_OBRABOTKU_F") ?></td>
                        <td align="center"><?= GetMessage("BITRIX_MPBUILDER_TEKUSAA_KODIROVKA_FA") ?></td>
                    </tr>
                    <?
                    $arFileList = Filesystem::getFiles($m_dir, ['lang']);
                    foreach ($arFileList as $file) {

                        $str = file_get_contents($m_dir . $file);
                        $charset = Filesystem::getStringCharset($str);
                        if ($charset == 'ascii')
                            continue;

//		echo $file.' =&gt; '.GetLangPath($file, $m_dir).'<br>';
                        ?>
                        <tr>
                            <td colspan=2>
                                <label><input type=checkbox
                                              name="work[<?= htmlspecialcharsbx($file) ?>]" <?= $_POST['save'] && !$_REQUEST['work'][$file] ? '' : 'checked' ?>> <?= htmlspecialcharsbx($file) ?>
                                </label>
                            </td>
                            <td align=center><?= $charset ?></td>
                        </tr>
                        <?
                    }
                    ?>
                </table>
                <script>
                    function SwitchAll(all) {
                        frm = document.forms.file_form;
                        l = frm.elements.length;
                        for (i = 0; i < l; i++) {
                            ob = frm.elements[i];
                            if (ob.type == 'checkbox' && ob != all)
                                ob.checked = all.checked;
                        }
                    }
                </script>
            </td>
        </tr>
        <?
    }

    $editTab->Buttons();
    ?>
    <input type="submit" name=save value="<?= GetMessage("BITRIX_MPBUILDER_PRODOLJITQ") ?>">
    <?
    $editTab->End();
    ?>
</form>
<?


?>
<?
require($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/include/epilog_admin.php");
?>
