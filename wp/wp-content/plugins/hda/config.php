<?php
/**
 * Module configuration registry.
 *
 * Each key is a module slug (used as checkbox ID in GlobalSetting).
 * Structure: slug => ['title' => '...', 'description' => '...']
 *
 * To add a new module: add an entry here.
 * To remove a module: delete the entry → orphan cleanup will handle DB options.
 *
 * @package HDAddons
 */

return [
	'global_setting'    => [
		'title'       => 'Global Setting',
		'description' => 'Toggle plugin modules on or off from a single dashboard.',
	],

	'aspect_ratio'      => [
		'title'       => 'Aspect Ratio',
		'description' => 'Set fixed aspect ratios for featured images and media embeds.',
	],

	'editor'            => [
		'title'       => 'Editor',
		'description' => 'Switch between Classic and Block editors, and configure editing defaults.',
	],

	'seo'               => [
		'title'       => 'SEO',
		'description' => 'Meta tags, Open Graph, Twitter Card, JSON-LD, and robots directives for search visibility.',
	],

	'security'          => [
		'title'       => 'Security',
		'description' => 'Hardening, WAF Firewall (SQLi/XSS/RCE/LFI), traffic logging, rate limiting, and threat intelligence.',
	],

	'login_security'    => [
		'title'       => 'Login Security',
		'description' => 'Custom login URL, IP restrictions, 2FA (OTP / TOTP / Magic Link), and brute-force protection.',
	],

	'file'              => [
		'title'       => 'File',
		'description' => 'Upload limits, SVG support, core integrity verification, and malware signature scanning.',
	],

	'optimize'          => [
		'title'       => 'Optimize',
		'description' => 'Heartbeat, embeds, wp_head cleanup, database optimization, and cache plugin integration.',
	],

	'social_link'       => [
		'title'       => 'Social Link',
		'description' => 'Configure social media links to connect your site with various platforms.',
	],

	'contact_link'      => [
		'title'       => 'Contact Link',
		'description' => 'Floating contact buttons with popup, custom icons, and click-to-action links.',
	],

	'cookie_consent'    => [
		'title'       => 'Cookie Consent',
		'description' => 'Display a GDPR-compliant cookie consent banner with customizable text and behavior.',
	],

	'custom_sorting'    => [
		'title'       => 'Custom Sorting',
		'description' => 'Drag-and-drop ordering for posts, pages, and custom taxonomies.',
	],

	'scheduled_content' => [
		'title'       => 'Scheduled Content',
		'description' => 'Auto-publish and auto-expire content based on a start/end date schedule.',
	],

	'post_type_archive' => [
		'title'       => 'Post Type Archive',
		'description' => 'Assign a static page as the archive for any custom post type — like the built-in Posts page.',
	],

	'recaptcha'         => [
		'title'       => 'CAPTCHA',
		'description' => 'Google reCAPTCHA v2 and Cloudflare Turnstile integration with auto-protection for login, registration, password reset, and comment forms.',
	],

	'redirect'          => [
		'title'       => 'Redirect',
		'description' => '301/302 URL redirects to preserve SEO rankings and fix broken links.',
	],

	'monitor_404'       => [
		'title'       => '404 Monitor',
		'description' => 'Log broken links with URL, referrer, and hit count. Auto-block IPs with configurable 404 flood threshold.',
	],

	'cron_manager'      => [
		'title'       => 'Cron Manager',
		'description' => 'View, run, and delete WP-Cron events. Detect overdue jobs and inspect registered schedules.',
	],

	'maintenance'       => [
		'title'       => 'Maintenance',
		'description' => 'Enable maintenance mode to temporarily restrict site access during updates or development.',
	],

	'custom_code'       => [
		'title'       => 'Custom Code',
		'description' => 'Inject custom CSS to override styles and tracking scripts / JS into head or body.',
	],
];
