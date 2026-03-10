const AUTO_START = '// @mpbuilder-auto-start';
const AUTO_END = '// @mpbuilder-auto-end';

function parseExistingCopyDirs(code)
{
	const dirs = new Set();
	const re = /\$updater->CopyFiles\(\s*["']install\/([^"']+)["']\s*,\s*["'][^"']*["']\s*\)/g;
	let match;

	while ((match = re.exec(code)) !== null)
	{
		dirs.add(match[1]);
	}

	return dirs;
}

function parseExistingDeleteFiles(code)
{
	const files = new Set();
	const re = /\$filesToDelete\s*=\s*\[([\s\S]*?)\]/g;
	let match;

	while ((match = re.exec(code)) !== null)
	{
		const entries = match[1].matchAll(/['"]([^'"]+)['"]/g);
		for (const entry of entries)
		{
			files.add(entry[1]);
		}
	}

	return files;
}

function removeOldBlocks(code)
{
	const autoStartIdx = code.indexOf(AUTO_START);
	const autoEndIdx = code.indexOf(AUTO_END);
	let cleanCode = code;

	if (autoStartIdx !== -1 && autoEndIdx !== -1 && autoEndIdx > autoStartIdx)
	{
		cleanCode = code.substring(0, autoStartIdx).trimEnd()
			+ '\n'
			+ code.substring(autoEndIdx + AUTO_END.length);
	}

	cleanCode = cleanCode.replace(
		/\n*if\s*\(\s*IsModuleInstalled\([^)]+\)\s*\)\s*\{[^}]*\$updater->CopyFiles[^}]*\}\n*/gs,
		'\n',
	);

	cleanCode = cleanCode.replace(
		/\n*if\s*\(\s*\$updater->canUpdateKernel\(\)\s*\)\s*\{[\s\S]*?\$filesToDelete[\s\S]*?^\}\n*/gm,
		'\n',
	);

	return cleanCode;
}

function generateAutoBlock(moduleId, copyDirs, deleteFiles, prevVersion)
{
	const lines = [];
	lines.push(AUTO_START);

	if (copyDirs.size > 0)
	{
		lines.push('');
		lines.push(`if (IsModuleInstalled('${moduleId}'))`);
		lines.push('{');

		for (const dir of copyDirs)
		{
			lines.push(`\t$updater->CopyFiles("install/${dir}", "${dir}");`);
		}

		lines.push('}');
	}

	if (deleteFiles.size > 0)
	{
		lines.push('');
		lines.push('if ($updater->canUpdateKernel())');
		lines.push('{');

		if (prevVersion)
		{
			lines.push(`\t// Files removed since ${prevVersion}`);
		}

		lines.push('\t$filesToDelete = [');

		for (const f of deleteFiles)
		{
			lines.push(`\t\t'${f}',`);
		}

		lines.push('\t];');
		lines.push('');
		lines.push('\tforeach ($filesToDelete as $fileName)');
		lines.push('\t{');
		lines.push("\t\tCUpdateSystem::deleteDirFilesEx(");
		lines.push("\t\t\t$_SERVER['DOCUMENT_ROOT'] . $updater->kernelPath . '/' . $fileName");
		lines.push('\t\t);');
		lines.push('\t}');
		lines.push('}');
	}

	lines.push(AUTO_END);

	return lines.join('\n');
}

function buildDeleteFilePaths(deletedFiles, moduleId)
{
	const publicDirs = new Set([
		'components', 'js', 'css', 'admin', 'themes', 'images', 'tools',
	]);

	const files = new Set();

	for (const file of deletedFiles)
	{
		files.add(`modules/${moduleId}${file}`);

		if (file.startsWith('/install/'))
		{
			const relPath = file.substring('/install/'.length);
			const topDir = relPath.split('/')[0];

			if (publicDirs.has(topDir))
			{
				files.add(relPath);
			}
		}
	}

	return files;
}

export function applyAutoBlock(currentUpdater, data)
{
	const moduleId = data.moduleId;
	const newDirs = data.changedInstallDirs || [];
	const newDeletedFiles = data.deletedFiles || [];

	const existingDirs = parseExistingCopyDirs(currentUpdater);
	const existingFiles = parseExistingDeleteFiles(currentUpdater);

	const mergedDirs = new Set([...existingDirs, ...newDirs]);
	const newFilePaths = buildDeleteFilePaths(newDeletedFiles, moduleId);
	const mergedFiles = new Set([...existingFiles, ...newFilePaths]);

	let cleanUpdater = removeOldBlocks(currentUpdater);

	const autoBlock = generateAutoBlock(
		moduleId,
		mergedDirs,
		mergedFiles,
		data.prevVersion || '',
	);

	cleanUpdater = cleanUpdater.trimEnd();
	const closeIdx = cleanUpdater.lastIndexOf('?>');

	if (closeIdx !== -1)
	{
		return cleanUpdater.substring(0, closeIdx).trimEnd()
			+ '\n\n' + autoBlock + '\n\n'
			+ cleanUpdater.substring(closeIdx);
	}

	return cleanUpdater + '\n\n' + autoBlock;
}
