<?php
/**
 * GitHub auto-updater for Email Campaign WP.
 *
 * Hooks into WordPress's native plugin update pipeline and checks the
 * latest GitHub release.  When a newer tag is available the standard
 * "Update Available" banner appears in Plugins → Installed Plugins, and
 * admins can install it with a single click — no manual zip upload needed.
 *
 * How it works
 * ─────────────
 * 1. On each WordPress update-check cycle this class calls the GitHub
 *    Releases API (result cached for 12 h) and compares the remote tag
 *    with ECWP_VERSION.
 * 2. When the remote version is higher it injects the update into the
 *    `update_plugins` site-transient that WordPress uses for its dashboard
 *    notices and one-click updater.
 * 3. WordPress downloads the GitHub zip, extracts it, and renames the
 *    folder to `email-campaign-wp/` (GitHub source zips extract with a
 *    hash-suffixed folder name; the `upgrader_source_selection` filter
 *    handles the rename transparently).
 *
 * Release workflow (developer side)
 * ───────────────────────────────────
 * Just push a new git tag (e.g. v1.0.6) and create a GitHub Release from
 * it — that's all.  WordPress sites will detect the new version on their
 * next update check (≤ 12 h) and offer a one-click update.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ECWP_Updater {

	/** GitHub repo in "owner/repo" format. */
	const REPO = 'dylanfostercoxgp/email-campaign-wp';

	/** Plugin folder / slug. */
	const SLUG = 'email-campaign-wp';

	/** WordPress plugin identifier (folder/main-file). */
	const PLUGIN_FILE = 'email-campaign-wp/email-campaign-wp.php';

	/** Transient key for the cached GitHub API response. */
	const TRANSIENT = 'ecwp_github_release';

	/** Cache lifetime in seconds (12 hours). */
	const CACHE_TTL = 43200;

	/* ------------------------------------------------------------------ */
	/*  Boot                                                                */
	/* ------------------------------------------------------------------ */

	public function init() {
		add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_for_update' ] );
		add_filter( 'plugins_api',                           [ $this, 'plugin_info' ], 20, 3 );
		add_filter( 'upgrader_source_selection',             [ $this, 'fix_source_dir' ], 10, 4 );
		add_action( 'upgrader_process_complete',             [ $this, 'clear_cache' ],   10, 2 );
	}

	/* ------------------------------------------------------------------ */
	/*  GitHub API                                                          */
	/* ------------------------------------------------------------------ */

	/**
	 * Fetch the latest release object from GitHub (cached).
	 *
	 * @return object|false Release object on success, false on failure.
	 */
	private function get_release() {
		$cached = get_transient( self::TRANSIENT );
		if ( $cached !== false ) {
			return $cached;
		}

		$url      = 'https://api.github.com/repos/' . self::REPO . '/releases/latest';
		$response = wp_remote_get( $url, [
			'headers' => [
				'Accept'     => 'application/vnd.github+json',
				'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' ),
			],
			'timeout' => 10,
		] );

		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return false;
		}

		$release = json_decode( wp_remote_retrieve_body( $response ) );
		if ( empty( $release->tag_name ) ) {
			return false;
		}

		set_transient( self::TRANSIENT, $release, self::CACHE_TTL );
		return $release;
	}

	/**
	 * Strip a leading "v" from a git tag to get a plain version string.
	 *
	 * @param  string $tag  e.g. "v1.0.6"
	 * @return string       e.g. "1.0.6"
	 */
	private function tag_to_version( $tag ) {
		return ltrim( $tag, 'vV' );
	}

	/* ------------------------------------------------------------------ */
	/*  WordPress update pipeline hooks                                     */
	/* ------------------------------------------------------------------ */

	/**
	 * Inject update info into the site transient that powers WP's update UI.
	 *
	 * @param  object $transient  The update_plugins transient.
	 * @return object
	 */
	public function check_for_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$release = $this->get_release();
		if ( ! $release ) {
			return $transient;
		}

		$remote_version = $this->tag_to_version( $release->tag_name );

		if ( version_compare( ECWP_VERSION, $remote_version, '<' ) ) {
			$transient->response[ self::PLUGIN_FILE ] = (object) [
				'id'          => 'github.com/' . self::REPO,
				'slug'        => self::SLUG,
				'plugin'      => self::PLUGIN_FILE,
				'new_version' => $remote_version,
				'url'         => 'https://github.com/' . self::REPO,
				'package'     => $release->zipball_url,
				'icons'       => [],
				'banners'     => [],
				'requires'    => '6.0',
				'tested'      => '6.9',
			];
		} else {
			// Make sure a stale "update available" entry is cleared.
			unset( $transient->response[ self::PLUGIN_FILE ] );
		}

		return $transient;
	}

	/**
	 * Populate the "View version details" modal in the WP update screen.
	 *
	 * @param  false|object $result  Existing result (false if none).
	 * @param  string       $action  API action requested.
	 * @param  object       $args    Request args (includes slug).
	 * @return false|object
	 */
	public function plugin_info( $result, $action, $args ) {
		if ( $action !== 'plugin_information' ) {
			return $result;
		}
		if ( ( $args->slug ?? '' ) !== self::SLUG ) {
			return $result;
		}

		$release = $this->get_release();
		if ( ! $release ) {
			return $result;
		}

		$remote_version = $this->tag_to_version( $release->tag_name );
		$changelog      = ! empty( $release->body )
			? '<pre>' . esc_html( $release->body ) . '</pre>'
			: '<p>See <a href="https://github.com/' . self::REPO . '/releases" target="_blank">GitHub Releases</a> for changelog.</p>';

		return (object) [
			'name'           => 'Email Campaign WP',
			'slug'           => self::SLUG,
			'version'        => $remote_version,
			'author'         => '<a href="https://ideaboss.io">ideaBoss</a>',
			'author_profile' => 'https://ideaboss.io',
			'homepage'       => 'https://github.com/' . self::REPO,
			'requires'       => '6.0',
			'tested'         => '6.9',
			'last_updated'   => $release->published_at ?? '',
			'download_link'  => $release->zipball_url,
			'sections'       => [
				'description' => '<p>A powerful email campaign manager with Mailgun integration, subscriber management, tagging, HTML editor, batch scheduling, and full analytics.</p>',
				'changelog'   => $changelog,
			],
		];
	}

	/**
	 * Rename the extracted GitHub zip folder to the correct plugin slug.
	 *
	 * GitHub source zips extract to a folder named like:
	 *   dylanfostercoxgp-email-campaign-wp-a1b2c3d/
	 *
	 * WordPress expects:
	 *   email-campaign-wp/
	 *
	 * @param  string $source        Path to the extracted source directory.
	 * @param  string $remote_source Temp directory holding the archive.
	 * @param  object $upgrader      WP_Upgrader instance.
	 * @param  array  $hook_extra    Extra context (includes 'plugin' key).
	 * @return string  Corrected source path.
	 */
	public function fix_source_dir( $source, $remote_source, $upgrader, $hook_extra ) {
		// Only act when our plugin is being updated.
		if ( ( $hook_extra['plugin'] ?? '' ) !== self::PLUGIN_FILE ) {
			return $source;
		}

		global $wp_filesystem;

		$correct_dir = trailingslashit( dirname( untrailingslashit( $source ) ) ) . self::SLUG . '/';

		if ( untrailingslashit( $source ) === untrailingslashit( $correct_dir ) ) {
			return $source; // Already correct.
		}

		if ( $wp_filesystem->move( $source, $correct_dir, true ) ) {
			return $correct_dir;
		}

		// Return original if move failed (WP will surface an error).
		return $source;
	}

	/**
	 * Clear cached release data after our plugin is updated so the next
	 * check always fetches fresh data.
	 *
	 * @param  WP_Upgrader $upgrader  Upgrader instance.
	 * @param  array       $options   Upgrader options.
	 */
	public function clear_cache( $upgrader, $options ) {
		if (
			isset( $options['action'], $options['type'] ) &&
			$options['action'] === 'update' &&
			$options['type'] === 'plugin'
		) {
			delete_transient( self::TRANSIENT );
		}
	}
}
