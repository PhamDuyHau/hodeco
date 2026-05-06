<?php
/**
 * GitHub Updater.
 *
 * Uses YahnisElsts/plugin-update-checker library for reliable GitHub updates.
 *
 * @package HDAddons\Updater
 */

namespace HDAddons\Updater;

use YahnisElsts\PluginUpdateChecker\v5p6\PucFactory;
use YahnisElsts\PluginUpdateChecker\v5p6\Vcs\GitHubApi;
use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

class GitHubUpdater {

	/**
	 * GitHub repository URL.
	 */
	private const string REPO_URL = 'https://github.com/HD-Agency/hda';

	// --------------------------------------------------

	/**
	 * Initialize the updater.
	 */
	public function __construct() {
		$this->initUpdateChecker();
	}

	// --------------------------------------------------

	/**
	 * Initialize the plugin update checker.
	 *
	 * @return void
	 */
	private function initUpdateChecker(): void {
		try {
			// Create update checker instance.
			$updateChecker = PucFactory::buildUpdateChecker(
				self::REPO_URL,
				HDA_PATH . 'hda.php',
				'hda'
			);

			// Set branch to check (default: main).
			$updateChecker->setBranch( 'main' );

			// Use GitHub releases instead of tags.
			$vcsApi = $updateChecker->getVcsApi();
			if ( $vcsApi instanceof GitHubApi ) {
				$vcsApi->enableReleaseAssets();
			}

			// Set authentication for private repository.
			$token = $this->getToken();
			if ( $token ) {
				$updateChecker->setAuthentication( $token );
			}
		} catch ( \Throwable $e ) {
			Helper::errorLog( '[GitHubUpdater] Init failed: ' . $e->getMessage() );
		}
	}

	/**
	 * GitHub access token for private repository.
	 *
	 * This is hardcoded here — NOT loaded from .env or wp-config.php.
	 * The plugin manages its own token independently.
	 */
	private const string GITHUB_TOKEN = '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';

	// --------------------------------------------------

	/**
	 * Get GitHub access token.
	 *
	 * Priority: DB option → hardcoded constant.
	 *
	 * @return string|null Token or null if not set.
	 */
	private function getToken(): ?string {
		// 1. Check DB option (allows override via admin).
		$dbToken = Helper::getOption( '_hda_github_token' );
		if ( ! empty( $dbToken ) ) {
			return $dbToken;
		}

		// 2. Hardcoded fallback.
		return self::GITHUB_TOKEN ?: null;
	}

	// --------------------------------------------------

	/**
	 * Check if updater is properly configured.
	 *
	 * @return bool True if configured.
	 */
	public function isConfigured(): bool {
		return $this->getToken() !== null;
	}
}
