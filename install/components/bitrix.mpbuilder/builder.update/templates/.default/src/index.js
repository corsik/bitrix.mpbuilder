import { APP_CONTAINER_ID, COMPONENT_NAME } from './constants';
import { appTemplate } from './template';
import { applyAutoBlock as applyAutoBlockToUpdater } from './updater-generator';

const BuilderUpdateApp = {
	props: {
		params: { type: Object, required: true },
	},

	data()
	{
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

			isPreparing: false,
			prepareResult: null,
			prepareIncludedExpanded: true,
			prepareExcludedExpanded: true,

			isBuilding: false,
			buildResult: null,
			buildErrors: [],

			fileListExpanded: false,

			isGeneratingStructure: false,
			isAnalyzingStructure: false,
			structureInfo: null,

			devVersions: [],
			isLoadingDevVersion: false,

			descriptionEditor: null,
			updaterEditor: null,
			editorKey: 0,
		};
	},

	computed: {
		canPrepare()
		{
			return this.selectedModuleId
				&& this.version
				&& !this.isPreparing
				&& !this.isBuilding;
		},

		canBuild()
		{
			return this.prepareResult
				&& !this.isBuilding;
		},
	},

	mounted()
	{
		this.loadModules();
	},

	watch: {
		selectedModuleId(newVal)
		{
			this.prepareResult = null;
			this.buildResult = null;
			this.buildErrors = [];
			this.structureInfo = null;

			if (newVal)
			{
				this.loadModuleInfo(newVal);
			}
			else
			{
				this.moduleInfo = null;
			}
		},
	},

	methods: {
		loc(messageId)
		{
			return BX.message(messageId) || '';
		},

		async runAction(action, data = {})
		{
			return BX.ajax.runComponentAction(
				COMPONENT_NAME,
				action,
				{ mode: 'class', data },
			);
		},

		async loadModules()
		{
			try
			{
				const response = await this.runAction('getModules');
				this.modules = response.data.modules || [];
			}
			catch (error)
			{
				console.error('Failed to load modules', error);
			}
			finally
			{
				if (this.selectedModuleId)
				{
					this.loadModuleInfo(this.selectedModuleId);
				}
			}
		},

		async loadModuleInfo(moduleId)
		{
			try
			{
				this.isLoadingModuleInfo = true;
				this.moduleInfo = null;
				this.prepareResult = null;
				this.buildResult = null;
				this.buildErrors = [];

				const response = await this.runAction('getModuleInfo', { moduleId });
				const info = response.data;

				this.moduleInfo = info;
				this.devVersions = info.devVersions || [];
				this.version = info.nextVersion || '';
				this.namespace = info.namespace || '';
				this.description = info.description || '<ul>\n  <li></li>\n</ul>';
				this.updater = info.updater || '';
				this.storeVersion = false;
				this.components = false;

				const savedVersion = this.getSavedVersion(moduleId);

				if (savedVersion && this.devVersions.includes(savedVersion))
				{
					await this.selectDevVersion(savedVersion);
				}
			}
			catch (error)
			{
				console.error('Failed to load module info', error);
			}
			finally
			{
				this.isLoadingModuleInfo = false;
				this.$nextTick(() => this.initCodeEditors());
			}
		},

		initCodeEditors()
		{
			if (!this.params.useCodeEditor || !window.JCCodeEditor)
			{
				return;
			}

			const editorMessages = this.params.codeEditorMessages || {};

			if (document.getElementById('mpb-description-editor'))
			{
				this.descriptionEditor = new window.JCCodeEditor({
					textareaId: 'mpb-description-editor',
					height: 350,
					forceSyntax: 'php',
					highlightMode: true,
				}, editorMessages);
			}

			if (document.getElementById('mpb-updater-editor'))
			{
				this.updaterEditor = new window.JCCodeEditor({
					textareaId: 'mpb-updater-editor',
					height: 350,
					forceSyntax: 'php',
					highlightMode: true,
				}, editorMessages);
			}
		},

		syncEditorValues()
		{
			if (this.descriptionEditor)
			{
				this.descriptionEditor.Save();
				const el = document.getElementById('mpb-description-editor');
				if (el)
				{
					this.description = el.value;
				}
			}

			if (this.updaterEditor)
			{
				this.updaterEditor.Save();
				const el = document.getElementById('mpb-updater-editor');
				if (el)
				{
					this.updater = el.value;
				}
			}
		},

		async prepareUpdate()
		{
			if (!this.canPrepare)
			{
				return;
			}

			this.syncEditorValues();
			this.isPreparing = true;
			this.buildErrors = [];
			this.prepareIncludedExpanded = true;
			this.prepareExcludedExpanded = true;

			try
			{
				const response = await this.runAction('prepareUpdate', {
					moduleId: this.selectedModuleId,
					version: this.version,
					components: this.components,
					namespace: this.namespace,
				});

				this.prepareResult = response.data;
			}
			catch (response)
			{
				console.error('prepareUpdate error:', response);

				if (response.errors && response.errors.length)
				{
					this.buildErrors = response.errors.map(e => e.message);
				}
				else
				{
					this.buildErrors = ['Unknown error'];
				}
			}
			finally
			{
				this.isPreparing = false;
			}
		},

		cancelPrepare()
		{
			this.prepareResult = null;
			this.buildResult = null;
		},

		async buildUpdate()
		{
			if (!this.canBuild)
			{
				return;
			}

			this.syncEditorValues();

			this.isBuilding = true;
			this.buildResult = null;
			this.buildErrors = [];
			this.fileListExpanded = false;

			try
			{
				const response = await this.runAction('buildUpdate', {
					moduleId: this.selectedModuleId,
					version: this.version,
					description: this.description,
					updater: this.updater,
					storeVersion: this.storeVersion,
					components: this.components,
					namespace: this.namespace,
				});

				this.buildResult = response.data;
			}
			catch (response)
			{
				if (response.errors && response.errors.length)
				{
					this.buildErrors = response.errors.map(e => e.message);
				}
				else
				{
					this.buildErrors = ['Unknown error'];
				}
			}
			finally
			{
				this.isBuilding = false;
			}
		},

		async deleteTemp()
		{
			if (!confirm(this.loc('MPBUILDER_UPDATE_DELETE_CONFIRM')))
			{
				return;
			}

			try
			{
				await this.runAction('deleteTemp', {
					moduleId: this.selectedModuleId,
				});

				this.buildResult = null;
			}
			catch (error)
			{
				console.error('Failed to delete temp', error);
			}
		},

		async generateStructure()
		{
			this.isGeneratingStructure = true;
			this.structureInfo = null;

			try
			{
				const response = await this.runAction('generateStructure', {
					moduleId: this.selectedModuleId,
					version: this.version,
				});

				this.structureInfo = { success: true, ...response.data };
			}
			catch (err)
			{
				const msg = err?.errors?.[0]?.message || err?.message || 'Unknown error';
				this.structureInfo = { success: false, error: msg };
			}
			finally
			{
				this.isGeneratingStructure = false;
			}
		},

		async analyzeStructure()
		{
			this.isAnalyzingStructure = true;

			let data = null;

			try
			{
				const response = await this.runAction('analyzeStructure', {
					moduleId: this.selectedModuleId,
					version: this.version,
				});

				data = response.data;
			}
			catch (err)
			{
				const msg = err?.errors?.[0]?.message || err?.message || 'Unknown error';
				alert(this.loc('MPBUILDER_UPDATE_ANALYZE_ERROR') + ': ' + msg);
				return;
			}
			finally
			{
				this.isAnalyzingStructure = false;
			}

			if (data)
			{
				this.applyAutoBlock(data);
			}
		},

		applyAutoBlock(data)
		{
			data.moduleId = data.moduleId || this.selectedModuleId;
			this.updater = applyAutoBlockToUpdater(this.updater || '', data);
			this.refreshEditors();
		},

		selectNewVersion()
		{
			this.version = this.moduleInfo.nextVersion || '';
			this.description = this.moduleInfo.description || '<ul>\n  <li></li>\n</ul>';
			this.updater = this.moduleInfo.updater || '';
			this.prepareResult = null;
			this.buildResult = null;
			this.buildErrors = [];
			this.structureInfo = null;
			this.saveVersion(this.selectedModuleId, null);
			this.$nextTick(() => this.refreshEditors());
		},

		async selectDevVersion(ver)
		{
			if (this.isLoadingDevVersion || this.isBuilding)
			{
				return;
			}

			this.isLoadingDevVersion = true;
			this.prepareResult = null;
			this.buildResult = null;
			this.buildErrors = [];
			this.structureInfo = null;

			try
			{
				const response = await this.runAction('loadDevVersion', {
					moduleId: this.selectedModuleId,
					version: ver,
				});

				const data = response.data;
				this.version = data.version || ver;
				this.description = data.description || '<ul>\n  <li></li>\n</ul>';
				this.updater = data.updater || '';
				this.saveVersion(this.selectedModuleId, ver);
				this.$nextTick(() => this.refreshEditors());
			}
			catch (err)
			{
				console.error('Failed to load dev version', err);
			}
			finally
			{
				this.isLoadingDevVersion = false;
			}
		},

		saveVersion(moduleId, version)
		{
			try
			{
				const key = `mpbuilder_version_${moduleId}`;

				if (version)
				{
					sessionStorage.setItem(key, version);
				}
				else
				{
					sessionStorage.removeItem(key);
				}
			}
			catch (e) {}
		},

		getSavedVersion(moduleId)
		{
			try
			{
				return sessionStorage.getItem(`mpbuilder_version_${moduleId}`) || null;
			}
			catch (e)
			{
				return null;
			}
		},

		refreshEditors()
		{
			this.descriptionEditor = null;
			this.updaterEditor = null;
			this.editorKey++;
			this.$nextTick(() => this.initCodeEditors());
		},

		async restoreVersion()
		{
			if (!confirm(this.loc('MPBUILDER_UPDATE_RESTORE_CONFIRM')))
			{
				return;
			}

			try
			{
				const response = await this.runAction('restoreVersion', {
					moduleId: this.selectedModuleId,
				});

				if (response.data)
				{
					this.loadModuleInfo(this.selectedModuleId);
				}
			}
			catch (error)
			{
				console.error('Failed to restore version', error);
			}
		},
	},

	template: appTemplate,
};

BX.ready(() => {
	const container = document.getElementById(APP_CONTAINER_ID);

	if (!container)
	{
		return;
	}

	let params = {};

	try
	{
		params = JSON.parse(container.dataset.params || '{}');
	}
	catch (e)
	{
		console.error('BuilderUpdate: failed to parse params', e);

		return;
	}

	const app = BX.Vue3.BitrixVue.createApp(BuilderUpdateApp, { params });
	app.mount(container);
});
