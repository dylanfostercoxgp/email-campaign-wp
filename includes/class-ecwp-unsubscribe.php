<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Handles the front-end unsubscribe page.
 * URL: yoursite.com/ecwp-unsubscribe/?email=...&token=...
 */
class ECWP_Unsubscribe {

	public function init() {
		add_action( 'init',              [ $this, 'add_rewrite_rules' ] );
		add_filter( 'query_vars',        [ $this, 'add_query_vars' ] );
		add_action( 'template_redirect', [ $this, 'handle_request' ] );
	}

	public function add_rewrite_rules() {
		add_rewrite_rule( '^ecwp-unsubscribe/?$', 'index.php?ecwp_unsubscribe=1', 'top' );
	}

	public function add_query_vars( $vars ) {
		$vars[] = 'ecwp_unsubscribe';
		return $vars;
	}

	public function handle_request() {
		if ( ! get_query_var( 'ecwp_unsubscribe' ) ) {
			return;
		}

		$email          = sanitize_email( urldecode( $_GET['email'] ?? '' ) );
		$token          = sanitize_text_field( $_GET['token'] ?? '' );
		$unsubscribed   = false;
		$error          = '';
		$site_name      = get_bloginfo( 'name' );
		$home_url       = home_url();

		if ( $email && $token ) {
			$scheduler      = new ECWP_Scheduler();
			$expected_token = $scheduler->unsubscribe_token( $email );

			if ( hash_equals( $expected_token, $token ) ) {
				$subscribers = new ECWP_Subscribers();
				$result      = $subscribers->unsubscribe( $email );
				$unsubscribed = ( $result !== false );
			} else {
				$error = 'This unsubscribe link is invalid or has expired.';
			}
		} else {
			$error = 'Missing required parameters.';
		}

		// Render a simple, clean page — no WP theme dependency.
		status_header( 200 );
		header( 'Content-Type: text/html; charset=UTF-8' );
		?>
		<!DOCTYPE html>
		<html lang="en">
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<title><?php echo $unsubscribed ? 'Unsubscribed' : 'Error'; ?> | <?php echo esc_html( $site_name ); ?></title>
			<style>
				*, *::before, *::after { box-sizing: border-box; }
				body {
					margin: 0;
					font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
					background: #f0f2f5;
					display: flex;
					align-items: center;
					justify-content: center;
					min-height: 100vh;
					color: #333;
				}
				.card {
					background: #fff;
					border-radius: 12px;
					box-shadow: 0 4px 24px rgba(0,0,0,.08);
					padding: 48px 40px;
					max-width: 480px;
					width: 90%;
					text-align: center;
				}
				.icon { font-size: 52px; margin-bottom: 16px; }
				h1 { margin: 0 0 12px; font-size: 1.6rem; }
				p  { color: #666; line-height: 1.6; margin: 0 0 24px; }
				.links a { color: #3b82f6; text-decoration: none; margin: 0 8px; font-size: .9rem; }
				.links a:hover { text-decoration: underline; }
				.brand { margin-top: 32px; font-size: .8rem; color: #bbb; }
				.brand a { color: #bbb; text-decoration: none; }
			</style>
		</head>
		<body>
			<div class="card">
				<?php if ( $unsubscribed ) : ?>
					<div class="icon">✅</div>
					<h1>You've been unsubscribed</h1>
					<p>
						<strong><?php echo esc_html( $email ); ?></strong> has been removed from our mailing list.
						You won't receive any further emails from us.
					</p>
				<?php else : ?>
					<div class="icon">⚠️</div>
					<h1>Something went wrong</h1>
					<p><?php echo esc_html( $error ); ?></p>
				<?php endif; ?>

				<div class="links">
					<a href="<?php echo esc_url( $home_url ); ?>">← Back to website</a>
				</div>
				<div class="brand">
					Powered by <a href="https://ideaboss.io" target="_blank" rel="noopener">ideaBoss</a>
				</div>
			</div>
		</body>
		</html>
		<?php
		exit;
	}
}
