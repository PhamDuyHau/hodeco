<?php
/**
 * Plugin Integration Interface
 *
 * Defines contract for plugin integrations.
 * All plugin integration classes should implement this interface
 * to provide consistent plugin activation checking.
 *
 * @package HD\Plugins
 * @author  HD
 */

namespace HD\Plugins;

defined( 'ABSPATH' ) || die;

interface PluginIntegration {
	/**
	 * Check if the plugin is active and available.
	 *
	 * @return bool True if plugin is active, false otherwise.
	 */
	public static function isActive(): bool;
	
	/**
	 * Get singleton instance.
	 *
	 * @return static
	 */
	public static function get_instance(): static;
}
