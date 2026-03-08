import { APP_CONTAINER_ID, COMPONENT_NAME } from './constants';
import { appTemplate } from './template';

const BuilderArchiveApp = {
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
			updateVersion: false,

			isPreviewing: false,
			previewResult: null,
			includedExpanded: false,
			excludedExpanded: true,

			isBuilding: false,
			buildResult: null,
			buildErrors: [],

			fileListExpanded: false,
		};
	},

	computed: {
		canPreview()
		{
			return this.selectedModuleId
				&& !this.isPreviewing
				&& !this.isBuilding;
		},

		canBuild()
		{
			return this.previewResult
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
			this.previewResult = null;
			this.buildResult = null;
			this.buildErrors = [];

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
				this.previewResult = null;
				this.buildResult = null;
				this.buildErrors = [];

				const response = await this.runAction('getModuleInfo', { moduleId });
				const info = response.data;

				this.moduleInfo = info;
				this.version = info.nextVersion || '';
				this.updateVersion = false;
			}
			catch (error)
			{
				console.error('Failed to load module info', error);
			}
			finally
			{
				this.isLoadingModuleInfo = false;
			}
		},

		async previewArchive()
		{
			if (!this.canPreview)
			{
				return;
			}

			this.isPreviewing = true;
			this.buildErrors = [];
			this.excludedExpanded = true;
			this.includedExpanded = false;

			try
			{
				const response = await this.runAction('previewArchive', {
					moduleId: this.selectedModuleId,
				});

				this.previewResult = response.data;
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
				this.isPreviewing = false;
			}
		},

		cancelPreview()
		{
			this.previewResult = null;
			this.buildResult = null;
		},

		async buildArchive()
		{
			if (!this.canBuild)
			{
				return;
			}

			this.isBuilding = true;
			this.buildResult = null;
			this.buildErrors = [];
			this.fileListExpanded = false;

			try
			{
				const response = await this.runAction('buildArchive', {
					moduleId: this.selectedModuleId,
					version: this.updateVersion ? this.version : '',
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
			if (!confirm(this.loc('MPBUILDER_ARCHIVE_DELETE_CONFIRM')))
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
		console.error('BuilderArchive: failed to parse params', e);

		return;
	}

	const app = BX.Vue3.BitrixVue.createApp(BuilderArchiveApp, { params });
	app.mount(container);
});
