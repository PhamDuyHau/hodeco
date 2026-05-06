/**
 * HDA Settings Page Scripts
 *
 * Entry point — plugin settings pages.
 */

import select2 from 'select2';
import 'select2/dist/css/select2.min.css';
import './utils/jquery-plugins.js';

import { initCodeMirror } from './modules/settings/codemirror.js';
import { initSelect2 } from './modules/settings/select2-init.js';
import { initOtpSettings } from './modules/settings/otp-settings.js';
import { initRedirectRepeater } from './modules/settings/redirect-repeater.js';
import { initSettingsForm } from './modules/settings/settings-form.js';
import { initFilterTabs } from './modules/settings/filter-tabs.js';
import { initWaf } from './modules/settings/waf.js';
import { initDbOptimizer } from './modules/settings/db-optimizer.js';
import { initCronManager } from './modules/settings/cron-manager.js';
import { initFileIntegrity } from './modules/settings/file-integrity.js';
import { initCookieConsentSettings } from './modules/settings/cookie-consent-settings.js';
import { initMaintenanceSettings } from './modules/settings/maintenance-settings.js';

const $ = window.jQuery;
select2($);

document.addEventListener('DOMContentLoaded', () => {
	initCodeMirror();
	initSelect2($);
	initOtpSettings();
	initRedirectRepeater();
	initWaf();
	initDbOptimizer();
	initCronManager();
	initFileIntegrity();
	initCookieConsentSettings();
	initMaintenanceSettings();
});

jQuery(function ($) {
	$('.hda-color-field').wpColorPicker();

	initSettingsForm($);
	initFilterTabs($);
});
