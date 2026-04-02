/* eslint-disable */
(function (exports) {
	'use strict';

	var APP_CONTAINER_ID = 'mpbuilder-update-app';
	var COMPONENT_NAME = 'bitrix.mpbuilder:builder.update';

	var appTemplate = "\n<div class=\"mpb-update\">\n\n\t<div class=\"mpb-update__layout\" :class=\"{ 'mpb-update__layout--split': prepareResult || buildResult }\">\n\t\t<div class=\"mpb-update__layout-main\">\n\n\t\t\t<div class=\"mpb-update__note\">\n\t\t\t\t<span class=\"ui-icon-set --info-circle mpb-update__note-icon\"></span>\n\t\t\t\t<div class=\"mpb-update__note-text\">{{ loc('MPBUILDER_UPDATE_NOTE') }}</div>\n\t\t\t</div>\n\n\t\t\t<div class=\"mpb-update__card\">\n\t\t\t\t<div class=\"mpb-update__card-header\">\n\t\t\t\t\t<h4 class=\"mpb-update__card-title\">{{ loc('MPBUILDER_UPDATE_SELECT_MODULE') }}</h4>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"mpb-update__card-body\">\n\t\t\t\t\t<div class=\"mpb-update__field\">\n\t\t\t\t\t\t<select\n\t\t\t\t\t\t\tclass=\"mpb-update__select\"\n\t\t\t\t\t\t\tv-model=\"selectedModuleId\"\n\t\t\t\t\t\t\t:disabled=\"isBuilding\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t<option value=\"\">{{ loc('MPBUILDER_UPDATE_SELECT_MODULE_PLACEHOLDER') }}</option>\n\t\t\t\t\t\t\t<option\n\t\t\t\t\t\t\t\tv-for=\"mod in modules\"\n\t\t\t\t\t\t\t\t:key=\"mod\"\n\t\t\t\t\t\t\t\t:value=\"mod\"\n\t\t\t\t\t\t\t>{{ mod }}</option>\n\t\t\t\t\t\t</select>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\n\t\t\t<template v-if=\"isLoadingModuleInfo\">\n\t\t\t\t<div class=\"mpb-update__card\">\n\t\t\t\t\t<div class=\"mpb-update__card-body mpb-update__loading\">\n\t\t\t\t\t\t<div class=\"mpb-update__spinner\"></div>\n\t\t\t\t\t\t<span>{{ loc('MPBUILDER_UPDATE_LOADING') }}</span>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</template>\n\n\t\t\t<template v-if=\"moduleInfo && !isLoadingModuleInfo\">\n\n\t\t\t\t<div class=\"mpb-update__card\" :class=\"{ 'mpb-update__card--disabled': isBuilding }\">\n\t\t\t\t\t<div class=\"mpb-update__card-header\">\n\t\t\t\t\t\t<h4 class=\"mpb-update__card-title\">{{ loc('MPBUILDER_UPDATE_VERSION_SETTINGS') }}</h4>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"mpb-update__card-body\">\n\t\t\t\t\t\t<div class=\"mpb-update__field\">\n\t\t\t\t\t\t\t<label class=\"mpb-update__label\">{{ loc('MPBUILDER_UPDATE_VERSION_LABEL') }}</label>\n\t\t\t\t\t\t\t<input\n\t\t\t\t\t\t\t\ttype=\"text\"\n\t\t\t\t\t\t\t\tclass=\"mpb-update__input\"\n\t\t\t\t\t\t\t\tv-model=\"version\"\n\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t</div>\n\n\t\t\t\t\t\t<div v-if=\"moduleInfo.isDevStrategyActive && devVersions.length > 0\" class=\"mpb-update__field\">\n\t\t\t\t\t\t\t<label class=\"mpb-update__label\">{{ loc('MPBUILDER_UPDATE_DEV_VERSIONS') }}</label>\n\t\t\t\t\t\t\t<div class=\"mpb-update__dev-versions\">\n\t\t\t\t\t\t\t\t<button\n\t\t\t\t\t\t\t\t\tclass=\"mpb-update__dev-version-tag\"\n\t\t\t\t\t\t\t\t\t:class=\"{ 'mpb-update__dev-version-tag--active': version === moduleInfo.nextVersion }\"\n\t\t\t\t\t\t\t\t\t:disabled=\"isLoadingDevVersion || isBuilding\"\n\t\t\t\t\t\t\t\t\t@click=\"selectNewVersion\"\n\t\t\t\t\t\t\t\t>{{ loc('MPBUILDER_UPDATE_DEV_VERSION_NEW') }}</button>\n\t\t\t\t\t\t\t\t<button\n\t\t\t\t\t\t\t\t\tv-for=\"ver in devVersions\"\n\t\t\t\t\t\t\t\t\t:key=\"ver\"\n\t\t\t\t\t\t\t\t\tclass=\"mpb-update__dev-version-tag\"\n\t\t\t\t\t\t\t\t\t:class=\"{ 'mpb-update__dev-version-tag--active': version === ver }\"\n\t\t\t\t\t\t\t\t\t:disabled=\"isLoadingDevVersion || isBuilding\"\n\t\t\t\t\t\t\t\t\t@click=\"selectDevVersion(ver)\"\n\t\t\t\t\t\t\t\t>{{ ver }}</button>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\n\t\t\t\t\t\t<div class=\"mpb-update__field\">\n\t\t\t\t\t\t\t<label class=\"mpb-update__checkbox\">\n\t\t\t\t\t\t\t\t<input type=\"checkbox\" v-model=\"storeVersion\" />\n\t\t\t\t\t\t\t\t<span class=\"mpb-update__checkbox-text\">{{ loc('MPBUILDER_UPDATE_STORE_VERSION') }}</span>\n\t\t\t\t\t\t\t</label>\n\t\t\t\t\t\t</div>\n\n\t\t\t\t\t\t<div v-if=\"moduleInfo.isDevStrategyActive && devVersions.length > 0\" class=\"mpb-update__field\">\n\t\t\t\t\t\t\t<label class=\"mpb-update__label\">{{ loc('MPBUILDER_UPDATE_BASE_VERSION') }}</label>\n\t\t\t\t\t\t\t<select\n\t\t\t\t\t\t\t\tclass=\"mpb-update__select\"\n\t\t\t\t\t\t\t\tv-model=\"baseVersion\"\n\t\t\t\t\t\t\t\t:disabled=\"isBuilding\"\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t<option value=\"\">{{ loc('MPBUILDER_UPDATE_BASE_VERSION_AUTO') }}</option>\n\t\t\t\t\t\t\t\t<option\n\t\t\t\t\t\t\t\t\tv-for=\"ver in devVersions\"\n\t\t\t\t\t\t\t\t\t:key=\"ver\"\n\t\t\t\t\t\t\t\t\t:value=\"ver\"\n\t\t\t\t\t\t\t\t>{{ ver }}</option>\n\t\t\t\t\t\t\t</select>\n\t\t\t\t\t\t\t<div class=\"mpb-update__hint\">{{ loc('MPBUILDER_UPDATE_BASE_VERSION_HINT') }}</div>\n\t\t\t\t\t\t</div>\n\n\t\t\t\t\t\t<div class=\"mpb-update__field\">\n\t\t\t\t\t\t\t<label class=\"mpb-update__checkbox\">\n\t\t\t\t\t\t\t\t<input type=\"checkbox\" v-model=\"useCustomDate\" />\n\t\t\t\t\t\t\t\t<span class=\"mpb-update__checkbox-text\">{{ loc('MPBUILDER_UPDATE_CUSTOM_DATE') }}</span>\n\t\t\t\t\t\t\t</label>\n\t\t\t\t\t\t\t<div v-if=\"useCustomDate\" class=\"mpb-update__field mpb-update__field--nested\">\n\t\t\t\t\t\t\t\t<input\n\t\t\t\t\t\t\t\t\ttype=\"datetime-local\"\n\t\t\t\t\t\t\t\t\tclass=\"mpb-update__input\"\n\t\t\t\t\t\t\t\t\tv-model=\"customDateFrom\"\n\t\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t\t\t<div class=\"mpb-update__hint\">{{ loc('MPBUILDER_UPDATE_CUSTOM_DATE_HINT') }}</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\n\t\t\t\t\t\t<div v-if=\"moduleInfo.backupVersion && !moduleInfo.isDevStrategyActive\" class=\"mpb-update__field\">\n\t\t\t\t\t\t\t<div class=\"mpb-update__warning-badge\">\n\t\t\t\t\t\t\t\t{{ loc('MPBUILDER_UPDATE_BACKUP_AVAILABLE') }}\n\t\t\t\t\t\t\t\t<strong>{{ moduleInfo.backupVersion }}</strong>.\n\t\t\t\t\t\t\t\t<a href=\"javascript:void(0)\" @click=\"restoreVersion\" class=\"mpb-update__link\">\n\t\t\t\t\t\t\t\t\t{{ loc('MPBUILDER_UPDATE_RESTORE') }}\n\t\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\n\t\t\t\t<div\n\t\t\t\t\tv-if=\"moduleInfo.hasComponents\"\n\t\t\t\t\tclass=\"mpb-update__card\"\n\t\t\t\t\t:class=\"{ 'mpb-update__card--disabled': isBuilding }\"\n\t\t\t\t>\n\t\t\t\t\t<div class=\"mpb-update__card-header\">\n\t\t\t\t\t\t<h4 class=\"mpb-update__card-title\">{{ loc('MPBUILDER_UPDATE_COMPONENTS') }}</h4>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"mpb-update__card-body\">\n\t\t\t\t\t\t<div class=\"mpb-update__field\">\n\t\t\t\t\t\t\t<label class=\"mpb-update__checkbox\">\n\t\t\t\t\t\t\t\t<input type=\"checkbox\" v-model=\"components\" />\n\t\t\t\t\t\t\t\t<span class=\"mpb-update__checkbox-text\">{{ loc('MPBUILDER_UPDATE_COPY_COMPONENTS') }}</span>\n\t\t\t\t\t\t\t</label>\n\t\t\t\t\t\t</div>\n\n\t\t\t\t\t\t<div v-if=\"moduleInfo.hasCustomNamespace && components\" class=\"mpb-update__field\">\n\t\t\t\t\t\t\t<label class=\"mpb-update__label\">{{ loc('MPBUILDER_UPDATE_NAMESPACE_LABEL') }}</label>\n\t\t\t\t\t\t\t<div class=\"mpb-update__input-prefix\">\n\t\t\t\t\t\t\t\t<span class=\"mpb-update__input-prefix-text\">/bitrix/components/</span>\n\t\t\t\t\t\t\t\t<input\n\t\t\t\t\t\t\t\t\ttype=\"text\"\n\t\t\t\t\t\t\t\t\tclass=\"mpb-update__input\"\n\t\t\t\t\t\t\t\t\tv-model=\"namespace\"\n\t\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"mpb-update__hint\">{{ loc('MPBUILDER_UPDATE_NAMESPACE_HINT') }}</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\n\t\t\t\t<div\n\t\t\t\t\tv-if=\"!moduleInfo.hasComponents\"\n\t\t\t\t\tclass=\"mpb-update__card\"\n\t\t\t\t\t:class=\"{ 'mpb-update__card--disabled': isBuilding }\"\n\t\t\t\t>\n\t\t\t\t\t<div class=\"mpb-update__card-header\">\n\t\t\t\t\t\t<h4 class=\"mpb-update__card-title\">{{ loc('MPBUILDER_UPDATE_COMPONENTS') }}</h4>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"mpb-update__card-body\">\n\t\t\t\t\t\t<div class=\"mpb-update__hint\">{{ loc('MPBUILDER_UPDATE_NO_COMPONENTS') }}</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\n\t\t\t\t<div :key=\"editorKey\" class=\"mpb-update__card\" :class=\"{ 'mpb-update__card--disabled': isBuilding }\">\n\t\t\t\t\t<div class=\"mpb-update__card-header\">\n\t\t\t\t\t\t<h4 class=\"mpb-update__card-title\">{{ loc('MPBUILDER_UPDATE_CONTENT') }}</h4>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"mpb-update__card-body\">\n\t\t\t\t\t\t<div class=\"mpb-update__field\">\n\t\t\t\t\t\t\t<div class=\"mpb-update__field-header\">\n\t\t\t\t\t\t\t\t<label class=\"mpb-update__label\">{{ loc('MPBUILDER_UPDATE_DESCRIPTION_LABEL') }}</label>\n\t\t\t\t\t\t\t\t<div v-if=\"moduleInfo.isDevStrategyActive\" class=\"mpb-update__dev-tools\">\n\t\t\t\t\t\t\t\t\t<button\n\t\t\t\t\t\t\t\t\t\tclass=\"mpb-update__button mpb-update__button--tool\"\n\t\t\t\t\t\t\t\t\t\t:disabled=\"!version || isBuilding || isSavingDescription\"\n\t\t\t\t\t\t\t\t\t\t@click=\"saveDescription\"\n\t\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t\t<template v-if=\"isSavingDescription\">\n\t\t\t\t\t\t\t\t\t\t\t<span class=\"mpb-update__spinner mpb-update__spinner--xs mpb-update__spinner--secondary\"></span>\n\t\t\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t\t\t{{ loc('MPBUILDER_UPDATE_SAVE_DESCRIPTION') }}\n\t\t\t\t\t\t\t\t\t</button>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div v-if=\"descriptionSaveInfo\" class=\"mpb-update__structure-hint\" :class=\"{ 'mpb-update__structure-hint--error': !descriptionSaveInfo.success }\">\n\t\t\t\t\t\t\t\t<template v-if=\"descriptionSaveInfo.success\">\n\t\t\t\t\t\t\t\t\t{{ loc('MPBUILDER_UPDATE_DESCRIPTION_SAVED') }}\n\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t\t\t\t{{ descriptionSaveInfo.error }}\n\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<textarea\n\t\t\t\t\t\t\t\tid=\"mpb-description-editor\"\n\t\t\t\t\t\t\t\tclass=\"mpb-update__textarea\"\n\t\t\t\t\t\t\t\tv-model=\"description\"\n\t\t\t\t\t\t\t\trows=\"8\"\n\t\t\t\t\t\t\t></textarea>\n\t\t\t\t\t\t\t<div class=\"mpb-update__hint\">{{ loc('MPBUILDER_UPDATE_DESCRIPTION_HINT') }}</div>\n\t\t\t\t\t\t</div>\n\n\t\t\t\t\t\t<div class=\"mpb-update__field\">\n\t\t\t\t\t\t\t<div class=\"mpb-update__field-header\">\n\t\t\t\t\t\t\t\t<label class=\"mpb-update__label\">{{ loc('MPBUILDER_UPDATE_UPDATER_LABEL') }}</label>\n\t\t\t\t\t\t\t\t<div v-if=\"moduleInfo.isDevStrategyActive\" class=\"mpb-update__dev-tools\">\n\t\t\t\t\t\t\t\t\t<button\n\t\t\t\t\t\t\t\t\t\tclass=\"mpb-update__button mpb-update__button--tool\"\n\t\t\t\t\t\t\t\t\t\t:disabled=\"!version || isBuilding\"\n\t\t\t\t\t\t\t\t\t\t@click=\"analyzeStructure\"\n\t\t\t\t\t\t\t\t\t\t:title=\"loc('MPBUILDER_UPDATE_ANALYZE_STRUCTURE_HINT')\"\n\t\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t\t<template v-if=\"isAnalyzingStructure\">\n\t\t\t\t\t\t\t\t\t\t\t<span class=\"mpb-update__spinner mpb-update__spinner--xs mpb-update__spinner--secondary\"></span>\n\t\t\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t\t\t\t\t\t<span class=\"ui-icon-set --magic-wand\"></span>\n\t\t\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t\t\t{{ loc('MPBUILDER_UPDATE_ANALYZE_STRUCTURE') }}\n\t\t\t\t\t\t\t\t\t</button>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<textarea\n\t\t\t\t\t\t\t\tid=\"mpb-updater-editor\"\n\t\t\t\t\t\t\t\tclass=\"mpb-update__textarea mpb-update__textarea--mono\"\n\t\t\t\t\t\t\t\tv-model=\"updater\"\n\t\t\t\t\t\t\t\trows=\"8\"\n\t\t\t\t\t\t\t></textarea>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\n\t\t\t\t<div class=\"mpb-update__actions\" v-if=\"!prepareResult && !buildResult\">\n\t\t\t\t\t<button\n\t\t\t\t\t\tclass=\"mpb-update__button mpb-update__button--primary\"\n\t\t\t\t\t\t:disabled=\"!canPrepare\"\n\t\t\t\t\t\t@click=\"prepareUpdate\"\n\t\t\t\t\t>\n\t\t\t\t\t\t<template v-if=\"isPreparing\">\n\t\t\t\t\t\t\t<span class=\"mpb-update__spinner mpb-update__spinner--sm\"></span>\n\t\t\t\t\t\t\t{{ loc('MPBUILDER_UPDATE_PREPARING') }}\n\t\t\t\t\t\t</template>\n\t\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t\t{{ loc('MPBUILDER_UPDATE_PREPARE_BUTTON') }}\n\t\t\t\t\t\t</template>\n\t\t\t\t\t</button>\n\t\t\t\t\t<button\n\t\t\t\t\t\tv-if=\"moduleInfo.isDevStrategyActive\"\n\t\t\t\t\t\tclass=\"mpb-update__button mpb-update__button--structure\"\n\t\t\t\t\t\t:disabled=\"!version || isBuilding\"\n\t\t\t\t\t\t@click=\"generateStructure\"\n\t\t\t\t\t\t:title=\"loc('MPBUILDER_UPDATE_GENERATE_STRUCTURE_HINT')\"\n\t\t\t\t\t>\n\t\t\t\t\t\t<template v-if=\"isGeneratingStructure\">\n\t\t\t\t\t\t\t<span class=\"mpb-update__spinner mpb-update__spinner--sm\"></span>\n\t\t\t\t\t\t</template>\n\t\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t\t<span class=\"ui-icon-set --folders\"></span>\n\t\t\t\t\t\t</template>\n\t\t\t\t\t\t{{ loc('MPBUILDER_UPDATE_GENERATE_STRUCTURE') }}\n\t\t\t\t\t</button>\n\t\t\t\t\t<div v-if=\"structureInfo\" class=\"mpb-update__structure-hint\" :class=\"{ 'mpb-update__structure-hint--error': !structureInfo.success }\">\n\t\t\t\t\t\t<template v-if=\"structureInfo.success\">\n\t\t\t\t\t\t\t{{ loc('MPBUILDER_UPDATE_STRUCTURE_SAVED') }} ({{ structureInfo.count }})\n\t\t\t\t\t\t</template>\n\t\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t\t{{ structureInfo.error }}\n\t\t\t\t\t\t</template>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\n\t\t\t</template>\n\n\t\t</div>\n\n\t\t<div v-if=\"prepareResult || buildResult\" class=\"mpb-update__layout-sidebar\">\n\n\t\t\t<div v-if=\"prepareResult\" class=\"mpb-update__card mpb-update__card--preview\">\n\t\t\t\t<div class=\"mpb-update__card-header mpb-update__card-header--preview\">\n\t\t\t\t\t<h4 class=\"mpb-update__card-title\">\n\t\t\t\t\t\t<span class=\"ui-icon-set --file\"></span>\n\t\t\t\t\t\t{{ loc('MPBUILDER_UPDATE_PREVIEW_TITLE') }}\n\t\t\t\t\t</h4>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"mpb-update__card-body\">\n\t\t\t\t\t<div class=\"mpb-update__preview-summary\">\n\t\t\t\t\t\t<div class=\"mpb-update__preview-info\">\n\t\t\t\t\t\t\t<span class=\"mpb-update__preview-info-label\">{{ loc('MPBUILDER_UPDATE_PREVIEW_MODULE') }}</span>\n\t\t\t\t\t\t\t<span class=\"mpb-update__preview-info-value\">{{ selectedModuleId }}</span>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"mpb-update__preview-info\">\n\t\t\t\t\t\t\t<span class=\"mpb-update__preview-info-label\">{{ loc('MPBUILDER_UPDATE_PREVIEW_VERSION') }}</span>\n\t\t\t\t\t\t\t<span class=\"mpb-update__preview-info-value\">{{ version }}</span>\n\t\t\t\t\t\t</div>\n\n\t\t\t\t\t\t<div class=\"mpb-update__preview-stats\">\n\t\t\t\t\t\t\t<div class=\"mpb-update__preview-stat\">\n\t\t\t\t\t\t\t\t<span class=\"mpb-update__preview-stat-value mpb-update__preview-stat-value--included\">{{ filteredIncludedFiles.length }}</span>\n\t\t\t\t\t\t\t\t<span class=\"mpb-update__preview-stat-label\">{{ loc('MPBUILDER_UPDATE_FILES_INCLUDED') }}</span>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"mpb-update__preview-stat\">\n\t\t\t\t\t\t\t\t<span class=\"mpb-update__preview-stat-value mpb-update__preview-stat-value--excluded\">{{ prepareResult.excludedCount }}</span>\n\t\t\t\t\t\t\t\t<span class=\"mpb-update__preview-stat-label\">{{ loc('MPBUILDER_UPDATE_FILES_EXCLUDED') }}</span>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div v-if=\"prepareResult.skippedByDate > 0\" class=\"mpb-update__preview-stat\">\n\t\t\t\t\t\t\t\t<span class=\"mpb-update__preview-stat-value\">{{ prepareResult.skippedByDate }}</span>\n\t\t\t\t\t\t\t\t<span class=\"mpb-update__preview-stat-label\">{{ loc('MPBUILDER_UPDATE_FILES_SKIPPED') }}</span>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\n\t\t\t\t\t\t<div v-if=\"prepareResult.versionDate\" class=\"mpb-update__preview-date\">\n\t\t\t\t\t\t\t{{ loc('MPBUILDER_UPDATE_FILES_SINCE') }}: {{ prepareResult.versionDate }}\n\t\t\t\t\t\t</div>\n\n\t\t\t\t\t\t<div v-if=\"prepareResult.hasComponentSync\" class=\"mpb-update__preview-note\">\n\t\t\t\t\t\t\t{{ loc('MPBUILDER_UPDATE_COMPONENT_SYNC_NOTE') }}\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\n\t\t\t\t\t<div class=\"mpb-update__file-list\">\n\t\t\t\t\t\t<div class=\"mpb-update__file-list-header\" @click=\"prepareIncludedExpanded = !prepareIncludedExpanded\">\n\t\t\t\t\t\t\t<span class=\"ui-icon-set --chevron-down mpb-update__file-list-chevron\" :class=\"{ 'mpb-update__file-list-chevron--expanded': prepareIncludedExpanded }\"></span>\n\t\t\t\t\t\t\t{{ loc('MPBUILDER_UPDATE_FILES_INCLUDED') }}\n\t\t\t\t\t\t\t<span class=\"mpb-update__file-list-count\">{{ filteredIncludedFiles.length }}</span>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div v-if=\"prepareIncludedExpanded\" class=\"mpb-update__file-list-body\">\n\t\t\t\t\t\t\t<div\n\t\t\t\t\t\t\t\tv-for=\"file in filteredIncludedFiles\"\n\t\t\t\t\t\t\t\t:key=\"file\"\n\t\t\t\t\t\t\t\tclass=\"mpb-update__file-list-item mpb-update__file-list-item--removable\"\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t<span>{{ file }}</span>\n\t\t\t\t\t\t\t\t<button\n\t\t\t\t\t\t\t\t\tclass=\"mpb-update__file-remove-btn\"\n\t\t\t\t\t\t\t\t\t:disabled=\"isBuilding\"\n\t\t\t\t\t\t\t\t\t@click=\"removeFile(file)\"\n\t\t\t\t\t\t\t\t\t:title=\"loc('MPBUILDER_UPDATE_FILE_REMOVE')\"\n\t\t\t\t\t\t\t\t>&times;</button>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\n\t\t\t\t\t<div v-if=\"removedFiles.length > 0\" class=\"mpb-update__file-list\">\n\t\t\t\t\t\t<div class=\"mpb-update__file-list-header mpb-update__file-list-header--removed\">\n\t\t\t\t\t\t\t{{ loc('MPBUILDER_UPDATE_FILES_REMOVED') }}\n\t\t\t\t\t\t\t<span class=\"mpb-update__file-list-count mpb-update__file-list-count--excluded\">{{ removedFiles.length }}</span>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"mpb-update__file-list-body\">\n\t\t\t\t\t\t\t<div\n\t\t\t\t\t\t\t\tv-for=\"file in removedFiles\"\n\t\t\t\t\t\t\t\t:key=\"file\"\n\t\t\t\t\t\t\t\tclass=\"mpb-update__file-list-item mpb-update__file-list-item--removed\"\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t<span>{{ file }}</span>\n\t\t\t\t\t\t\t\t<button\n\t\t\t\t\t\t\t\t\tclass=\"mpb-update__file-remove-btn mpb-update__file-remove-btn--restore\"\n\t\t\t\t\t\t\t\t\t:disabled=\"isBuilding\"\n\t\t\t\t\t\t\t\t\t@click=\"restoreFile(file)\"\n\t\t\t\t\t\t\t\t\t:title=\"loc('MPBUILDER_UPDATE_FILE_RESTORE')\"\n\t\t\t\t\t\t\t\t>&#8635;</button>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\n\t\t\t\t\t<div v-if=\"prepareResult.excludedCount > 0\" class=\"mpb-update__file-list\">\n\t\t\t\t\t\t<div class=\"mpb-update__file-list-header\" @click=\"prepareExcludedExpanded = !prepareExcludedExpanded\">\n\t\t\t\t\t\t\t<span class=\"ui-icon-set --chevron-down mpb-update__file-list-chevron\" :class=\"{ 'mpb-update__file-list-chevron--expanded': prepareExcludedExpanded }\"></span>\n\t\t\t\t\t\t\t{{ loc('MPBUILDER_UPDATE_FILES_EXCLUDED') }}\n\t\t\t\t\t\t\t<span class=\"mpb-update__file-list-count mpb-update__file-list-count--excluded\">{{ prepareResult.excludedCount }}</span>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div v-if=\"prepareExcludedExpanded\" class=\"mpb-update__file-list-body\">\n\t\t\t\t\t\t\t<div\n\t\t\t\t\t\t\t\tv-for=\"file in prepareResult.excludedFiles\"\n\t\t\t\t\t\t\t\t:key=\"file\"\n\t\t\t\t\t\t\t\tclass=\"mpb-update__file-list-item mpb-update__file-list-item--excluded\"\n\t\t\t\t\t\t\t>{{ file }}</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\n\t\t\t\t\t<div class=\"mpb-update__preview-actions\">\n\t\t\t\t\t\t<button\n\t\t\t\t\t\t\tclass=\"mpb-update__button mpb-update__button--primary\"\n\t\t\t\t\t\t\t:disabled=\"!canBuild\"\n\t\t\t\t\t\t\t@click=\"buildUpdate\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t<template v-if=\"isBuilding\">\n\t\t\t\t\t\t\t\t<span class=\"mpb-update__spinner mpb-update__spinner--sm\"></span>\n\t\t\t\t\t\t\t\t{{ loc('MPBUILDER_UPDATE_BUILDING') }}\n\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t\t\t{{ loc('MPBUILDER_UPDATE_BUILD_BUTTON') }}\n\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t</button>\n\t\t\t\t\t\t<button\n\t\t\t\t\t\t\tv-if=\"moduleInfo.isDevStrategyActive\"\n\t\t\t\t\t\t\tclass=\"mpb-update__button mpb-update__button--structure\"\n\t\t\t\t\t\t\t:disabled=\"!version || isBuilding\"\n\t\t\t\t\t\t\t@click=\"generateStructure\"\n\t\t\t\t\t\t\t:title=\"loc('MPBUILDER_UPDATE_GENERATE_STRUCTURE_HINT')\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t<template v-if=\"isGeneratingStructure\">\n\t\t\t\t\t\t\t\t<span class=\"mpb-update__spinner mpb-update__spinner--sm\"></span>\n\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t\t\t<span class=\"ui-icon-set --folders\"></span>\n\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t{{ loc('MPBUILDER_UPDATE_GENERATE_STRUCTURE') }}\n\t\t\t\t\t\t</button>\n\t\t\t\t\t\t<button\n\t\t\t\t\t\t\tclass=\"mpb-update__button mpb-update__button--secondary\"\n\t\t\t\t\t\t\t:disabled=\"isBuilding || isPreparing\"\n\t\t\t\t\t\t\t@click=\"prepareUpdate\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t<template v-if=\"isPreparing\">\n\t\t\t\t\t\t\t\t<span class=\"mpb-update__spinner mpb-update__spinner--sm mpb-update__spinner--secondary\"></span>\n\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t\t\t{{ loc('MPBUILDER_UPDATE_REPREPARE_BUTTON') }}\n\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t</button>\n\t\t\t\t\t\t<button\n\t\t\t\t\t\t\tclass=\"mpb-update__button mpb-update__button--ghost\"\n\t\t\t\t\t\t\t:disabled=\"isBuilding\"\n\t\t\t\t\t\t\t@click=\"cancelPrepare\"\n\t\t\t\t\t\t>{{ loc('MPBUILDER_UPDATE_CANCEL_PREPARE') }}</button>\n\t\t\t\t\t\t<div v-if=\"structureInfo\" class=\"mpb-update__structure-hint\" :class=\"{ 'mpb-update__structure-hint--error': !structureInfo.success }\">\n\t\t\t\t\t\t\t<template v-if=\"structureInfo.success\">\n\t\t\t\t\t\t\t\t{{ loc('MPBUILDER_UPDATE_STRUCTURE_SAVED') }} ({{ structureInfo.count }})\n\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t\t\t{{ structureInfo.error }}\n\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\n\t\t\t<div v-if=\"buildResult\" class=\"mpb-update__card mpb-update__card--success\">\n\t\t\t\t<div class=\"mpb-update__card-header mpb-update__card-header--success\">\n\t\t\t\t\t<h4 class=\"mpb-update__card-title\">\n\t\t\t\t\t\t<span class=\"ui-icon-set --circle-check mpb-update__icon--success\"></span>\n\t\t\t\t\t\t{{ loc('MPBUILDER_UPDATE_SUCCESS') }}\n\t\t\t\t\t</h4>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"mpb-update__card-body\">\n\n\t\t\t\t\t<div v-if=\"buildResult.strategy === 'dev'\" class=\"mpb-update__dev-path\">\n\t\t\t\t\t\t<span class=\"mpb-update__dev-path-label\">{{ loc('MPBUILDER_UPDATE_DEV_PATH') }}</span>\n\t\t\t\t\t\t<code class=\"mpb-update__dev-path-value\">{{ buildResult.devPath }}</code>\n\t\t\t\t\t</div>\n\n\t\t\t\t\t<div class=\"mpb-update__result-links\">\n\t\t\t\t\t\t\t<a :href=\"buildResult.filemanLink\" target=\"_blank\" class=\"mpb-update__result-link\">\n\t\t\t\t\t\t\t\t<span class=\"ui-icon-set --open-in-40\"></span>\n\t\t\t\t\t\t\t\t{{ loc('MPBUILDER_UPDATE_OPEN_FOLDER') }}\n\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t\t<a :href=\"buildResult.downloadLink\" class=\"mpb-update__result-link\">\n\t\t\t\t\t\t\t\t<span class=\"ui-icon-set --download\"></span>\n\t\t\t\t\t\t\t\t{{ loc('MPBUILDER_UPDATE_DOWNLOAD_ARCHIVE') }}\n\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t\t<a :href=\"buildResult.marketplaceLink\" target=\"_blank\" class=\"mpb-update__result-link\">\n\t\t\t\t\t\t\t\t<span class=\"ui-icon-set --cloud-transfer-data\"></span>\n\t\t\t\t\t\t\t\t{{ loc('MPBUILDER_UPDATE_UPLOAD_MARKETPLACE') }}\n\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\n\t\t\t\t\t<div class=\"mpb-update__file-list\">\n\t\t\t\t\t\t<div class=\"mpb-update__file-list-header\" @click=\"fileListExpanded = !fileListExpanded\">\n\t\t\t\t\t\t\t<span class=\"ui-icon-set --chevron-down mpb-update__file-list-chevron\" :class=\"{ 'mpb-update__file-list-chevron--expanded': fileListExpanded }\"></span>\n\t\t\t\t\t\t\t{{ loc('MPBUILDER_UPDATE_FILE_LIST') }}\n\t\t\t\t\t\t\t<span class=\"mpb-update__file-list-count\">{{ buildResult.fileList.length }}</span>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div v-if=\"fileListExpanded\" class=\"mpb-update__file-list-body\">\n\t\t\t\t\t\t\t<div\n\t\t\t\t\t\t\t\tv-for=\"file in buildResult.fileList\"\n\t\t\t\t\t\t\t\t:key=\"file\"\n\t\t\t\t\t\t\t\tclass=\"mpb-update__file-list-item\"\n\t\t\t\t\t\t\t>{{ file }}</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\n\t\t\t\t\t<div class=\"mpb-update__actions mpb-update__actions--secondary\">\n\t\t\t\t\t\t<button\n\t\t\t\t\t\t\tclass=\"mpb-update__button mpb-update__button--danger\"\n\t\t\t\t\t\t\t@click=\"deleteTemp\"\n\t\t\t\t\t\t>{{ loc('MPBUILDER_UPDATE_DELETE_TEMP') }}</button>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\n\t\t\t<div v-if=\"buildErrors.length\" class=\"mpb-update__card mpb-update__card--error\">\n\t\t\t\t<div class=\"mpb-update__card-header mpb-update__card-header--error\">\n\t\t\t\t\t<h4 class=\"mpb-update__card-title\">{{ loc('MPBUILDER_UPDATE_ERROR') }}</h4>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"mpb-update__card-body\">\n\t\t\t\t\t<ul class=\"mpb-update__error-list\">\n\t\t\t\t\t\t<li v-for=\"(error, index) in buildErrors\" :key=\"index\">{{ error }}</li>\n\t\t\t\t\t</ul>\n\t\t\t\t</div>\n\t\t\t</div>\n\n\t\t</div>\n\t</div>\n\n</div>\n";

	function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	function _regeneratorRuntime() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime = function _regeneratorRuntime() { return exports; }; var exports = {}, Op = Object.prototype, hasOwn = Op.hasOwnProperty, defineProperty = Object.defineProperty || function (obj, key, desc) { obj[key] = desc.value; }, $Symbol = "function" == typeof Symbol ? Symbol : {}, iteratorSymbol = $Symbol.iterator || "@@iterator", asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator", toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag"; function define(obj, key, value) { return Object.defineProperty(obj, key, { value: value, enumerable: !0, configurable: !0, writable: !0 }), obj[key]; } try { define({}, ""); } catch (err) { define = function define(obj, key, value) { return obj[key] = value; }; } function wrap(innerFn, outerFn, self, tryLocsList) { var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator, generator = Object.create(protoGenerator.prototype), context = new Context(tryLocsList || []); return defineProperty(generator, "_invoke", { value: makeInvokeMethod(innerFn, self, context) }), generator; } function tryCatch(fn, obj, arg) { try { return { type: "normal", arg: fn.call(obj, arg) }; } catch (err) { return { type: "throw", arg: err }; } } exports.wrap = wrap; var ContinueSentinel = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var IteratorPrototype = {}; define(IteratorPrototype, iteratorSymbol, function () { return this; }); var getProto = Object.getPrototypeOf, NativeIteratorPrototype = getProto && getProto(getProto(values([]))); NativeIteratorPrototype && NativeIteratorPrototype !== Op && hasOwn.call(NativeIteratorPrototype, iteratorSymbol) && (IteratorPrototype = NativeIteratorPrototype); var Gp = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(IteratorPrototype); function defineIteratorMethods(prototype) { ["next", "throw", "return"].forEach(function (method) { define(prototype, method, function (arg) { return this._invoke(method, arg); }); }); } function AsyncIterator(generator, PromiseImpl) { function invoke(method, arg, resolve, reject) { var record = tryCatch(generator[method], generator, arg); if ("throw" !== record.type) { var result = record.arg, value = result.value; return value && "object" == babelHelpers["typeof"](value) && hasOwn.call(value, "__await") ? PromiseImpl.resolve(value.__await).then(function (value) { invoke("next", value, resolve, reject); }, function (err) { invoke("throw", err, resolve, reject); }) : PromiseImpl.resolve(value).then(function (unwrapped) { result.value = unwrapped, resolve(result); }, function (error) { return invoke("throw", error, resolve, reject); }); } reject(record.arg); } var previousPromise; defineProperty(this, "_invoke", { value: function value(method, arg) { function callInvokeWithMethodAndArg() { return new PromiseImpl(function (resolve, reject) { invoke(method, arg, resolve, reject); }); } return previousPromise = previousPromise ? previousPromise.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); } }); } function makeInvokeMethod(innerFn, self, context) { var state = "suspendedStart"; return function (method, arg) { if ("executing" === state) throw new Error("Generator is already running"); if ("completed" === state) { if ("throw" === method) throw arg; return doneResult(); } for (context.method = method, context.arg = arg;;) { var delegate = context.delegate; if (delegate) { var delegateResult = maybeInvokeDelegate(delegate, context); if (delegateResult) { if (delegateResult === ContinueSentinel) continue; return delegateResult; } } if ("next" === context.method) context.sent = context._sent = context.arg;else if ("throw" === context.method) { if ("suspendedStart" === state) throw state = "completed", context.arg; context.dispatchException(context.arg); } else "return" === context.method && context.abrupt("return", context.arg); state = "executing"; var record = tryCatch(innerFn, self, context); if ("normal" === record.type) { if (state = context.done ? "completed" : "suspendedYield", record.arg === ContinueSentinel) continue; return { value: record.arg, done: context.done }; } "throw" === record.type && (state = "completed", context.method = "throw", context.arg = record.arg); } }; } function maybeInvokeDelegate(delegate, context) { var methodName = context.method, method = delegate.iterator[methodName]; if (undefined === method) return context.delegate = null, "throw" === methodName && delegate.iterator["return"] && (context.method = "return", context.arg = undefined, maybeInvokeDelegate(delegate, context), "throw" === context.method) || "return" !== methodName && (context.method = "throw", context.arg = new TypeError("The iterator does not provide a '" + methodName + "' method")), ContinueSentinel; var record = tryCatch(method, delegate.iterator, context.arg); if ("throw" === record.type) return context.method = "throw", context.arg = record.arg, context.delegate = null, ContinueSentinel; var info = record.arg; return info ? info.done ? (context[delegate.resultName] = info.value, context.next = delegate.nextLoc, "return" !== context.method && (context.method = "next", context.arg = undefined), context.delegate = null, ContinueSentinel) : info : (context.method = "throw", context.arg = new TypeError("iterator result is not an object"), context.delegate = null, ContinueSentinel); } function pushTryEntry(locs) { var entry = { tryLoc: locs[0] }; 1 in locs && (entry.catchLoc = locs[1]), 2 in locs && (entry.finallyLoc = locs[2], entry.afterLoc = locs[3]), this.tryEntries.push(entry); } function resetTryEntry(entry) { var record = entry.completion || {}; record.type = "normal", delete record.arg, entry.completion = record; } function Context(tryLocsList) { this.tryEntries = [{ tryLoc: "root" }], tryLocsList.forEach(pushTryEntry, this), this.reset(!0); } function values(iterable) { if (iterable) { var iteratorMethod = iterable[iteratorSymbol]; if (iteratorMethod) return iteratorMethod.call(iterable); if ("function" == typeof iterable.next) return iterable; if (!isNaN(iterable.length)) { var i = -1, next = function next() { for (; ++i < iterable.length;) if (hasOwn.call(iterable, i)) return next.value = iterable[i], next.done = !1, next; return next.value = undefined, next.done = !0, next; }; return next.next = next; } } return { next: doneResult }; } function doneResult() { return { value: undefined, done: !0 }; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, defineProperty(Gp, "constructor", { value: GeneratorFunctionPrototype, configurable: !0 }), defineProperty(GeneratorFunctionPrototype, "constructor", { value: GeneratorFunction, configurable: !0 }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, toStringTagSymbol, "GeneratorFunction"), exports.isGeneratorFunction = function (genFun) { var ctor = "function" == typeof genFun && genFun.constructor; return !!ctor && (ctor === GeneratorFunction || "GeneratorFunction" === (ctor.displayName || ctor.name)); }, exports.mark = function (genFun) { return Object.setPrototypeOf ? Object.setPrototypeOf(genFun, GeneratorFunctionPrototype) : (genFun.__proto__ = GeneratorFunctionPrototype, define(genFun, toStringTagSymbol, "GeneratorFunction")), genFun.prototype = Object.create(Gp), genFun; }, exports.awrap = function (arg) { return { __await: arg }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, asyncIteratorSymbol, function () { return this; }), exports.AsyncIterator = AsyncIterator, exports.async = function (innerFn, outerFn, self, tryLocsList, PromiseImpl) { void 0 === PromiseImpl && (PromiseImpl = Promise); var iter = new AsyncIterator(wrap(innerFn, outerFn, self, tryLocsList), PromiseImpl); return exports.isGeneratorFunction(outerFn) ? iter : iter.next().then(function (result) { return result.done ? result.value : iter.next(); }); }, defineIteratorMethods(Gp), define(Gp, toStringTagSymbol, "Generator"), define(Gp, iteratorSymbol, function () { return this; }), define(Gp, "toString", function () { return "[object Generator]"; }), exports.keys = function (val) { var object = Object(val), keys = []; for (var key in object) keys.push(key); return keys.reverse(), function next() { for (; keys.length;) { var key = keys.pop(); if (key in object) return next.value = key, next.done = !1, next; } return next.done = !0, next; }; }, exports.values = values, Context.prototype = { constructor: Context, reset: function reset(skipTempReset) { if (this.prev = 0, this.next = 0, this.sent = this._sent = undefined, this.done = !1, this.delegate = null, this.method = "next", this.arg = undefined, this.tryEntries.forEach(resetTryEntry), !skipTempReset) for (var name in this) "t" === name.charAt(0) && hasOwn.call(this, name) && !isNaN(+name.slice(1)) && (this[name] = undefined); }, stop: function stop() { this.done = !0; var rootRecord = this.tryEntries[0].completion; if ("throw" === rootRecord.type) throw rootRecord.arg; return this.rval; }, dispatchException: function dispatchException(exception) { if (this.done) throw exception; var context = this; function handle(loc, caught) { return record.type = "throw", record.arg = exception, context.next = loc, caught && (context.method = "next", context.arg = undefined), !!caught; } for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i], record = entry.completion; if ("root" === entry.tryLoc) return handle("end"); if (entry.tryLoc <= this.prev) { var hasCatch = hasOwn.call(entry, "catchLoc"), hasFinally = hasOwn.call(entry, "finallyLoc"); if (hasCatch && hasFinally) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } else if (hasCatch) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); } else { if (!hasFinally) throw new Error("try statement without catch or finally"); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } } } }, abrupt: function abrupt(type, arg) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc <= this.prev && hasOwn.call(entry, "finallyLoc") && this.prev < entry.finallyLoc) { var finallyEntry = entry; break; } } finallyEntry && ("break" === type || "continue" === type) && finallyEntry.tryLoc <= arg && arg <= finallyEntry.finallyLoc && (finallyEntry = null); var record = finallyEntry ? finallyEntry.completion : {}; return record.type = type, record.arg = arg, finallyEntry ? (this.method = "next", this.next = finallyEntry.finallyLoc, ContinueSentinel) : this.complete(record); }, complete: function complete(record, afterLoc) { if ("throw" === record.type) throw record.arg; return "break" === record.type || "continue" === record.type ? this.next = record.arg : "return" === record.type ? (this.rval = this.arg = record.arg, this.method = "return", this.next = "end") : "normal" === record.type && afterLoc && (this.next = afterLoc), ContinueSentinel; }, finish: function finish(finallyLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.finallyLoc === finallyLoc) return this.complete(entry.completion, entry.afterLoc), resetTryEntry(entry), ContinueSentinel; } }, "catch": function _catch(tryLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc === tryLoc) { var record = entry.completion; if ("throw" === record.type) { var thrown = record.arg; resetTryEntry(entry); } return thrown; } } throw new Error("illegal catch attempt"); }, delegateYield: function delegateYield(iterable, resultName, nextLoc) { return this.delegate = { iterator: values(iterable), resultName: resultName, nextLoc: nextLoc }, "next" === this.method && (this.arg = undefined), ContinueSentinel; } }, exports; }
	var BuilderUpdateApp = {
	  props: {
	    params: {
	      type: Object,
	      required: true
	    }
	  },
	  data: function data() {
	    return {
	      modules: [],
	      selectedModuleId: this.params.sessionModuleId || '',
	      moduleInfo: null,
	      isLoadingModuleInfo: false,
	      version: '',
	      storeVersion: false,
	      components: false,
	      namespace: '',
	      description: '',
	      updater: '',
	      useCustomDate: false,
	      customDateFrom: '',
	      baseVersion: '',
	      isPreparing: false,
	      prepareResult: null,
	      prepareIncludedExpanded: true,
	      prepareExcludedExpanded: true,
	      removedFiles: [],
	      isBuilding: false,
	      buildResult: null,
	      buildErrors: [],
	      fileListExpanded: false,
	      isGeneratingStructure: false,
	      isAnalyzingStructure: false,
	      structureInfo: null,
	      isSavingDescription: false,
	      descriptionSaveInfo: null,
	      devVersions: [],
	      isLoadingDevVersion: false,
	      descriptionEditor: null,
	      updaterEditor: null,
	      editorKey: 0
	    };
	  },
	  computed: {
	    canPrepare: function canPrepare() {
	      return this.selectedModuleId && this.version && !this.isPreparing && !this.isBuilding;
	    },
	    canBuild: function canBuild() {
	      return this.prepareResult && !this.isBuilding;
	    },
	    filteredIncludedFiles: function filteredIncludedFiles() {
	      var _this = this;
	      if (!this.prepareResult) {
	        return [];
	      }
	      return this.prepareResult.includedFiles.filter(function (f) {
	        return !_this.removedFiles.includes(f);
	      });
	    }
	  },
	  mounted: function mounted() {
	    this.loadModules();
	  },
	  watch: {
	    useCustomDate: function useCustomDate(val) {
	      if (val && !this.customDateFrom) {
	        var now = new Date();
	        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
	        this.customDateFrom = now.toISOString().slice(0, 16);
	      }
	    },
	    baseVersion: function baseVersion(ver) {
	      var _this2 = this;
	      return babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee() {
	        var response, d;
	        return _regeneratorRuntime().wrap(function _callee$(_context) {
	          while (1) switch (_context.prev = _context.next) {
	            case 0:
	              if (ver) {
	                _context.next = 2;
	                break;
	              }
	              return _context.abrupt("return");
	            case 2:
	              _context.prev = 2;
	              _context.next = 5;
	              return _this2.runAction('loadDevVersion', {
	                moduleId: _this2.selectedModuleId,
	                version: ver
	              });
	            case 5:
	              response = _context.sent;
	              if (response.data.versionDate) {
	                _this2.useCustomDate = true;
	                d = new Date(response.data.versionDate.replace(' ', 'T'));
	                d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
	                _this2.customDateFrom = d.toISOString().slice(0, 16);
	              }
	              _context.next = 12;
	              break;
	            case 9:
	              _context.prev = 9;
	              _context.t0 = _context["catch"](2);
	              console.error('Failed to load base version date', _context.t0);
	            case 12:
	            case "end":
	              return _context.stop();
	          }
	        }, _callee, null, [[2, 9]]);
	      }))();
	    },
	    selectedModuleId: function selectedModuleId(newVal) {
	      this.prepareResult = null;
	      this.buildResult = null;
	      this.buildErrors = [];
	      this.structureInfo = null;
	      this.descriptionSaveInfo = null;
	      if (newVal) {
	        this.loadModuleInfo(newVal);
	      } else {
	        this.moduleInfo = null;
	      }
	    }
	  },
	  methods: {
	    loc: function loc(messageId) {
	      return BX.message(messageId) || '';
	    },
	    runAction: function runAction(action) {
	      var _arguments = arguments;
	      return babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee2() {
	        var data;
	        return _regeneratorRuntime().wrap(function _callee2$(_context2) {
	          while (1) switch (_context2.prev = _context2.next) {
	            case 0:
	              data = _arguments.length > 1 && _arguments[1] !== undefined ? _arguments[1] : {};
	              return _context2.abrupt("return", BX.ajax.runComponentAction(COMPONENT_NAME, action, {
	                mode: 'class',
	                data: data
	              }));
	            case 2:
	            case "end":
	              return _context2.stop();
	          }
	        }, _callee2);
	      }))();
	    },
	    loadModules: function loadModules() {
	      var _this3 = this;
	      return babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee3() {
	        var response;
	        return _regeneratorRuntime().wrap(function _callee3$(_context3) {
	          while (1) switch (_context3.prev = _context3.next) {
	            case 0:
	              _context3.prev = 0;
	              _context3.next = 3;
	              return _this3.runAction('getModules');
	            case 3:
	              response = _context3.sent;
	              _this3.modules = response.data.modules || [];
	              _context3.next = 10;
	              break;
	            case 7:
	              _context3.prev = 7;
	              _context3.t0 = _context3["catch"](0);
	              console.error('Failed to load modules', _context3.t0);
	            case 10:
	              _context3.prev = 10;
	              if (_this3.selectedModuleId) {
	                _this3.loadModuleInfo(_this3.selectedModuleId);
	              }
	              return _context3.finish(10);
	            case 13:
	            case "end":
	              return _context3.stop();
	          }
	        }, _callee3, null, [[0, 7, 10, 13]]);
	      }))();
	    },
	    loadModuleInfo: function loadModuleInfo(moduleId) {
	      var _this4 = this;
	      return babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee4() {
	        var response, info, savedVersion;
	        return _regeneratorRuntime().wrap(function _callee4$(_context4) {
	          while (1) switch (_context4.prev = _context4.next) {
	            case 0:
	              _context4.prev = 0;
	              _this4.isLoadingModuleInfo = true;
	              _this4.moduleInfo = null;
	              _this4.prepareResult = null;
	              _this4.buildResult = null;
	              _this4.buildErrors = [];
	              _context4.next = 8;
	              return _this4.runAction('getModuleInfo', {
	                moduleId: moduleId
	              });
	            case 8:
	              response = _context4.sent;
	              info = response.data;
	              _this4.moduleInfo = info;
	              _this4.devVersions = info.devVersions || [];
	              _this4.version = info.nextVersion || '';
	              _this4.namespace = info.namespace || '';
	              _this4.description = info.description || '<ul>\n  <li></li>\n</ul>';
	              _this4.updater = info.updater || '';
	              _this4.storeVersion = false;
	              _this4.components = false;
	              savedVersion = _this4.getSavedVersion(moduleId);
	              if (!(savedVersion && _this4.devVersions.includes(savedVersion))) {
	                _context4.next = 22;
	                break;
	              }
	              _context4.next = 22;
	              return _this4.selectDevVersion(savedVersion);
	            case 22:
	              _context4.next = 27;
	              break;
	            case 24:
	              _context4.prev = 24;
	              _context4.t0 = _context4["catch"](0);
	              console.error('Failed to load module info', _context4.t0);
	            case 27:
	              _context4.prev = 27;
	              _this4.isLoadingModuleInfo = false;
	              _this4.$nextTick(function () {
	                return _this4.initCodeEditors();
	              });
	              return _context4.finish(27);
	            case 31:
	            case "end":
	              return _context4.stop();
	          }
	        }, _callee4, null, [[0, 24, 27, 31]]);
	      }))();
	    },
	    initCodeEditors: function initCodeEditors() {
	      if (!this.params.useCodeEditor || !window.JCCodeEditor) {
	        return;
	      }
	      var editorMessages = this.params.codeEditorMessages || {};
	      if (document.getElementById('mpb-description-editor')) {
	        this.descriptionEditor = new window.JCCodeEditor({
	          textareaId: 'mpb-description-editor',
	          height: 350,
	          forceSyntax: 'php',
	          highlightMode: true
	        }, editorMessages);
	      }
	      if (document.getElementById('mpb-updater-editor')) {
	        this.updaterEditor = new window.JCCodeEditor({
	          textareaId: 'mpb-updater-editor',
	          height: 350,
	          forceSyntax: 'php',
	          highlightMode: true
	        }, editorMessages);
	      }
	    },
	    syncEditorValues: function syncEditorValues() {
	      if (this.descriptionEditor) {
	        try {
	          this.descriptionEditor.Save();
	        } catch (e) {}
	        var el = document.getElementById('mpb-description-editor');
	        if (el) {
	          this.description = el.value;
	        }
	      }
	      if (this.updaterEditor) {
	        try {
	          this.updaterEditor.Save();
	        } catch (e) {}
	        var _el = document.getElementById('mpb-updater-editor');
	        if (_el) {
	          this.updater = _el.value;
	        }
	      }
	    },
	    prepareUpdate: function prepareUpdate() {
	      var _this5 = this;
	      return babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee5() {
	        var response;
	        return _regeneratorRuntime().wrap(function _callee5$(_context5) {
	          while (1) switch (_context5.prev = _context5.next) {
	            case 0:
	              if (_this5.canPrepare) {
	                _context5.next = 2;
	                break;
	              }
	              return _context5.abrupt("return");
	            case 2:
	              _this5.syncEditorValues();
	              _this5.isPreparing = true;
	              _this5.buildErrors = [];
	              _this5.prepareIncludedExpanded = true;
	              _this5.prepareExcludedExpanded = true;
	              _context5.prev = 7;
	              _context5.next = 10;
	              return _this5.runAction('prepareUpdate', {
	                moduleId: _this5.selectedModuleId,
	                version: _this5.version,
	                components: _this5.components,
	                namespace: _this5.namespace,
	                customDateFrom: _this5.useCustomDate ? _this5.customDateFrom : ''
	              });
	            case 10:
	              response = _context5.sent;
	              _this5.prepareResult = response.data;
	              _this5.removedFiles = [];
	              _context5.next = 19;
	              break;
	            case 15:
	              _context5.prev = 15;
	              _context5.t0 = _context5["catch"](7);
	              console.error('prepareUpdate error:', _context5.t0);
	              if (_context5.t0.errors && _context5.t0.errors.length) {
	                _this5.buildErrors = _context5.t0.errors.map(function (e) {
	                  return e.message;
	                });
	              } else {
	                _this5.buildErrors = ['Unknown error'];
	              }
	            case 19:
	              _context5.prev = 19;
	              _this5.isPreparing = false;
	              return _context5.finish(19);
	            case 22:
	            case "end":
	              return _context5.stop();
	          }
	        }, _callee5, null, [[7, 15, 19, 22]]);
	      }))();
	    },
	    removeFile: function removeFile(file) {
	      if (!this.removedFiles.includes(file)) {
	        this.removedFiles.push(file);
	      }
	    },
	    restoreFile: function restoreFile(file) {
	      this.removedFiles = this.removedFiles.filter(function (f) {
	        return f !== file;
	      });
	    },
	    cancelPrepare: function cancelPrepare() {
	      this.prepareResult = null;
	      this.buildResult = null;
	    },
	    buildUpdate: function buildUpdate() {
	      var _this6 = this;
	      return babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee6() {
	        var response;
	        return _regeneratorRuntime().wrap(function _callee6$(_context6) {
	          while (1) switch (_context6.prev = _context6.next) {
	            case 0:
	              if (_this6.canBuild) {
	                _context6.next = 2;
	                break;
	              }
	              return _context6.abrupt("return");
	            case 2:
	              _this6.syncEditorValues();
	              _this6.isBuilding = true;
	              _this6.buildResult = null;
	              _this6.buildErrors = [];
	              _this6.fileListExpanded = false;
	              _context6.prev = 7;
	              _context6.next = 10;
	              return _this6.runAction('buildUpdate', {
	                moduleId: _this6.selectedModuleId,
	                version: _this6.version,
	                description: _this6.description,
	                updater: _this6.updater,
	                storeVersion: _this6.storeVersion,
	                components: _this6.components,
	                namespace: _this6.namespace,
	                customDateFrom: _this6.useCustomDate ? _this6.customDateFrom : '',
	                excludedFiles: JSON.stringify(_this6.removedFiles)
	              });
	            case 10:
	              response = _context6.sent;
	              _this6.buildResult = response.data;
	              _context6.next = 17;
	              break;
	            case 14:
	              _context6.prev = 14;
	              _context6.t0 = _context6["catch"](7);
	              if (_context6.t0.errors && _context6.t0.errors.length) {
	                _this6.buildErrors = _context6.t0.errors.map(function (e) {
	                  return e.message;
	                });
	              } else {
	                _this6.buildErrors = ['Unknown error'];
	              }
	            case 17:
	              _context6.prev = 17;
	              _this6.isBuilding = false;
	              return _context6.finish(17);
	            case 20:
	            case "end":
	              return _context6.stop();
	          }
	        }, _callee6, null, [[7, 14, 17, 20]]);
	      }))();
	    },
	    deleteTemp: function deleteTemp() {
	      var _this7 = this;
	      return babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee7() {
	        return _regeneratorRuntime().wrap(function _callee7$(_context7) {
	          while (1) switch (_context7.prev = _context7.next) {
	            case 0:
	              if (confirm(_this7.loc('MPBUILDER_UPDATE_DELETE_CONFIRM'))) {
	                _context7.next = 2;
	                break;
	              }
	              return _context7.abrupt("return");
	            case 2:
	              _context7.prev = 2;
	              _context7.next = 5;
	              return _this7.runAction('deleteTemp', {
	                moduleId: _this7.selectedModuleId
	              });
	            case 5:
	              _this7.buildResult = null;
	              _context7.next = 11;
	              break;
	            case 8:
	              _context7.prev = 8;
	              _context7.t0 = _context7["catch"](2);
	              console.error('Failed to delete temp', _context7.t0);
	            case 11:
	            case "end":
	              return _context7.stop();
	          }
	        }, _callee7, null, [[2, 8]]);
	      }))();
	    },
	    saveDescription: function saveDescription() {
	      var _this8 = this;
	      return babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee8() {
	        var _err$errors, _err$errors$, msg;
	        return _regeneratorRuntime().wrap(function _callee8$(_context8) {
	          while (1) switch (_context8.prev = _context8.next) {
	            case 0:
	              _this8.syncEditorValues();
	              _this8.isSavingDescription = true;
	              _this8.descriptionSaveInfo = null;
	              _context8.prev = 3;
	              _context8.next = 6;
	              return _this8.runAction('saveDescription', {
	                moduleId: _this8.selectedModuleId,
	                version: _this8.version,
	                description: _this8.description
	              });
	            case 6:
	              _this8.descriptionSaveInfo = {
	                success: true
	              };
	              _context8.next = 13;
	              break;
	            case 9:
	              _context8.prev = 9;
	              _context8.t0 = _context8["catch"](3);
	              msg = (_context8.t0 === null || _context8.t0 === void 0 ? void 0 : (_err$errors = _context8.t0.errors) === null || _err$errors === void 0 ? void 0 : (_err$errors$ = _err$errors[0]) === null || _err$errors$ === void 0 ? void 0 : _err$errors$.message) || (_context8.t0 === null || _context8.t0 === void 0 ? void 0 : _context8.t0.message) || 'Unknown error';
	              _this8.descriptionSaveInfo = {
	                success: false,
	                error: msg
	              };
	            case 13:
	              _context8.prev = 13;
	              _this8.isSavingDescription = false;
	              return _context8.finish(13);
	            case 16:
	            case "end":
	              return _context8.stop();
	          }
	        }, _callee8, null, [[3, 9, 13, 16]]);
	      }))();
	    },
	    generateStructure: function generateStructure() {
	      var _this9 = this;
	      return babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee9() {
	        var response, _err$errors2, _err$errors2$, msg;
	        return _regeneratorRuntime().wrap(function _callee9$(_context9) {
	          while (1) switch (_context9.prev = _context9.next) {
	            case 0:
	              _this9.isGeneratingStructure = true;
	              _this9.structureInfo = null;
	              _context9.prev = 2;
	              _context9.next = 5;
	              return _this9.runAction('generateStructure', {
	                moduleId: _this9.selectedModuleId,
	                version: _this9.version
	              });
	            case 5:
	              response = _context9.sent;
	              _this9.structureInfo = _objectSpread({
	                success: true
	              }, response.data);
	              _context9.next = 13;
	              break;
	            case 9:
	              _context9.prev = 9;
	              _context9.t0 = _context9["catch"](2);
	              msg = (_context9.t0 === null || _context9.t0 === void 0 ? void 0 : (_err$errors2 = _context9.t0.errors) === null || _err$errors2 === void 0 ? void 0 : (_err$errors2$ = _err$errors2[0]) === null || _err$errors2$ === void 0 ? void 0 : _err$errors2$.message) || (_context9.t0 === null || _context9.t0 === void 0 ? void 0 : _context9.t0.message) || 'Unknown error';
	              _this9.structureInfo = {
	                success: false,
	                error: msg
	              };
	            case 13:
	              _context9.prev = 13;
	              _this9.isGeneratingStructure = false;
	              return _context9.finish(13);
	            case 16:
	            case "end":
	              return _context9.stop();
	          }
	        }, _callee9, null, [[2, 9, 13, 16]]);
	      }))();
	    },
	    analyzeStructure: function analyzeStructure() {
	      var _this10 = this;
	      return babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee10() {
	        var response, _err$errors3, _err$errors3$, msg;
	        return _regeneratorRuntime().wrap(function _callee10$(_context10) {
	          while (1) switch (_context10.prev = _context10.next) {
	            case 0:
	              _this10.syncEditorValues();
	              _this10.isAnalyzingStructure = true;
	              _context10.prev = 2;
	              _context10.next = 5;
	              return _this10.runAction('analyzeStructure', {
	                moduleId: _this10.selectedModuleId,
	                version: _this10.version,
	                updater: _this10.updater || '',
	                baseVersion: _this10.baseVersion || ''
	              });
	            case 5:
	              response = _context10.sent;
	              _this10.updater = response.data.updater || _this10.updater;
	              _this10.refreshEditors();
	              _context10.next = 14;
	              break;
	            case 10:
	              _context10.prev = 10;
	              _context10.t0 = _context10["catch"](2);
	              msg = (_context10.t0 === null || _context10.t0 === void 0 ? void 0 : (_err$errors3 = _context10.t0.errors) === null || _err$errors3 === void 0 ? void 0 : (_err$errors3$ = _err$errors3[0]) === null || _err$errors3$ === void 0 ? void 0 : _err$errors3$.message) || (_context10.t0 === null || _context10.t0 === void 0 ? void 0 : _context10.t0.message) || 'Unknown error';
	              alert(_this10.loc('MPBUILDER_UPDATE_ANALYZE_ERROR') + ': ' + msg);
	            case 14:
	              _context10.prev = 14;
	              _this10.isAnalyzingStructure = false;
	              return _context10.finish(14);
	            case 17:
	            case "end":
	              return _context10.stop();
	          }
	        }, _callee10, null, [[2, 10, 14, 17]]);
	      }))();
	    },
	    selectNewVersion: function selectNewVersion() {
	      var _this11 = this;
	      this.version = this.moduleInfo.nextVersion || '';
	      this.description = this.moduleInfo.description || '<ul>\n  <li></li>\n</ul>';
	      this.updater = this.moduleInfo.updater || '';
	      this.prepareResult = null;
	      this.buildResult = null;
	      this.buildErrors = [];
	      this.structureInfo = null;
	      this.descriptionSaveInfo = null;
	      this.saveVersion(this.selectedModuleId, null);
	      this.$nextTick(function () {
	        return _this11.refreshEditors();
	      });
	    },
	    selectDevVersion: function selectDevVersion(ver) {
	      var _this12 = this;
	      return babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee11() {
	        var response, data;
	        return _regeneratorRuntime().wrap(function _callee11$(_context11) {
	          while (1) switch (_context11.prev = _context11.next) {
	            case 0:
	              if (!(_this12.isLoadingDevVersion || _this12.isBuilding)) {
	                _context11.next = 2;
	                break;
	              }
	              return _context11.abrupt("return");
	            case 2:
	              _this12.isLoadingDevVersion = true;
	              _this12.prepareResult = null;
	              _this12.buildResult = null;
	              _this12.buildErrors = [];
	              _this12.structureInfo = null;
	              _this12.descriptionSaveInfo = null;
	              _context11.prev = 8;
	              _context11.next = 11;
	              return _this12.runAction('loadDevVersion', {
	                moduleId: _this12.selectedModuleId,
	                version: ver
	              });
	            case 11:
	              response = _context11.sent;
	              data = response.data;
	              _this12.version = data.version || ver;
	              _this12.description = data.description || '<ul>\n  <li></li>\n</ul>';
	              _this12.updater = data.updater || '';
	              _this12.saveVersion(_this12.selectedModuleId, ver);
	              _this12.$nextTick(function () {
	                return _this12.refreshEditors();
	              });
	              _context11.next = 23;
	              break;
	            case 20:
	              _context11.prev = 20;
	              _context11.t0 = _context11["catch"](8);
	              console.error('Failed to load dev version', _context11.t0);
	            case 23:
	              _context11.prev = 23;
	              _this12.isLoadingDevVersion = false;
	              return _context11.finish(23);
	            case 26:
	            case "end":
	              return _context11.stop();
	          }
	        }, _callee11, null, [[8, 20, 23, 26]]);
	      }))();
	    },
	    saveVersion: function saveVersion(moduleId, version) {
	      try {
	        var key = "mpbuilder_version_".concat(moduleId);
	        if (version) {
	          sessionStorage.setItem(key, version);
	        } else {
	          sessionStorage.removeItem(key);
	        }
	      } catch (e) {}
	    },
	    getSavedVersion: function getSavedVersion(moduleId) {
	      try {
	        return sessionStorage.getItem("mpbuilder_version_".concat(moduleId)) || null;
	      } catch (e) {
	        return null;
	      }
	    },
	    refreshEditors: function refreshEditors() {
	      var _this13 = this;
	      this.descriptionEditor = null;
	      this.updaterEditor = null;
	      this.editorKey++;
	      this.$nextTick(function () {
	        return _this13.initCodeEditors();
	      });
	    },
	    restoreVersion: function restoreVersion() {
	      var _this14 = this;
	      return babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee12() {
	        var response;
	        return _regeneratorRuntime().wrap(function _callee12$(_context12) {
	          while (1) switch (_context12.prev = _context12.next) {
	            case 0:
	              if (confirm(_this14.loc('MPBUILDER_UPDATE_RESTORE_CONFIRM'))) {
	                _context12.next = 2;
	                break;
	              }
	              return _context12.abrupt("return");
	            case 2:
	              _context12.prev = 2;
	              _context12.next = 5;
	              return _this14.runAction('restoreVersion', {
	                moduleId: _this14.selectedModuleId
	              });
	            case 5:
	              response = _context12.sent;
	              if (response.data) {
	                _this14.loadModuleInfo(_this14.selectedModuleId);
	              }
	              _context12.next = 12;
	              break;
	            case 9:
	              _context12.prev = 9;
	              _context12.t0 = _context12["catch"](2);
	              console.error('Failed to restore version', _context12.t0);
	            case 12:
	            case "end":
	              return _context12.stop();
	          }
	        }, _callee12, null, [[2, 9]]);
	      }))();
	    }
	  },
	  template: appTemplate
	};
	BX.ready(function () {
	  var container = document.getElementById(APP_CONTAINER_ID);
	  if (!container) {
	    return;
	  }
	  var params = {};
	  try {
	    params = JSON.parse(container.dataset.params || '{}');
	  } catch (e) {
	    console.error('BuilderUpdate: failed to parse params', e);
	    return;
	  }
	  var app = BX.Vue3.BitrixVue.createApp(BuilderUpdateApp, {
	    params: params
	  });
	  app.mount(container);
	});

}((this.window = this.window || {})));
//# sourceMappingURL=script.js.map
