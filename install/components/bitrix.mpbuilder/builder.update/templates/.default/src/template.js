export const appTemplate = `
<div class="mpb-update">

	<div class="mpb-update__layout" :class="{ 'mpb-update__layout--split': prepareResult || buildResult }">
		<div class="mpb-update__layout-main">

			<div class="mpb-update__note">
				<span class="ui-icon-set --info-circle mpb-update__note-icon"></span>
				<div class="mpb-update__note-text">{{ loc('MPBUILDER_UPDATE_NOTE') }}</div>
			</div>

			<div class="mpb-update__card">
				<div class="mpb-update__card-header">
					<h4 class="mpb-update__card-title">{{ loc('MPBUILDER_UPDATE_SELECT_MODULE') }}</h4>
				</div>
				<div class="mpb-update__card-body">
					<div class="mpb-update__field">
						<select
							class="mpb-update__select"
							v-model="selectedModuleId"
							:disabled="isBuilding"
						>
							<option value="">{{ loc('MPBUILDER_UPDATE_SELECT_MODULE_PLACEHOLDER') }}</option>
							<option
								v-for="mod in modules"
								:key="mod"
								:value="mod"
							>{{ mod }}</option>
						</select>
					</div>
				</div>
			</div>

			<template v-if="isLoadingModuleInfo">
				<div class="mpb-update__card">
					<div class="mpb-update__card-body mpb-update__loading">
						<div class="mpb-update__spinner"></div>
						<span>{{ loc('MPBUILDER_UPDATE_LOADING') }}</span>
					</div>
				</div>
			</template>

			<template v-if="moduleInfo && !isLoadingModuleInfo">

				<div class="mpb-update__card" :class="{ 'mpb-update__card--disabled': isBuilding }">
					<div class="mpb-update__card-header">
						<h4 class="mpb-update__card-title">{{ loc('MPBUILDER_UPDATE_VERSION_SETTINGS') }}</h4>
					</div>
					<div class="mpb-update__card-body">
						<div class="mpb-update__field">
							<label class="mpb-update__label">{{ loc('MPBUILDER_UPDATE_VERSION_LABEL') }}</label>
							<input
								type="text"
								class="mpb-update__input"
								v-model="version"
							/>
						</div>

						<div v-if="moduleInfo.isDevStrategyActive && devVersions.length > 0" class="mpb-update__field">
							<label class="mpb-update__label">{{ loc('MPBUILDER_UPDATE_DEV_VERSIONS') }}</label>
							<div class="mpb-update__dev-versions">
								<button
									class="mpb-update__dev-version-tag"
									:class="{ 'mpb-update__dev-version-tag--active': version === moduleInfo.nextVersion }"
									:disabled="isLoadingDevVersion || isBuilding"
									@click="selectNewVersion"
								>{{ loc('MPBUILDER_UPDATE_DEV_VERSION_NEW') }}</button>
								<button
									v-for="ver in devVersions"
									:key="ver"
									class="mpb-update__dev-version-tag"
									:class="{ 'mpb-update__dev-version-tag--active': version === ver }"
									:disabled="isLoadingDevVersion || isBuilding"
									@click="selectDevVersion(ver)"
								>{{ ver }}</button>
							</div>
						</div>

						<div class="mpb-update__field">
							<label class="mpb-update__checkbox">
								<input type="checkbox" v-model="storeVersion" />
								<span class="mpb-update__checkbox-text">{{ loc('MPBUILDER_UPDATE_STORE_VERSION') }}</span>
							</label>
						</div>

						<div v-if="moduleInfo.backupVersion && !moduleInfo.isDevStrategyActive" class="mpb-update__field">
							<div class="mpb-update__warning-badge">
								{{ loc('MPBUILDER_UPDATE_BACKUP_AVAILABLE') }}
								<strong>{{ moduleInfo.backupVersion }}</strong>.
								<a href="javascript:void(0)" @click="restoreVersion" class="mpb-update__link">
									{{ loc('MPBUILDER_UPDATE_RESTORE') }}
								</a>
							</div>
						</div>
					</div>
				</div>

				<div
					v-if="moduleInfo.hasComponents"
					class="mpb-update__card"
					:class="{ 'mpb-update__card--disabled': isBuilding }"
				>
					<div class="mpb-update__card-header">
						<h4 class="mpb-update__card-title">{{ loc('MPBUILDER_UPDATE_COMPONENTS') }}</h4>
					</div>
					<div class="mpb-update__card-body">
						<div class="mpb-update__field">
							<label class="mpb-update__checkbox">
								<input type="checkbox" v-model="components" />
								<span class="mpb-update__checkbox-text">{{ loc('MPBUILDER_UPDATE_COPY_COMPONENTS') }}</span>
							</label>
						</div>

						<div v-if="moduleInfo.hasCustomNamespace && components" class="mpb-update__field">
							<label class="mpb-update__label">{{ loc('MPBUILDER_UPDATE_NAMESPACE_LABEL') }}</label>
							<div class="mpb-update__input-prefix">
								<span class="mpb-update__input-prefix-text">/bitrix/components/</span>
								<input
									type="text"
									class="mpb-update__input"
									v-model="namespace"
								/>
							</div>
							<div class="mpb-update__hint">{{ loc('MPBUILDER_UPDATE_NAMESPACE_HINT') }}</div>
						</div>
					</div>
				</div>

				<div
					v-if="!moduleInfo.hasComponents"
					class="mpb-update__card"
					:class="{ 'mpb-update__card--disabled': isBuilding }"
				>
					<div class="mpb-update__card-header">
						<h4 class="mpb-update__card-title">{{ loc('MPBUILDER_UPDATE_COMPONENTS') }}</h4>
					</div>
					<div class="mpb-update__card-body">
						<div class="mpb-update__hint">{{ loc('MPBUILDER_UPDATE_NO_COMPONENTS') }}</div>
					</div>
				</div>

				<div :key="editorKey" class="mpb-update__card" :class="{ 'mpb-update__card--disabled': isBuilding }">
					<div class="mpb-update__card-header">
						<h4 class="mpb-update__card-title">{{ loc('MPBUILDER_UPDATE_CONTENT') }}</h4>
					</div>
					<div class="mpb-update__card-body">
						<div class="mpb-update__field">
							<label class="mpb-update__label">{{ loc('MPBUILDER_UPDATE_DESCRIPTION_LABEL') }}</label>
							<textarea
								id="mpb-description-editor"
								class="mpb-update__textarea"
								v-model="description"
								rows="8"
							></textarea>
							<div class="mpb-update__hint">{{ loc('MPBUILDER_UPDATE_DESCRIPTION_HINT') }}</div>
						</div>

						<div class="mpb-update__field">
							<div class="mpb-update__field-header">
								<label class="mpb-update__label">{{ loc('MPBUILDER_UPDATE_UPDATER_LABEL') }}</label>
								<div v-if="moduleInfo.isDevStrategyActive" class="mpb-update__dev-tools">
									<button
										class="mpb-update__button mpb-update__button--tool"
										:disabled="!version || isBuilding"
										@click="generateStructure"
										:title="loc('MPBUILDER_UPDATE_GENERATE_STRUCTURE_HINT')"
									>
										<template v-if="isGeneratingStructure">
											<span class="mpb-update__spinner mpb-update__spinner--xs mpb-update__spinner--secondary"></span>
										</template>
										{{ loc('MPBUILDER_UPDATE_GENERATE_STRUCTURE') }}
									</button>
									<button
										class="mpb-update__button mpb-update__button--tool"
										:disabled="!version || isBuilding"
										@click="analyzeStructure"
										:title="loc('MPBUILDER_UPDATE_ANALYZE_STRUCTURE_HINT')"
									>
										<template v-if="isAnalyzingStructure">
											<span class="mpb-update__spinner mpb-update__spinner--xs mpb-update__spinner--secondary"></span>
										</template>
										<template v-else>
											<span class="ui-icon-set --magic-wand"></span>
										</template>
										{{ loc('MPBUILDER_UPDATE_ANALYZE_STRUCTURE') }}
									</button>
								</div>
							</div>
							<div v-if="structureInfo" class="mpb-update__structure-hint" :class="{ 'mpb-update__structure-hint--error': !structureInfo.success }">
								<template v-if="structureInfo.success">
									{{ loc('MPBUILDER_UPDATE_STRUCTURE_SAVED') }} ({{ structureInfo.count }})
								</template>
								<template v-else>
									{{ structureInfo.error }}
								</template>
							</div>
							<textarea
								id="mpb-updater-editor"
								class="mpb-update__textarea mpb-update__textarea--mono"
								v-model="updater"
								rows="8"
							></textarea>
						</div>
					</div>
				</div>

				<div class="mpb-update__actions" v-if="!prepareResult && !buildResult">
					<button
						class="mpb-update__button mpb-update__button--primary"
						:disabled="!canPrepare"
						@click="prepareUpdate"
					>
						<template v-if="isPreparing">
							<span class="mpb-update__spinner mpb-update__spinner--sm"></span>
							{{ loc('MPBUILDER_UPDATE_PREPARING') }}
						</template>
						<template v-else>
							{{ loc('MPBUILDER_UPDATE_PREPARE_BUTTON') }}
						</template>
					</button>
				</div>

			</template>

		</div>

		<div v-if="prepareResult || buildResult" class="mpb-update__layout-sidebar">

			<div v-if="prepareResult" class="mpb-update__card mpb-update__card--preview">
				<div class="mpb-update__card-header mpb-update__card-header--preview">
					<h4 class="mpb-update__card-title">
						<span class="ui-icon-set --file"></span>
						{{ loc('MPBUILDER_UPDATE_PREVIEW_TITLE') }}
					</h4>
				</div>
				<div class="mpb-update__card-body">
					<div class="mpb-update__preview-summary">
						<div class="mpb-update__preview-info">
							<span class="mpb-update__preview-info-label">{{ loc('MPBUILDER_UPDATE_PREVIEW_MODULE') }}</span>
							<span class="mpb-update__preview-info-value">{{ selectedModuleId }}</span>
						</div>
						<div class="mpb-update__preview-info">
							<span class="mpb-update__preview-info-label">{{ loc('MPBUILDER_UPDATE_PREVIEW_VERSION') }}</span>
							<span class="mpb-update__preview-info-value">{{ version }}</span>
						</div>

						<div class="mpb-update__preview-stats">
							<div class="mpb-update__preview-stat">
								<span class="mpb-update__preview-stat-value mpb-update__preview-stat-value--included">{{ prepareResult.includedCount }}</span>
								<span class="mpb-update__preview-stat-label">{{ loc('MPBUILDER_UPDATE_FILES_INCLUDED') }}</span>
							</div>
							<div class="mpb-update__preview-stat">
								<span class="mpb-update__preview-stat-value mpb-update__preview-stat-value--excluded">{{ prepareResult.excludedCount }}</span>
								<span class="mpb-update__preview-stat-label">{{ loc('MPBUILDER_UPDATE_FILES_EXCLUDED') }}</span>
							</div>
							<div v-if="prepareResult.skippedByDate > 0" class="mpb-update__preview-stat">
								<span class="mpb-update__preview-stat-value">{{ prepareResult.skippedByDate }}</span>
								<span class="mpb-update__preview-stat-label">{{ loc('MPBUILDER_UPDATE_FILES_SKIPPED') }}</span>
							</div>
						</div>

						<div v-if="prepareResult.versionDate" class="mpb-update__preview-date">
							{{ loc('MPBUILDER_UPDATE_FILES_SINCE') }}: {{ prepareResult.versionDate }}
						</div>

						<div v-if="prepareResult.hasComponentSync" class="mpb-update__preview-note">
							{{ loc('MPBUILDER_UPDATE_COMPONENT_SYNC_NOTE') }}
						</div>
					</div>

					<div class="mpb-update__file-list">
						<div class="mpb-update__file-list-header" @click="prepareIncludedExpanded = !prepareIncludedExpanded">
							<span class="ui-icon-set --chevron-down mpb-update__file-list-chevron" :class="{ 'mpb-update__file-list-chevron--expanded': prepareIncludedExpanded }"></span>
							{{ loc('MPBUILDER_UPDATE_FILES_INCLUDED') }}
							<span class="mpb-update__file-list-count">{{ prepareResult.includedCount }}</span>
						</div>
						<div v-if="prepareIncludedExpanded" class="mpb-update__file-list-body">
							<div
								v-for="file in prepareResult.includedFiles"
								:key="file"
								class="mpb-update__file-list-item"
							>{{ file }}</div>
						</div>
					</div>

					<div v-if="prepareResult.excludedCount > 0" class="mpb-update__file-list">
						<div class="mpb-update__file-list-header" @click="prepareExcludedExpanded = !prepareExcludedExpanded">
							<span class="ui-icon-set --chevron-down mpb-update__file-list-chevron" :class="{ 'mpb-update__file-list-chevron--expanded': prepareExcludedExpanded }"></span>
							{{ loc('MPBUILDER_UPDATE_FILES_EXCLUDED') }}
							<span class="mpb-update__file-list-count mpb-update__file-list-count--excluded">{{ prepareResult.excludedCount }}</span>
						</div>
						<div v-if="prepareExcludedExpanded" class="mpb-update__file-list-body">
							<div
								v-for="file in prepareResult.excludedFiles"
								:key="file"
								class="mpb-update__file-list-item mpb-update__file-list-item--excluded"
							>{{ file }}</div>
						</div>
					</div>

					<div class="mpb-update__preview-actions">
						<button
							class="mpb-update__button mpb-update__button--primary"
							:disabled="!canBuild"
							@click="buildUpdate"
						>
							<template v-if="isBuilding">
								<span class="mpb-update__spinner mpb-update__spinner--sm"></span>
								{{ loc('MPBUILDER_UPDATE_BUILDING') }}
							</template>
							<template v-else>
								{{ loc('MPBUILDER_UPDATE_BUILD_BUTTON') }}
							</template>
						</button>
						<button
							class="mpb-update__button mpb-update__button--secondary"
							:disabled="isBuilding || isPreparing"
							@click="prepareUpdate"
						>
							<template v-if="isPreparing">
								<span class="mpb-update__spinner mpb-update__spinner--sm mpb-update__spinner--secondary"></span>
							</template>
							<template v-else>
								{{ loc('MPBUILDER_UPDATE_REPREPARE_BUTTON') }}
							</template>
						</button>
						<button
							class="mpb-update__button mpb-update__button--ghost"
							:disabled="isBuilding"
							@click="cancelPrepare"
						>{{ loc('MPBUILDER_UPDATE_CANCEL_PREPARE') }}</button>
					</div>
				</div>
			</div>

			<div v-if="buildResult" class="mpb-update__card mpb-update__card--success">
				<div class="mpb-update__card-header mpb-update__card-header--success">
					<h4 class="mpb-update__card-title">
						<span class="ui-icon-set --circle-check mpb-update__icon--success"></span>
						{{ loc('MPBUILDER_UPDATE_SUCCESS') }}
					</h4>
				</div>
				<div class="mpb-update__card-body">

					<div v-if="buildResult.strategy === 'dev'" class="mpb-update__dev-path">
						<span class="mpb-update__dev-path-label">{{ loc('MPBUILDER_UPDATE_DEV_PATH') }}</span>
						<code class="mpb-update__dev-path-value">{{ buildResult.devPath }}</code>
					</div>

					<div class="mpb-update__result-links">
							<a :href="buildResult.filemanLink" target="_blank" class="mpb-update__result-link">
								<span class="ui-icon-set --open-in-40"></span>
								{{ loc('MPBUILDER_UPDATE_OPEN_FOLDER') }}
							</a>
							<a :href="buildResult.downloadLink" class="mpb-update__result-link">
								<span class="ui-icon-set --download"></span>
								{{ loc('MPBUILDER_UPDATE_DOWNLOAD_ARCHIVE') }}
							</a>
							<a :href="buildResult.marketplaceLink" target="_blank" class="mpb-update__result-link">
								<span class="ui-icon-set --cloud-transfer-data"></span>
								{{ loc('MPBUILDER_UPDATE_UPLOAD_MARKETPLACE') }}
							</a>
						</div>
					
					<div class="mpb-update__file-list">
						<div class="mpb-update__file-list-header" @click="fileListExpanded = !fileListExpanded">
							<span class="ui-icon-set --chevron-down mpb-update__file-list-chevron" :class="{ 'mpb-update__file-list-chevron--expanded': fileListExpanded }"></span>
							{{ loc('MPBUILDER_UPDATE_FILE_LIST') }}
							<span class="mpb-update__file-list-count">{{ buildResult.fileList.length }}</span>
						</div>
						<div v-if="fileListExpanded" class="mpb-update__file-list-body">
							<div
								v-for="file in buildResult.fileList"
								:key="file"
								class="mpb-update__file-list-item"
							>{{ file }}</div>
						</div>
					</div>

					<div class="mpb-update__actions mpb-update__actions--secondary">
						<button
							class="mpb-update__button mpb-update__button--danger"
							@click="deleteTemp"
						>{{ loc('MPBUILDER_UPDATE_DELETE_TEMP') }}</button>
					</div>
				</div>
			</div>

			<div v-if="buildErrors.length" class="mpb-update__card mpb-update__card--error">
				<div class="mpb-update__card-header mpb-update__card-header--error">
					<h4 class="mpb-update__card-title">{{ loc('MPBUILDER_UPDATE_ERROR') }}</h4>
				</div>
				<div class="mpb-update__card-body">
					<ul class="mpb-update__error-list">
						<li v-for="(error, index) in buildErrors" :key="index">{{ error }}</li>
					</ul>
				</div>
			</div>

		</div>
	</div>

</div>
`;
