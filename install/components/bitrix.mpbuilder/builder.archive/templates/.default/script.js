/* eslint-disable */
(function (exports) {
	'use strict';

	var APP_CONTAINER_ID = 'mpbuilder-archive-app';
	var COMPONENT_NAME = 'bitrix.mpbuilder:builder.archive';

	var appTemplate = "\n<div class=\"mpb-update\">\n\n\t<div class=\"mpb-update__layout\" :class=\"{ 'mpb-update__layout--split': previewResult || buildResult }\">\n\t\t<div class=\"mpb-update__layout-main\">\n\n\t\t\t<div class=\"mpb-update__note\">\n\t\t\t\t<span class=\"ui-icon-set --info-circle mpb-update__note-icon\"></span>\n\t\t\t\t<div class=\"mpb-update__note-text\">{{ loc('MPBUILDER_ARCHIVE_NOTE') }}</div>\n\t\t\t</div>\n\n\t\t\t<div class=\"mpb-update__card\">\n\t\t\t\t<div class=\"mpb-update__card-header\">\n\t\t\t\t\t<h4 class=\"mpb-update__card-title\">{{ loc('MPBUILDER_ARCHIVE_SELECT_MODULE') }}</h4>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"mpb-update__card-body\">\n\t\t\t\t\t<div class=\"mpb-update__field\">\n\t\t\t\t\t\t<select\n\t\t\t\t\t\t\tclass=\"mpb-update__select\"\n\t\t\t\t\t\t\tv-model=\"selectedModuleId\"\n\t\t\t\t\t\t\t:disabled=\"isBuilding\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t<option value=\"\">{{ loc('MPBUILDER_ARCHIVE_SELECT_MODULE_PLACEHOLDER') }}</option>\n\t\t\t\t\t\t\t<option\n\t\t\t\t\t\t\t\tv-for=\"mod in modules\"\n\t\t\t\t\t\t\t\t:key=\"mod\"\n\t\t\t\t\t\t\t\t:value=\"mod\"\n\t\t\t\t\t\t\t>{{ mod }}</option>\n\t\t\t\t\t\t</select>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\n\t\t\t<template v-if=\"isLoadingModuleInfo\">\n\t\t\t\t<div class=\"mpb-update__card\">\n\t\t\t\t\t<div class=\"mpb-update__card-body mpb-update__loading\">\n\t\t\t\t\t\t<div class=\"mpb-update__spinner\"></div>\n\t\t\t\t\t\t<span>{{ loc('MPBUILDER_ARCHIVE_LOADING') }}</span>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</template>\n\n\t\t\t<template v-if=\"moduleInfo && !isLoadingModuleInfo\">\n\n\t\t\t\t<div class=\"mpb-update__card\" :class=\"{ 'mpb-update__card--disabled': isBuilding }\">\n\t\t\t\t\t<div class=\"mpb-update__card-header\">\n\t\t\t\t\t\t<h4 class=\"mpb-update__card-title\">{{ loc('MPBUILDER_ARCHIVE_VERSION_SETTINGS') }}</h4>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"mpb-update__card-body\">\n\t\t\t\t\t\t<div class=\"mpb-update__field\">\n\t\t\t\t\t\t\t<label class=\"mpb-update__label\">{{ loc('MPBUILDER_ARCHIVE_VERSION_LABEL') }}</label>\n\t\t\t\t\t\t\t<input\n\t\t\t\t\t\t\t\ttype=\"text\"\n\t\t\t\t\t\t\t\tclass=\"mpb-update__input\"\n\t\t\t\t\t\t\t\tv-model=\"version\"\n\t\t\t\t\t\t\t\t:disabled=\"!updateVersion\"\n\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t</div>\n\n\t\t\t\t\t\t<div class=\"mpb-update__field\">\n\t\t\t\t\t\t\t<label class=\"mpb-update__checkbox\">\n\t\t\t\t\t\t\t\t<input type=\"checkbox\" v-model=\"updateVersion\" />\n\t\t\t\t\t\t\t\t<span class=\"mpb-update__checkbox-text\">{{ loc('MPBUILDER_ARCHIVE_UPDATE_VERSION') }}</span>\n\t\t\t\t\t\t\t</label>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\n\t\t\t\t<div class=\"mpb-update__actions\" v-if=\"!previewResult && !buildResult\">\n\t\t\t\t\t<button\n\t\t\t\t\t\tclass=\"mpb-update__button mpb-update__button--primary\"\n\t\t\t\t\t\t:disabled=\"!canPreview\"\n\t\t\t\t\t\t@click=\"previewArchive\"\n\t\t\t\t\t>\n\t\t\t\t\t\t<template v-if=\"isPreviewing\">\n\t\t\t\t\t\t\t<span class=\"mpb-update__spinner mpb-update__spinner--sm\"></span>\n\t\t\t\t\t\t\t{{ loc('MPBUILDER_ARCHIVE_PREVIEWING') }}\n\t\t\t\t\t\t</template>\n\t\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t\t{{ loc('MPBUILDER_ARCHIVE_PREVIEW_BUTTON') }}\n\t\t\t\t\t\t</template>\n\t\t\t\t\t</button>\n\t\t\t\t</div>\n\n\t\t\t</template>\n\n\t\t</div>\n\n\t\t<div v-if=\"previewResult || buildResult\" class=\"mpb-update__layout-sidebar\">\n\n\t\t\t<div v-if=\"previewResult\" class=\"mpb-update__card mpb-update__card--preview\">\n\t\t\t\t<div class=\"mpb-update__card-header mpb-update__card-header--preview\">\n\t\t\t\t\t<h4 class=\"mpb-update__card-title\">\n\t\t\t\t\t\t<span class=\"ui-icon-set --file\"></span>\n\t\t\t\t\t\t{{ loc('MPBUILDER_ARCHIVE_PREVIEW_TITLE') }}\n\t\t\t\t\t</h4>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"mpb-update__card-body\">\n\t\t\t\t\t<div class=\"mpb-update__preview-summary\">\n\t\t\t\t\t\t<div class=\"mpb-update__preview-info\">\n\t\t\t\t\t\t\t<span class=\"mpb-update__preview-info-label\">{{ loc('MPBUILDER_ARCHIVE_PREVIEW_MODULE') }}</span>\n\t\t\t\t\t\t\t<span class=\"mpb-update__preview-info-value\">{{ selectedModuleId }}</span>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"mpb-update__preview-info\">\n\t\t\t\t\t\t\t<span class=\"mpb-update__preview-info-label\">{{ loc('MPBUILDER_ARCHIVE_PREVIEW_VERSION') }}</span>\n\t\t\t\t\t\t\t<span class=\"mpb-update__preview-info-value\">{{ version }}</span>\n\t\t\t\t\t\t</div>\n\n\t\t\t\t\t\t<div class=\"mpb-update__preview-stats\">\n\t\t\t\t\t\t\t<div class=\"mpb-update__preview-stat\">\n\t\t\t\t\t\t\t\t<span class=\"mpb-update__preview-stat-value mpb-update__preview-stat-value--included\">{{ previewResult.includedCount }}</span>\n\t\t\t\t\t\t\t\t<span class=\"mpb-update__preview-stat-label\">{{ loc('MPBUILDER_ARCHIVE_FILES_INCLUDED') }}</span>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"mpb-update__preview-stat\">\n\t\t\t\t\t\t\t\t<span class=\"mpb-update__preview-stat-value mpb-update__preview-stat-value--excluded\">{{ previewResult.excludedCount }}</span>\n\t\t\t\t\t\t\t\t<span class=\"mpb-update__preview-stat-label\">{{ loc('MPBUILDER_ARCHIVE_FILES_EXCLUDED') }}</span>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\n\t\t\t\t\t<div v-if=\"previewResult.excludedCount > 0\" class=\"mpb-update__file-list\">\n\t\t\t\t\t\t<div class=\"mpb-update__file-list-header\" @click=\"excludedExpanded = !excludedExpanded\">\n\t\t\t\t\t\t\t<span class=\"ui-icon-set --chevron-down mpb-update__file-list-chevron\" :class=\"{ 'mpb-update__file-list-chevron--expanded': excludedExpanded }\"></span>\n\t\t\t\t\t\t\t{{ loc('MPBUILDER_ARCHIVE_FILES_EXCLUDED') }}\n\t\t\t\t\t\t\t<span class=\"mpb-update__file-list-count mpb-update__file-list-count--excluded\">{{ previewResult.excludedCount }}</span>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div v-if=\"excludedExpanded\" class=\"mpb-update__file-list-body\">\n\t\t\t\t\t\t\t<div\n\t\t\t\t\t\t\t\tv-for=\"file in previewResult.excludedFiles\"\n\t\t\t\t\t\t\t\t:key=\"file\"\n\t\t\t\t\t\t\t\tclass=\"mpb-update__file-list-item mpb-update__file-list-item--excluded\"\n\t\t\t\t\t\t\t>{{ file }}</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\n\t\t\t\t\t<div class=\"mpb-update__file-list\">\n\t\t\t\t\t\t<div class=\"mpb-update__file-list-header\" @click=\"includedExpanded = !includedExpanded\">\n\t\t\t\t\t\t\t<span class=\"ui-icon-set --chevron-down mpb-update__file-list-chevron\" :class=\"{ 'mpb-update__file-list-chevron--expanded': includedExpanded }\"></span>\n\t\t\t\t\t\t\t{{ loc('MPBUILDER_ARCHIVE_FILES_INCLUDED') }}\n\t\t\t\t\t\t\t<span class=\"mpb-update__file-list-count\">{{ previewResult.includedCount }}</span>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div v-if=\"includedExpanded\" class=\"mpb-update__file-list-body\">\n\t\t\t\t\t\t\t<div\n\t\t\t\t\t\t\t\tv-for=\"file in previewResult.includedFiles\"\n\t\t\t\t\t\t\t\t:key=\"file\"\n\t\t\t\t\t\t\t\tclass=\"mpb-update__file-list-item\"\n\t\t\t\t\t\t\t>{{ file }}</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\n\t\t\t\t\t<div class=\"mpb-update__preview-actions\">\n\t\t\t\t\t\t<button\n\t\t\t\t\t\t\tclass=\"mpb-update__button mpb-update__button--primary\"\n\t\t\t\t\t\t\t:disabled=\"!canBuild\"\n\t\t\t\t\t\t\t@click=\"buildArchive\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t<template v-if=\"isBuilding\">\n\t\t\t\t\t\t\t\t<span class=\"mpb-update__spinner mpb-update__spinner--sm\"></span>\n\t\t\t\t\t\t\t\t{{ loc('MPBUILDER_ARCHIVE_BUILDING') }}\n\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t\t\t{{ loc('MPBUILDER_ARCHIVE_BUILD_BUTTON') }}\n\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t</button>\n\t\t\t\t\t\t<button\n\t\t\t\t\t\t\tclass=\"mpb-update__button mpb-update__button--secondary\"\n\t\t\t\t\t\t\t:disabled=\"isBuilding || isPreviewing\"\n\t\t\t\t\t\t\t@click=\"previewArchive\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t<template v-if=\"isPreviewing\">\n\t\t\t\t\t\t\t\t<span class=\"mpb-update__spinner mpb-update__spinner--sm mpb-update__spinner--secondary\"></span>\n\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t\t\t{{ loc('MPBUILDER_ARCHIVE_REBUILD_PREVIEW') }}\n\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t</button>\n\t\t\t\t\t\t<button\n\t\t\t\t\t\t\tclass=\"mpb-update__button mpb-update__button--ghost\"\n\t\t\t\t\t\t\t:disabled=\"isBuilding\"\n\t\t\t\t\t\t\t@click=\"cancelPreview\"\n\t\t\t\t\t\t>{{ loc('MPBUILDER_ARCHIVE_CANCEL_PREVIEW') }}</button>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\n\t\t\t<div v-if=\"buildResult\" class=\"mpb-update__card mpb-update__card--success\">\n\t\t\t\t<div class=\"mpb-update__card-header mpb-update__card-header--success\">\n\t\t\t\t\t<h4 class=\"mpb-update__card-title\">\n\t\t\t\t\t\t<span class=\"ui-icon-set --circle-check mpb-update__icon--success\"></span>\n\t\t\t\t\t\t{{ loc('MPBUILDER_ARCHIVE_SUCCESS') }}\n\t\t\t\t\t</h4>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"mpb-update__card-body\">\n\t\t\t\t\t<div class=\"mpb-update__result-links\">\n\t\t\t\t\t\t<a :href=\"buildResult.filemanLink\" target=\"_blank\" class=\"mpb-update__result-link\">\n\t\t\t\t\t\t\t<span class=\"ui-icon-set --open-in-40\"></span>\n\t\t\t\t\t\t\t{{ loc('MPBUILDER_ARCHIVE_OPEN_FOLDER') }}\n\t\t\t\t\t\t</a>\n\t\t\t\t\t\t<a :href=\"buildResult.downloadLink\" class=\"mpb-update__result-link\">\n\t\t\t\t\t\t\t<span class=\"ui-icon-set --download\"></span>\n\t\t\t\t\t\t\t{{ loc('MPBUILDER_ARCHIVE_DOWNLOAD_ARCHIVE') }}\n\t\t\t\t\t\t</a>\n\t\t\t\t\t\t<a :href=\"buildResult.marketplaceLink\" target=\"_blank\" class=\"mpb-update__result-link\">\n\t\t\t\t\t\t\t<span class=\"ui-icon-set --cloud-transfer-data\"></span>\n\t\t\t\t\t\t\t{{ loc('MPBUILDER_ARCHIVE_UPLOAD_MARKETPLACE') }}\n\t\t\t\t\t\t</a>\n\t\t\t\t\t</div>\n\n\t\t\t\t\t<div class=\"mpb-update__file-list\">\n\t\t\t\t\t\t<div class=\"mpb-update__file-list-header\" @click=\"fileListExpanded = !fileListExpanded\">\n\t\t\t\t\t\t\t<span class=\"ui-icon-set --chevron-down mpb-update__file-list-chevron\" :class=\"{ 'mpb-update__file-list-chevron--expanded': fileListExpanded }\"></span>\n\t\t\t\t\t\t\t{{ loc('MPBUILDER_ARCHIVE_FILE_LIST') }}\n\t\t\t\t\t\t\t<span class=\"mpb-update__file-list-count\">{{ buildResult.fileList.length }}</span>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div v-if=\"fileListExpanded\" class=\"mpb-update__file-list-body\">\n\t\t\t\t\t\t\t<div\n\t\t\t\t\t\t\t\tv-for=\"file in buildResult.fileList\"\n\t\t\t\t\t\t\t\t:key=\"file\"\n\t\t\t\t\t\t\t\tclass=\"mpb-update__file-list-item\"\n\t\t\t\t\t\t\t>{{ file }}</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\n\t\t\t\t\t<div class=\"mpb-update__actions mpb-update__actions--secondary\">\n\t\t\t\t\t\t<button\n\t\t\t\t\t\t\tclass=\"mpb-update__button mpb-update__button--danger\"\n\t\t\t\t\t\t\t@click=\"deleteTemp\"\n\t\t\t\t\t\t>{{ loc('MPBUILDER_ARCHIVE_DELETE_TEMP') }}</button>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\n\t\t\t<div v-if=\"buildErrors.length\" class=\"mpb-update__card mpb-update__card--error\">\n\t\t\t\t<div class=\"mpb-update__card-header mpb-update__card-header--error\">\n\t\t\t\t\t<h4 class=\"mpb-update__card-title\">{{ loc('MPBUILDER_ARCHIVE_ERROR') }}</h4>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"mpb-update__card-body\">\n\t\t\t\t\t<ul class=\"mpb-update__error-list\">\n\t\t\t\t\t\t<li v-for=\"(error, index) in buildErrors\" :key=\"index\">{{ error }}</li>\n\t\t\t\t\t</ul>\n\t\t\t\t</div>\n\t\t\t</div>\n\n\t\t</div>\n\t</div>\n\n</div>\n";

	function _regeneratorRuntime() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime = function _regeneratorRuntime() { return exports; }; var exports = {}, Op = Object.prototype, hasOwn = Op.hasOwnProperty, defineProperty = Object.defineProperty || function (obj, key, desc) { obj[key] = desc.value; }, $Symbol = "function" == typeof Symbol ? Symbol : {}, iteratorSymbol = $Symbol.iterator || "@@iterator", asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator", toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag"; function define(obj, key, value) { return Object.defineProperty(obj, key, { value: value, enumerable: !0, configurable: !0, writable: !0 }), obj[key]; } try { define({}, ""); } catch (err) { define = function define(obj, key, value) { return obj[key] = value; }; } function wrap(innerFn, outerFn, self, tryLocsList) { var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator, generator = Object.create(protoGenerator.prototype), context = new Context(tryLocsList || []); return defineProperty(generator, "_invoke", { value: makeInvokeMethod(innerFn, self, context) }), generator; } function tryCatch(fn, obj, arg) { try { return { type: "normal", arg: fn.call(obj, arg) }; } catch (err) { return { type: "throw", arg: err }; } } exports.wrap = wrap; var ContinueSentinel = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var IteratorPrototype = {}; define(IteratorPrototype, iteratorSymbol, function () { return this; }); var getProto = Object.getPrototypeOf, NativeIteratorPrototype = getProto && getProto(getProto(values([]))); NativeIteratorPrototype && NativeIteratorPrototype !== Op && hasOwn.call(NativeIteratorPrototype, iteratorSymbol) && (IteratorPrototype = NativeIteratorPrototype); var Gp = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(IteratorPrototype); function defineIteratorMethods(prototype) { ["next", "throw", "return"].forEach(function (method) { define(prototype, method, function (arg) { return this._invoke(method, arg); }); }); } function AsyncIterator(generator, PromiseImpl) { function invoke(method, arg, resolve, reject) { var record = tryCatch(generator[method], generator, arg); if ("throw" !== record.type) { var result = record.arg, value = result.value; return value && "object" == babelHelpers["typeof"](value) && hasOwn.call(value, "__await") ? PromiseImpl.resolve(value.__await).then(function (value) { invoke("next", value, resolve, reject); }, function (err) { invoke("throw", err, resolve, reject); }) : PromiseImpl.resolve(value).then(function (unwrapped) { result.value = unwrapped, resolve(result); }, function (error) { return invoke("throw", error, resolve, reject); }); } reject(record.arg); } var previousPromise; defineProperty(this, "_invoke", { value: function value(method, arg) { function callInvokeWithMethodAndArg() { return new PromiseImpl(function (resolve, reject) { invoke(method, arg, resolve, reject); }); } return previousPromise = previousPromise ? previousPromise.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); } }); } function makeInvokeMethod(innerFn, self, context) { var state = "suspendedStart"; return function (method, arg) { if ("executing" === state) throw new Error("Generator is already running"); if ("completed" === state) { if ("throw" === method) throw arg; return doneResult(); } for (context.method = method, context.arg = arg;;) { var delegate = context.delegate; if (delegate) { var delegateResult = maybeInvokeDelegate(delegate, context); if (delegateResult) { if (delegateResult === ContinueSentinel) continue; return delegateResult; } } if ("next" === context.method) context.sent = context._sent = context.arg;else if ("throw" === context.method) { if ("suspendedStart" === state) throw state = "completed", context.arg; context.dispatchException(context.arg); } else "return" === context.method && context.abrupt("return", context.arg); state = "executing"; var record = tryCatch(innerFn, self, context); if ("normal" === record.type) { if (state = context.done ? "completed" : "suspendedYield", record.arg === ContinueSentinel) continue; return { value: record.arg, done: context.done }; } "throw" === record.type && (state = "completed", context.method = "throw", context.arg = record.arg); } }; } function maybeInvokeDelegate(delegate, context) { var methodName = context.method, method = delegate.iterator[methodName]; if (undefined === method) return context.delegate = null, "throw" === methodName && delegate.iterator["return"] && (context.method = "return", context.arg = undefined, maybeInvokeDelegate(delegate, context), "throw" === context.method) || "return" !== methodName && (context.method = "throw", context.arg = new TypeError("The iterator does not provide a '" + methodName + "' method")), ContinueSentinel; var record = tryCatch(method, delegate.iterator, context.arg); if ("throw" === record.type) return context.method = "throw", context.arg = record.arg, context.delegate = null, ContinueSentinel; var info = record.arg; return info ? info.done ? (context[delegate.resultName] = info.value, context.next = delegate.nextLoc, "return" !== context.method && (context.method = "next", context.arg = undefined), context.delegate = null, ContinueSentinel) : info : (context.method = "throw", context.arg = new TypeError("iterator result is not an object"), context.delegate = null, ContinueSentinel); } function pushTryEntry(locs) { var entry = { tryLoc: locs[0] }; 1 in locs && (entry.catchLoc = locs[1]), 2 in locs && (entry.finallyLoc = locs[2], entry.afterLoc = locs[3]), this.tryEntries.push(entry); } function resetTryEntry(entry) { var record = entry.completion || {}; record.type = "normal", delete record.arg, entry.completion = record; } function Context(tryLocsList) { this.tryEntries = [{ tryLoc: "root" }], tryLocsList.forEach(pushTryEntry, this), this.reset(!0); } function values(iterable) { if (iterable) { var iteratorMethod = iterable[iteratorSymbol]; if (iteratorMethod) return iteratorMethod.call(iterable); if ("function" == typeof iterable.next) return iterable; if (!isNaN(iterable.length)) { var i = -1, next = function next() { for (; ++i < iterable.length;) if (hasOwn.call(iterable, i)) return next.value = iterable[i], next.done = !1, next; return next.value = undefined, next.done = !0, next; }; return next.next = next; } } return { next: doneResult }; } function doneResult() { return { value: undefined, done: !0 }; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, defineProperty(Gp, "constructor", { value: GeneratorFunctionPrototype, configurable: !0 }), defineProperty(GeneratorFunctionPrototype, "constructor", { value: GeneratorFunction, configurable: !0 }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, toStringTagSymbol, "GeneratorFunction"), exports.isGeneratorFunction = function (genFun) { var ctor = "function" == typeof genFun && genFun.constructor; return !!ctor && (ctor === GeneratorFunction || "GeneratorFunction" === (ctor.displayName || ctor.name)); }, exports.mark = function (genFun) { return Object.setPrototypeOf ? Object.setPrototypeOf(genFun, GeneratorFunctionPrototype) : (genFun.__proto__ = GeneratorFunctionPrototype, define(genFun, toStringTagSymbol, "GeneratorFunction")), genFun.prototype = Object.create(Gp), genFun; }, exports.awrap = function (arg) { return { __await: arg }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, asyncIteratorSymbol, function () { return this; }), exports.AsyncIterator = AsyncIterator, exports.async = function (innerFn, outerFn, self, tryLocsList, PromiseImpl) { void 0 === PromiseImpl && (PromiseImpl = Promise); var iter = new AsyncIterator(wrap(innerFn, outerFn, self, tryLocsList), PromiseImpl); return exports.isGeneratorFunction(outerFn) ? iter : iter.next().then(function (result) { return result.done ? result.value : iter.next(); }); }, defineIteratorMethods(Gp), define(Gp, toStringTagSymbol, "Generator"), define(Gp, iteratorSymbol, function () { return this; }), define(Gp, "toString", function () { return "[object Generator]"; }), exports.keys = function (val) { var object = Object(val), keys = []; for (var key in object) keys.push(key); return keys.reverse(), function next() { for (; keys.length;) { var key = keys.pop(); if (key in object) return next.value = key, next.done = !1, next; } return next.done = !0, next; }; }, exports.values = values, Context.prototype = { constructor: Context, reset: function reset(skipTempReset) { if (this.prev = 0, this.next = 0, this.sent = this._sent = undefined, this.done = !1, this.delegate = null, this.method = "next", this.arg = undefined, this.tryEntries.forEach(resetTryEntry), !skipTempReset) for (var name in this) "t" === name.charAt(0) && hasOwn.call(this, name) && !isNaN(+name.slice(1)) && (this[name] = undefined); }, stop: function stop() { this.done = !0; var rootRecord = this.tryEntries[0].completion; if ("throw" === rootRecord.type) throw rootRecord.arg; return this.rval; }, dispatchException: function dispatchException(exception) { if (this.done) throw exception; var context = this; function handle(loc, caught) { return record.type = "throw", record.arg = exception, context.next = loc, caught && (context.method = "next", context.arg = undefined), !!caught; } for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i], record = entry.completion; if ("root" === entry.tryLoc) return handle("end"); if (entry.tryLoc <= this.prev) { var hasCatch = hasOwn.call(entry, "catchLoc"), hasFinally = hasOwn.call(entry, "finallyLoc"); if (hasCatch && hasFinally) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } else if (hasCatch) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); } else { if (!hasFinally) throw new Error("try statement without catch or finally"); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } } } }, abrupt: function abrupt(type, arg) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc <= this.prev && hasOwn.call(entry, "finallyLoc") && this.prev < entry.finallyLoc) { var finallyEntry = entry; break; } } finallyEntry && ("break" === type || "continue" === type) && finallyEntry.tryLoc <= arg && arg <= finallyEntry.finallyLoc && (finallyEntry = null); var record = finallyEntry ? finallyEntry.completion : {}; return record.type = type, record.arg = arg, finallyEntry ? (this.method = "next", this.next = finallyEntry.finallyLoc, ContinueSentinel) : this.complete(record); }, complete: function complete(record, afterLoc) { if ("throw" === record.type) throw record.arg; return "break" === record.type || "continue" === record.type ? this.next = record.arg : "return" === record.type ? (this.rval = this.arg = record.arg, this.method = "return", this.next = "end") : "normal" === record.type && afterLoc && (this.next = afterLoc), ContinueSentinel; }, finish: function finish(finallyLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.finallyLoc === finallyLoc) return this.complete(entry.completion, entry.afterLoc), resetTryEntry(entry), ContinueSentinel; } }, "catch": function _catch(tryLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc === tryLoc) { var record = entry.completion; if ("throw" === record.type) { var thrown = record.arg; resetTryEntry(entry); } return thrown; } } throw new Error("illegal catch attempt"); }, delegateYield: function delegateYield(iterable, resultName, nextLoc) { return this.delegate = { iterator: values(iterable), resultName: resultName, nextLoc: nextLoc }, "next" === this.method && (this.arg = undefined), ContinueSentinel; } }, exports; }
	var BuilderArchiveApp = {
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
	      updateVersion: false,
	      isPreviewing: false,
	      previewResult: null,
	      includedExpanded: false,
	      excludedExpanded: true,
	      isBuilding: false,
	      buildResult: null,
	      buildErrors: [],
	      fileListExpanded: false
	    };
	  },
	  computed: {
	    canPreview: function canPreview() {
	      return this.selectedModuleId && !this.isPreviewing && !this.isBuilding;
	    },
	    canBuild: function canBuild() {
	      return this.previewResult && !this.isBuilding;
	    }
	  },
	  mounted: function mounted() {
	    this.loadModules();
	  },
	  watch: {
	    selectedModuleId: function selectedModuleId(newVal) {
	      this.previewResult = null;
	      this.buildResult = null;
	      this.buildErrors = [];
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
	      return babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee() {
	        var data;
	        return _regeneratorRuntime().wrap(function _callee$(_context) {
	          while (1) switch (_context.prev = _context.next) {
	            case 0:
	              data = _arguments.length > 1 && _arguments[1] !== undefined ? _arguments[1] : {};
	              return _context.abrupt("return", BX.ajax.runComponentAction(COMPONENT_NAME, action, {
	                mode: 'class',
	                data: data
	              }));
	            case 2:
	            case "end":
	              return _context.stop();
	          }
	        }, _callee);
	      }))();
	    },
	    loadModules: function loadModules() {
	      var _this = this;
	      return babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee2() {
	        var response;
	        return _regeneratorRuntime().wrap(function _callee2$(_context2) {
	          while (1) switch (_context2.prev = _context2.next) {
	            case 0:
	              _context2.prev = 0;
	              _context2.next = 3;
	              return _this.runAction('getModules');
	            case 3:
	              response = _context2.sent;
	              _this.modules = response.data.modules || [];
	              _context2.next = 10;
	              break;
	            case 7:
	              _context2.prev = 7;
	              _context2.t0 = _context2["catch"](0);
	              console.error('Failed to load modules', _context2.t0);
	            case 10:
	              _context2.prev = 10;
	              if (_this.selectedModuleId) {
	                _this.loadModuleInfo(_this.selectedModuleId);
	              }
	              return _context2.finish(10);
	            case 13:
	            case "end":
	              return _context2.stop();
	          }
	        }, _callee2, null, [[0, 7, 10, 13]]);
	      }))();
	    },
	    loadModuleInfo: function loadModuleInfo(moduleId) {
	      var _this2 = this;
	      return babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee3() {
	        var response, info;
	        return _regeneratorRuntime().wrap(function _callee3$(_context3) {
	          while (1) switch (_context3.prev = _context3.next) {
	            case 0:
	              _context3.prev = 0;
	              _this2.isLoadingModuleInfo = true;
	              _this2.moduleInfo = null;
	              _this2.previewResult = null;
	              _this2.buildResult = null;
	              _this2.buildErrors = [];
	              _context3.next = 8;
	              return _this2.runAction('getModuleInfo', {
	                moduleId: moduleId
	              });
	            case 8:
	              response = _context3.sent;
	              info = response.data;
	              _this2.moduleInfo = info;
	              _this2.version = info.nextVersion || '';
	              _this2.updateVersion = false;
	              _context3.next = 18;
	              break;
	            case 15:
	              _context3.prev = 15;
	              _context3.t0 = _context3["catch"](0);
	              console.error('Failed to load module info', _context3.t0);
	            case 18:
	              _context3.prev = 18;
	              _this2.isLoadingModuleInfo = false;
	              return _context3.finish(18);
	            case 21:
	            case "end":
	              return _context3.stop();
	          }
	        }, _callee3, null, [[0, 15, 18, 21]]);
	      }))();
	    },
	    previewArchive: function previewArchive() {
	      var _this3 = this;
	      return babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee4() {
	        var response;
	        return _regeneratorRuntime().wrap(function _callee4$(_context4) {
	          while (1) switch (_context4.prev = _context4.next) {
	            case 0:
	              if (_this3.canPreview) {
	                _context4.next = 2;
	                break;
	              }
	              return _context4.abrupt("return");
	            case 2:
	              _this3.isPreviewing = true;
	              _this3.buildErrors = [];
	              _this3.excludedExpanded = true;
	              _this3.includedExpanded = false;
	              _context4.prev = 6;
	              _context4.next = 9;
	              return _this3.runAction('previewArchive', {
	                moduleId: _this3.selectedModuleId
	              });
	            case 9:
	              response = _context4.sent;
	              _this3.previewResult = response.data;
	              _context4.next = 16;
	              break;
	            case 13:
	              _context4.prev = 13;
	              _context4.t0 = _context4["catch"](6);
	              if (_context4.t0.errors && _context4.t0.errors.length) {
	                _this3.buildErrors = _context4.t0.errors.map(function (e) {
	                  return e.message;
	                });
	              } else {
	                _this3.buildErrors = ['Unknown error'];
	              }
	            case 16:
	              _context4.prev = 16;
	              _this3.isPreviewing = false;
	              return _context4.finish(16);
	            case 19:
	            case "end":
	              return _context4.stop();
	          }
	        }, _callee4, null, [[6, 13, 16, 19]]);
	      }))();
	    },
	    cancelPreview: function cancelPreview() {
	      this.previewResult = null;
	      this.buildResult = null;
	    },
	    buildArchive: function buildArchive() {
	      var _this4 = this;
	      return babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee5() {
	        var response;
	        return _regeneratorRuntime().wrap(function _callee5$(_context5) {
	          while (1) switch (_context5.prev = _context5.next) {
	            case 0:
	              if (_this4.canBuild) {
	                _context5.next = 2;
	                break;
	              }
	              return _context5.abrupt("return");
	            case 2:
	              _this4.isBuilding = true;
	              _this4.buildResult = null;
	              _this4.buildErrors = [];
	              _this4.fileListExpanded = false;
	              _context5.prev = 6;
	              _context5.next = 9;
	              return _this4.runAction('buildArchive', {
	                moduleId: _this4.selectedModuleId,
	                version: _this4.updateVersion ? _this4.version : ''
	              });
	            case 9:
	              response = _context5.sent;
	              _this4.buildResult = response.data;
	              _context5.next = 16;
	              break;
	            case 13:
	              _context5.prev = 13;
	              _context5.t0 = _context5["catch"](6);
	              if (_context5.t0.errors && _context5.t0.errors.length) {
	                _this4.buildErrors = _context5.t0.errors.map(function (e) {
	                  return e.message;
	                });
	              } else {
	                _this4.buildErrors = ['Unknown error'];
	              }
	            case 16:
	              _context5.prev = 16;
	              _this4.isBuilding = false;
	              return _context5.finish(16);
	            case 19:
	            case "end":
	              return _context5.stop();
	          }
	        }, _callee5, null, [[6, 13, 16, 19]]);
	      }))();
	    },
	    deleteTemp: function deleteTemp() {
	      var _this5 = this;
	      return babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee6() {
	        return _regeneratorRuntime().wrap(function _callee6$(_context6) {
	          while (1) switch (_context6.prev = _context6.next) {
	            case 0:
	              if (confirm(_this5.loc('MPBUILDER_ARCHIVE_DELETE_CONFIRM'))) {
	                _context6.next = 2;
	                break;
	              }
	              return _context6.abrupt("return");
	            case 2:
	              _context6.prev = 2;
	              _context6.next = 5;
	              return _this5.runAction('deleteTemp', {
	                moduleId: _this5.selectedModuleId
	              });
	            case 5:
	              _this5.buildResult = null;
	              _context6.next = 11;
	              break;
	            case 8:
	              _context6.prev = 8;
	              _context6.t0 = _context6["catch"](2);
	              console.error('Failed to delete temp', _context6.t0);
	            case 11:
	            case "end":
	              return _context6.stop();
	          }
	        }, _callee6, null, [[2, 8]]);
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
	    console.error('BuilderArchive: failed to parse params', e);
	    return;
	  }
	  var app = BX.Vue3.BitrixVue.createApp(BuilderArchiveApp, {
	    params: params
	  });
	  app.mount(container);
	});

}((this.window = this.window || {})));
//# sourceMappingURL=script.js.map
