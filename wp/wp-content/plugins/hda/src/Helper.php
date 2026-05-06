<?php
/**
 * All utility helpers used across the plugin.
 *
 * @author HD
 */

namespace HDAddons;

\defined( 'ABSPATH' ) || exit;

final class Helper {
	use Traits\Str;
	use Traits\Svg;
	use Traits\Minify;
	use Traits\Options;
	use Traits\Network;
	use Traits\Plugin;
	use Traits\Vite;
	use Traits\Cache;
	use Traits\Settings;
	use Traits\Misc;
	use Traits\IconRenderer;
	use Traits\Filesystem;
}
