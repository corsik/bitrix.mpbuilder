export const appTemplate = `
<div class="mpb-update">

	<div class="mpb-update__layout" :class="{ 'mpb-update__layout--split': previewResult || buildResult }">
		<div class="mpb-update__layout-main">

			<div class="mpb-update__note">
				<span class="ui-icon-set --info-circle mpb-update__note-icon"></span>
				<div class="mpb-update__note-text">{{ loc('MPBUILDER_ARCHIVE_NOTE') }}</div>
			</div>

			<div class="mpb-update__card">
				<div class="mpb-update__card-header">
					<h4 class="mpb-update__card-title">{{ loc('MPBUILDER_ARCHIVE_SELECT_MODULE') }}</h4>
				</div>
				<div class="mpb-update__card-body">
					<div class="mpb-update__field">
						<select
							class="mpb-update__select"
							v-model="selectedModuleId"
							:disabled="isBuilding"
						>
							<option value="">{{ loc('MPBUILDER_ARCHIVE_SELECT_MODULE_PLACEHOLDER') }}</option>
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
						<span>{{ loc('MPBUILDER_ARCHIVE_LOADING') }}</span>
					</div>
				</div>
			</template>

			<template v-if="moduleInfo && !isLoadingModuleInfo">

				<div class="mpb-update__card" :class="{ 'mpb-update__card--disabled': isBuilding }">
					<div class="mpb-update__card-header">
						<h4 class="mpb-update__card-title">{{ loc('MPBUILDER_ARCHIVE_VERSION_SETTINGS') }}</h4>
					</div>
					<div class="mpb-update__card-body">
						<div class="mpb-update__field">
							<label class="mpb-update__label">{{ loc('MPBUILDER_ARCHIVE_VERSION_LABEL') }}</label>
							<input
								type="text"
								class="mpb-update__input"
								v-model="version"
								:disabled="!updateVersion"
							/>
						</div>

						<div class="mpb-update__field">
							<label class="mpb-update__checkbox">
								<input type="checkbox" v-model="updateVersion" />
								<span class="mpb-update__checkbox-text">{{ loc('MPBUILDER_ARCHIVE_UPDATE_VERSION') }}</span>
							</label>
						</div>
					</div>
				</div>

				<div class="mpb-update__actions" v-if="!previewResult && !buildResult">
					<button
						class="mpb-update__button mpb-update__button--primary"
						:disabled="!canPreview"
						@click="previewArchive"
					>
						<template v-if="isPreviewing">
							<span class="mpb-update__spinner mpb-update__spinner--sm"></span>
							{{ loc('MPBUILDER_ARCHIVE_PREVIEWING') }}
						</template>
						<template v-else>
							{{ loc('MPBUILDER_ARCHIVE_PREVIEW_BUTTON') }}
						</template>
					</button>
				</div>

			</template>

		</div>

		<div v-if="previewResult || buildResult" class="mpb-update__layout-sidebar">

			<div v-if="previewResult" class="mpb-update__card mpb-update__card--preview">
				<div class="mpb-update__card-header mpb-update__card-header--preview">
					<h4 class="mpb-update__card-title">
						<span class="ui-icon-set --file"></span>
						{{ loc('MPBUILDER_ARCHIVE_PREVIEW_TITLE') }}
					</h4>
				</div>
				<div class="mpb-update__card-body">
					<div class="mpb-update__preview-summary">
						<div class="mpb-update__preview-info">
							<span class="mpb-update__preview-info-label">{{ loc('MPBUILDER_ARCHIVE_PREVIEW_MODULE') }}</span>
							<span class="mpb-update__preview-info-value">{{ selectedModuleId }}</span>
						</div>
						<div class="mpb-update__preview-info">
							<span class="mpb-update__preview-info-label">{{ loc('MPBUILDER_ARCHIVE_PREVIEW_VERSION') }}</span>
							<span class="mpb-update__preview-info-value">{{ version }}</span>
						</div>

						<div class="mpb-update__preview-stats">
							<div class="mpb-update__preview-stat">
								<span class="mpb-update__preview-stat-value mpb-update__preview-stat-value--included">{{ previewResult.includedCount }}</span>
								<span class="mpb-update__preview-stat-label">{{ loc('MPBUILDER_ARCHIVE_FILES_INCLUDED') }}</span>
							</div>
							<div class="mpb-update__preview-stat">
								<span class="mpb-update__preview-stat-value mpb-update__preview-stat-value--excluded">{{ previewResult.excludedCount }}</span>
								<span class="mpb-update__preview-stat-label">{{ loc('MPBUILDER_ARCHIVE_FILES_EXCLUDED') }}</span>
							</div>
						</div>
					</div>

					<div v-if="previewResult.excludedCount > 0" class="mpb-update__file-list">
						<div class="mpb-update__file-list-header" @click="excludedExpanded = !excludedExpanded">
							<span class="ui-icon-set --chevron-down mpb-update__file-list-chevron" :class="{ 'mpb-update__file-list-chevron--expanded': excludedExpanded }"></span>
							{{ loc('MPBUILDER_ARCHIVE_FILES_EXCLUDED') }}
							<span class="mpb-update__file-list-count mpb-update__file-list-count--excluded">{{ previewResult.excludedCount }}</span>
						</div>
						<div v-if="excludedExpanded" class="mpb-update__file-list-body">
							<div
								v-for="file in previewResult.excludedFiles"
								:key="file"
								class="mpb-update__file-list-item mpb-update__file-list-item--excluded"
							>{{ file }}</div>
						</div>
					</div>

					<div class="mpb-update__file-list">
						<div class="mpb-update__file-list-header" @click="includedExpanded = !includedExpanded">
							<span class="ui-icon-set --chevron-down mpb-update__file-list-chevron" :class="{ 'mpb-update__file-list-chevron--expanded': includedExpanded }"></span>
							{{ loc('MPBUILDER_ARCHIVE_FILES_INCLUDED') }}
							<span class="mpb-update__file-list-count">{{ previewResult.includedCount }}</span>
						</div>
						<div v-if="includedExpanded" class="mpb-update__file-list-body">
							<div
								v-for="file in previewResult.includedFiles"
								:key="file"
								class="mpb-update__file-list-item"
							>{{ file }}</div>
						</div>
					</div>

					<div class="mpb-update__preview-actions">
						<button
							class="mpb-update__button mpb-update__button--primary"
							:disabled="!canBuild"
							@click="buildArchive"
						>
							<template v-if="isBuilding">
								<span class="mpb-update__spinner mpb-update__spinner--sm"></span>
								{{ loc('MPBUILDER_ARCHIVE_BUILDING') }}
							</template>
							<template v-else>
								{{ loc('MPBUILDER_ARCHIVE_BUILD_BUTTON') }}
							</template>
						</button>
						<button
							class="mpb-update__button mpb-update__button--secondary"
							:disabled="isBuilding || isPreviewing"
							@click="previewArchive"
						>
							<template v-if="isPreviewing">
								<span class="mpb-update__spinner mpb-update__spinner--sm mpb-update__spinner--secondary"></span>
							</template>
							<template v-else>
								{{ loc('MPBUILDER_ARCHIVE_REBUILD_PREVIEW') }}
							</template>
						</button>
						<button
							class="mpb-update__button mpb-update__button--ghost"
							:disabled="isBuilding"
							@click="cancelPreview"
						>{{ loc('MPBUILDER_ARCHIVE_CANCEL_PREVIEW') }}</button>
					</div>
				</div>
			</div>

			<div v-if="buildResult" class="mpb-update__card mpb-update__card--success">
				<div class="mpb-update__card-header mpb-update__card-header--success">
					<h4 class="mpb-update__card-title">
						<span class="ui-icon-set --circle-check mpb-update__icon--success"></span>
						{{ loc('MPBUILDER_ARCHIVE_SUCCESS') }}
					</h4>
				</div>
				<div class="mpb-update__card-body">
					<div class="mpb-update__result-links">
						<a :href="buildResult.filemanLink" target="_blank" class="mpb-update__result-link">
							<span class="ui-icon-set --open-in-40"></span>
							{{ loc('MPBUILDER_ARCHIVE_OPEN_FOLDER') }}
						</a>
						<a :href="buildResult.downloadLink" class="mpb-update__result-link">
							<span class="ui-icon-set --download"></span>
							{{ loc('MPBUILDER_ARCHIVE_DOWNLOAD_ARCHIVE') }}
						</a>
						<a :href="buildResult.marketplaceLink" target="_blank" class="mpb-update__result-link">
							<span class="ui-icon-set --cloud-transfer-data"></span>
							{{ loc('MPBUILDER_ARCHIVE_UPLOAD_MARKETPLACE') }}
						</a>
					</div>

					<div class="mpb-update__file-list">
						<div class="mpb-update__file-list-header" @click="fileListExpanded = !fileListExpanded">
							<span class="ui-icon-set --chevron-down mpb-update__file-list-chevron" :class="{ 'mpb-update__file-list-chevron--expanded': fileListExpanded }"></span>
							{{ loc('MPBUILDER_ARCHIVE_FILE_LIST') }}
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
						>{{ loc('MPBUILDER_ARCHIVE_DELETE_TEMP') }}</button>
					</div>
				</div>
			</div>

			<div v-if="buildErrors.length" class="mpb-update__card mpb-update__card--error">
				<div class="mpb-update__card-header mpb-update__card-header--error">
					<h4 class="mpb-update__card-title">{{ loc('MPBUILDER_ARCHIVE_ERROR') }}</h4>
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
