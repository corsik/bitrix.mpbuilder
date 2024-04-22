<?php

namespace Bitrix\MpBuilder;

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/prolog.php");

use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Localization\Loc;

global $USER;
global $APPLICATION;

if (!$USER->IsAdmin())
{
	$APPLICATION->AuthForm();
}

IncludeModuleLangFile(__FILE__);
$MODULE_ID = 'bitrix.mpbuilder';

$APPLICATION->SetTitle(Loc::getMessage("BITRIX_MPBUILDER_SAG_CETVERTYY_SBORK"));
require($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/include/prolog_admin_after.php");

$aTabs = [
	[
		"DIV" => "tab1",
		"TAB" => Loc::getMessage("BITRIX_MPBUILDER_SAG"),
		"ICON" => "main_user_edit",
		"TITLE" => Loc::getMessage("BITRIX_MPBUILDER_SBORKA_OBNOVLENIA"),
	],
];
$editTab = new \CAdminTabControl("editTab", $aTabs);

$version = $_REQUEST['version'];
$actualVersion = null;

echo BeginNote()
	. Loc::getMessage("BITRIX_MPBUILDER_V_ARHIV_POPADUT_FAYL")
	. ' install/version.php. '
	. Loc::getMessage("BITRIX_MPBUILDER_OBNOVLENIE_NEOBHODIM")
	. ' <a href="https://partners.1c-bitrix.ru/personal/modules/modules.php?ACTIVE=Y" target="_blank">marketplace</a>.'
	. EndNote();

$moduleId = '';
$arModuleVersion = [];
$_REQUEST['module_id'] = str_replace(['..', '/', '\\'], '', $_REQUEST['module_id']);

if ($_REQUEST['module_id'] && is_dir($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $_REQUEST['module_id']))
{
	$moduleId = $_SESSION['mpbuilder']['module_id'] = $_REQUEST['module_id'];
}
else
{
	$moduleId = $_SESSION['mpbuilder']['module_id'];
}

if ($moduleId)
{

	$moduleBuilder = new Module($moduleId);
	$bitrixComponentRootPath = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/components';

	if ($_REQUEST['action'] === 'version_restore' && check_bitrix_sessid())
	{
		rename($moduleBuilder->getRootDirPath() . '/install/_version.php', $moduleBuilder->getRootFileVersionPath());
	}

	if (file_exists($moduleBuilder->getRootFileVersionPath()))
	{
		include($moduleBuilder->getRootFileVersionPath());
	}

	$NAMESPACE = \COption::GetOptionString($moduleId, 'NAMESPACE', '');

	if ($_REQUEST['action'] === 'delete' && check_bitrix_sessid())
	{
		Filesystem::rmDir($moduleBuilder->getRootTmpDirPath());
	}
	elseif ($_POST['save'] && check_bitrix_sessid())
	{
		$strError = '';
		$strFileList = '<br><br> <b>' . Loc::getMessage("BITRIX_MPBUILDER_SPISOK_FAYLOV_V_ARHI") . ':</b><br>';

		if ($bCustomNameSpace = array_key_exists('NAMESPACE', $_REQUEST))
		{
			\COption::SetOptionString(
				$moduleId,
				'NAMESPACE',
				$NAMESPACE = str_replace(['/', '\\', ' '], '', $_REQUEST['NAMESPACE'])
			);
		}

		if (!$version)
		{
			$strError .= Loc::getMessage("BITRIX_MPBUILDER_VERSIA_MODULA_NE_UKAZANA") . '<br>';
		}

		if (!$_REQUEST['description'])
		{
			$strError .= Loc::getMessage("BITRIX_MPBUILDER_NE_UKAZANO_OPISANIE") . '<br>';
		}

		if (!$strError && $_REQUEST['store_version'])
		{

			rename(
				$moduleBuilder->getRootFileVersionPath(),
				$moduleBuilder->getRootDirPath() . '/install/_version.php'
			);

			if (
				!file_put_contents(
					$moduleBuilder->getRootFileVersionPath(),
					$moduleBuilder->getContextVersion($version)
				)
			)
			{
				$strError .= Loc::getMessage("BITRIX_MPBUILDER_NE_UDALOSQ_ZAPISATQ")
					. $moduleBuilder->getRootFileVersionPath()
					. '<br>';
			}
		}

		if (!$strError)
		{
			if (is_dir($moduleBuilder->getRootTmpDirPath()))
			{
				Filesystem::rmDir($moduleBuilder->getRootTmpDirPath());
			}

			mkdir($moduleBuilder->getRootDirVersionPath($version), BX_DIR_PERMISSIONS, true);

			if (function_exists('mb_internal_encoding'))
			{
				mb_internal_encoding('ISO-8859-1');
			}

			if (!$strError && $_REQUEST['components'])
			{
				$ar = [];
				$componentDir = opendir($moduleBuilder->getRootDirComponentPath()); // let's get components list
				if ($bCustomNameSpace)
				{
					while (false !== $item = readdir($componentDir))
					{
						if ($item === '.' || $item === '..')
						{
							continue;
						}

						if (is_dir($f = $bitrixComponentRootPath . '/' . $NAMESPACE . '/' . $item))
						{
							$arTmp = Filesystem::getFiles($f, [], true);
							foreach ($arTmp as $file)
							{
								$ar[] = '/' . $NAMESPACE . '/' . $item . $file;
							}
						}
					}
					closedir($componentDir);
				}
				else
				{
					while (false !== $item = readdir($componentDir))
					{
						if (
							$item === '.' || $item === '..'
							|| !is_dir(
								$path0 = $moduleBuilder->getRootDirComponentPath() . '/' . $item
							)
						)
						{
							continue;
						}

						$dir0 = opendir($path0);
						while (false !== $item0 = readdir($dir0))
						{
							if ($item0 == '.' || $item0 == '..' || !is_dir($f = $path0 . '/' . $item0))
							{
								continue;
							}

							$arTmp = Filesystem::getFiles(
								$bitrixComponentRootPath . '/' . $item . '/' . $item0,
								[],
								true
							);

							foreach ($arTmp as $file)
							{
								$ar[] = '/' . $item . '/' . $item0 . $file;
							}
						}
						closedir($dir0);
					}
					closedir($componentDir);
				}

				foreach ($ar as $file)
				{
					$from = $bitrixComponentRootPath . $file;
					$to = $moduleBuilder->getRootDirComponentPath() . ($bCustomNameSpace ? preg_replace(
							'#^/[^/]+#',
							'',
							$file
						) : $file);

					if (!file_exists($to) || filemtime($from) > filemtime($to))
					{
						if (!is_dir($d = dirname($to)) && !mkdir($d, BX_DIR_PERMISSIONS, true) && !is_dir($d))
						{
							$strError .= Loc::getMessage("BITRIX_MPBUILDER_NE_SOZDATQ_PAPKU") . $d . '<br>';
						}
						elseif (!copy($from, $to))
						{
							$strError .= Loc::getMessage("BITRIX_MPBUILDER_NE_UDALOSQ_SKOPIROVA") . $from . '<br>';
						}
						else
						{
							touch($to, filemtime($from));
						}
					}
				}
			}

			$originalModuleFiles = Filesystem::getFiles($moduleBuilder->getRootDirPath(), [], true);
			$time_from = strtotime($arModuleVersion['VERSION_DATE']);
			$tmpDirStrLen = strlen($moduleBuilder->getRootTmpDirPath());
			foreach ($originalModuleFiles as $file)
			{
				$fromFile = $moduleBuilder->getRootDirPath() . $file;
				$toFile = $moduleBuilder->getRootDirVersionPath($version) . $file;

				if ($file === '/install/_version.php')
				{
					continue;
				}

				if ($file === '/install/version.php')
				{

					if (
						$_REQUEST['store_version']
						&& !file_put_contents(
							$fromFile,
							$moduleBuilder->getContextVersion($version)
						)
					)
					{
						$strError .= Loc::getMessage("BITRIX_MPBUILDER_NOT_WRITE_NEW_VERSION") . $fromFile . '<br>';
					}

					if (!file_exists($dir = dirname($toFile)))
					{
						mkdir($dir, BX_DIR_PERMISSIONS, true);
					}

					if (!file_put_contents($toFile, $moduleBuilder->getContextVersion($version)))
					{
						$strError .= Loc::getMessage("BITRIX_MPBUILDER_NOT_WRITE_NEW_VERSION") . $toFile . '<br>';
					}
					else
					{
						$strFileList .= substr($toFile, $tmpDirStrLen) . '<br>';
					}

					continue;
				}

				if (filemtime($fromFile) < $time_from)
				{
					continue;
				}

				$fileContents = file_get_contents($fromFile);
				if (!$fileContents)
				{
					$strError .= Loc::getMessage("BITRIX_MPBUILDER_NE_UDALOSQ_PROCITATQ") . $fromFile . '<br>';
				}
				else
				{
					if (substr($file, -4) == '.php' && Filesystem::getStringCharset($fileContents) == 'utf8')
					{
						$fileContents = Encoding::convertEncoding($fileContents, 'utf8', 'cp1251');
					}

					if (!file_exists($dir = dirname($toFile)))
					{
						mkdir($dir, BX_DIR_PERMISSIONS, true);
					}

					if (!file_put_contents($toFile, $fileContents))
					{
						$strError .= Loc::getMessage("BITRIX_MPBUILDER_NE_UDALOSQ_SOHRANITQ") . $toFile . '<br>';
					}
					else
					{
						$strFileList .= substr($toFile, $tmpDirStrLen) . '<br>';
					}
				}
			}

			if (!$strError)
			{
				$descriptionFilePath = $moduleBuilder->getRootDirVersionPath($version) . '/description.ru';
				$description = $_REQUEST['description'];
				if (defined('BX_UTF') && BX_UTF)
				{
					$description = Encoding::convertEncoding($description, 'utf8', 'cp1251');
				}
				if (!file_put_contents($descriptionFilePath, $description))
				{
					$strError .= Loc::getMessage("BITRIX_MPBUILDER_NE_UDALOSQ_ZAPISATQ") . $descriptionFilePath . '<br>';
				}
				else
				{
					$strFileList .= substr($descriptionFilePath, $tmpDirStrLen) . '<br>';
				}
			}

			if (!$strError && ($str = trim($_REQUEST['updater'])))
			{
				/*
				  $str = file_get_contents($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/bitrix.mpbuilder/samples/_updater.php');
				  $str = str_replace('{MODULE_ID}', $moduleId, $str);
				*/
				$updaterFilePath = $moduleBuilder->getRootDirVersionPath($version) . '/updater.php';
				if (!file_put_contents($updaterFilePath, $str))
				{
					$strError .= Loc::getMessage("BITRIX_MPBUILDER_NE_UDALOSQ_SOHRANITQ") . $updaterFilePath;
				}
				else
				{
					$strFileList .= substr($updaterFilePath, $tmpDirStrLen) . '<br>';
				}
			}

			Filesystem::packFolder(
				$moduleBuilder->getRootDirVersionPath($version),
				$moduleBuilder->getRootTmpDirPath()
			);
		}

		if (!$strError)
		{
			$linkFolder = $moduleBuilder->getTmpDirPath() . '/';
			$filemanLink = "/bitrix/admin/fileman_admin.php?lang=ru&site=s1&path=" . UrlEncode($linkFolder);
			$link = $linkFolder . $version . '.tar.gz';
			$href = "/bitrix/admin/fileman_file_download.php?path=" . UrlEncode($link);
			\CAdminMessage::ShowMessage([
				"MESSAGE" => Loc::getMessage("BITRIX_MPBUILDER_OBNOVLENIE_SOBRANO"),
				"DETAILS" => '<a target="_blank" href="'
					. $filemanLink
					. '">'
					. Loc::getMessage(
						"BITRIX_MPBUILDER_FOLDER_OBNOVLENIA_MOJ"
					)
					. '</a>.'
					. '<br>'
					. Loc::getMessage(
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
					. Loc::getMessage("BITRIX_MPBUILDER_ZAGRUZITQ_V")
					. ' marketplace</a> '
					. '<br><input type=button value="'
					. Loc::getMessage("BITRIX_MPBUILDER_UDALITQ_VREMENNYE_FA")
					. '" onclick="if(confirm(\''
					. Loc::getMessage("BITRIX_MPBUILDER_UDALITQ_PAPKU")
					. ' &quot;/bitrix/tmp/'
					. $moduleId
					. '&quot; '
					. Loc::getMessage("BITRIX_MPBUILDER_I_EE_SODERJIMOE")
					. '?\'))document.location=\'?action=delete&'
					. bitrix_sessid_get()
					. '\'">'
					. $strFileList,
				"TYPE" => "OK",
				"HTML" => true,
			]);
		}
		else
		{
			\CAdminMessage::ShowMessage([
				"MESSAGE" => Loc::getMessage("BITRIX_MPBUILDER_OSIBKA_OBRABOTKI_FAY"),
				"DETAILS" => $strError,
				"TYPE" => "ERROR",
				"HTML" => true,
			]);
		}
	}
}

?>
<form action="<?php echo $APPLICATION->GetCurPage() ?>?lang=<?= LANG ?>"
		method="POST"
		enctype="multipart/form-data"
		name="builder_form">
	<?php
	echo bitrix_sessid_post();
	$editTab->Begin();
	$editTab->BeginNextTab();
	?>
	<tr class=heading>
		<td colspan=2><?= Loc::getMessage("BITRIX_MPBUILDER_VYBOR_MODULA") ?></td>
	</tr>
	<tr>
		<td><?= Loc::getMessage("BITRIX_MPBUILDER_TEKUSIY_MODULQ") ?></td>
		<td>
			<select name=module_id onchange="document.location='?module_id='+this.value">
				<?php
				$arModules = [];
				$modulesDer = opendir($path = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules');
				while (false !== $item = readdir($modulesDer))
				{
					if ($item === '.' || $item === '..' || !is_dir($path . '/' . $item) || !strpos($item, '.'))
					{
						continue;
					}

					$arModules[$item] = '<option value="' . $item . '" ' . ($moduleId == $item ? 'selected' : '') . '>' . $item . '</option>';
				}

				closedir($modulesDer);
				asort($arModules);

				echo implode("\n", $arModules);
				?>
			</select>
		</td>
	</tr>
	<?php
	if ($moduleId)
	{
		$actualVersion = $version ? htmlspecialcharsbx($version) : VersionUp($arModuleVersion['VERSION']);
		$updatesModule = $_SERVER['DOCUMENT_ROOT'] . "/dev/updates/$actualVersion";
		?>
		<tr>
			<td valign=top><?= Loc::getMessage("BITRIX_MPBUILDER_VERSIA_OBNOVLENIA") ?></td>
			<td>
				<input name="version" value="<?= $actualVersion ?>">
				<label>
					<input type=checkbox name=store_version <?= $_REQUEST['store_version'] ? 'checked' : '' ?>>
					<?= Loc::getMessage("BITRIX_MPBUILDER_OBNOVITQ_DATU_I_VERS") ?>
				</label>
				<?php
				if (file_exists($f = $moduleBuilder->getRootDirPath() . '/install/_version.php'))
				{
					include($f);
					if ($arModuleVersion['VERSION'] != $version)
					{
						echo '<br>'
							. Loc::getMessage("BITRIX_MPBUILDER_DOSTUPNA_VERSIA")
							. htmlspecialcharsbx($arModuleVersion['VERSION'])
							. '</b> '
							. Loc::getMessage("BITRIX_MPBUILDER_FILE")
							. ' version.php. <a href="javascript:if(confirm(\''
							. Loc::getMessage("BITRIX_MPBUILDER_VOSSTANOVITQ_STARUU")
							. '?\'))document.location=\'?action=version_restore&'
							. bitrix_sessid_get()
							. '\'">'
							. Loc::getMessage("BITRIX_MPBUILDER_VOSSTANOVITQ")
							. '</a>.';
					}
				}
				?>
			</td>
		</tr>
		<tr>
			<td valign=top><?= Loc::getMessage("BITRIX_MPBUILDER_OBRABOTKA_USTANOVLEN") ?></td>
			<td>
				<?php
				$bCustomNameSpace = false;
				if (!file_exists($moduleBuilder->getRootDirComponentPath()))
				{
					echo Loc::getMessage("BITRIX_MPBUILDER_MODULQ_NE_SODERJIT_K") . ' /install';
				}
				else
				{
					echo '<label><input type=checkbox onchange="if(ob=document.getElementById(\'NAMESPACE\'))ob.disabled=!this.checked;" name=components '
						. ($_REQUEST['components'] ? 'checked' : '')
						. '> '
						. Loc::getMessage("BITRIX_MPBUILDER_SKOPIROVATQ_IZMENENN")
						. ' /bitrix/components/ '
						. Loc::getMessage("BITRIX_MPBUILDER_V_ADRO_MODULA")
						. '</label>';

					$componentDir = opendir($moduleBuilder->getRootDirComponentPath());
					while (false !== $item = readdir($componentDir))
					{
						$path0 = $moduleBuilder->getRootDirComponentPath();

						if ($item === '.' || $item === '..' || !is_dir($path0 . '/' . $item))
						{
							continue;
						}

						if ($bCustomNameSpace = file_exists($path0 . '/component.php'))
						{
							break;
						}
					}
					closedir($componentDir);
				}
				?>
			</td>
		</tr>
		<?php
		if ($bCustomNameSpace)
		{
			?>
			<tr>
				<td><?= Loc::getMessage("BITRIX_MPBUILDER_PROSTRANSTVO_IMEN_KO") ?></td>
				<td>/bitrix/components/<input name=NAMESPACE
							id=NAMESPACE
							value="<?= htmlspecialcharsbx($NAMESPACE) ?>" <?= $_REQUEST['components']
						? '' : 'disabled' ?>>
				</td>
			</tr>
			<tr>
				<td></td>
				<td class=smalltext><?= Loc::getMessage("BITRIX_MPBUILDER_KOMPONENTY_MODULA_LE") ?>
					<i>install</i>, <?= Loc::getMessage("BITRIX_MPBUILDER_CTOBY_PROVERITQ_IZME") ?><br>
					<?= Loc::getMessage("BITRIX_MPBUILDER_TAKJE_UBEDITESQ_CTO") ?>
					<i>updater.php</i> <?= Loc::getMessage("BITRIX_MPBUILDER_PRAVILQNO_KOPIRUET_K") ?></td>
			</tr>
			<?
		}
		?>
		<tr>
			<td valign=top><?= Loc::getMessage("BITRIX_MPBUILDER_OPISANIE_OBNOVLENIA") ?></td>
			<td>
				<?php
				$description = '<ul>
	<li></li>
</ul>';
				$repoDescription = "$updatesModule/description.ru";
				$descriptionPath = $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . "/tmp/$moduleId/$version/description.ru";

				if (file_exists($repoDescription))
				{
					$description = file_get_contents($repoDescription);
				}
				elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $_REQUEST['updater'])
				{
					$updater = $_REQUEST['updater'];
				}
				elseif (file_exists($descriptionPath))
				{
					$description = file_get_contents($descriptionPath);
					if (defined('BX_UTF') && BX_UTF)
					{
						$description = Encoding::convertEncoding($description, 'cp1251', 'utf8');
					}
				}
				?>
				<textarea id="description__editor" name=description style="width:100%" rows=10><?= htmlspecialcharsbx(
						$description
					) ?></textarea>
				<?php
				if (Option::get('fileman', "use_code_editor", "Y") === "Y" && Loader::includeModule('fileman'))
				{
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
			<td class=smalltext><?= Loc::getMessage("BITRIX_MPBUILDER_PRI_PEREDACE_ISPOLNA") ?></td>
		</tr>
		<tr>
			<td valign=top><?= Loc::getMessage("BITRIX_MPBUILDER_SKRIPT_OBNOVLENIA") ?> updater.php:</td>
			<td>
				<?php
				$repoUpdater = "$updatesModule/updater.php";
				if (file_exists($repoUpdater))
				{
					$updater = file_get_contents($repoUpdater);
				}
				elseif ($_SERVER['REQUEST_METHOD'] === 'POST')
				{
					$updater = $_REQUEST['updater'];
				}
				else
				{
					$updater = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/bitrix.mpbuilder/samples/_updater.php');
					$updater = str_replace(['{MODULE_ID}', '{NAMESPACE}'], [
						$moduleId,
						$bCustomNameSpace ? $NAMESPACE : '',
					], $updater);
				}
				?>
				<textarea id="updater__editor"
						name=updater
						style="width:100%"
						rows=10><?= htmlspecialcharsbx($updater) ?>
				</textarea>
				<?php
				if (Option::get('fileman', "use_code_editor", "Y") === "Y" && Loader::includeModule('fileman'))
				{
					\CCodeEditor::Show([
						'textareaId' => 'updater__editor',
						'height' => 350,
						'forceSyntax' => 'php',
					]);
				}
				?>
			</td>
		</tr>
		<?php
	}
	$editTab->Buttons();
	?>
	<input type="submit" name=save value="<?= Loc::getMessage("BITRIX_MPBUILDER_PRODOLJITQ") ?>">
</form>

<?php
$editTab->End();
require($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/include/epilog_admin.php");
?>
