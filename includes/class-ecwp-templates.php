<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class ECWP_Templates {

	private $table;

	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'ecwp_templates';
	}

	// ── System templates ───────────────────────────────────────────────────

	public static function get_system_templates() {
		return [
			[
				'id'       => 'sys_newsletter',
				'name'     => 'Modern Newsletter',
				'category' => 'Newsletter',
				'preview'  => '#2563eb',
				'html'     => self::tpl_newsletter(),
			],
			[
				'id'       => 'sys_promo',
				'name'     => 'Flash Sale',
				'category' => 'Promotional',
				'preview'  => '#dc2626',
				'html'     => self::tpl_promo(),
			],
			[
				'id'       => 'sys_welcome',
				'name'     => 'Welcome Email',
				'category' => 'Onboarding',
				'preview'  => '#16a34a',
				'html'     => self::tpl_welcome(),
			],
			[
				'id'       => 'sys_announce',
				'name'     => 'Announcement',
				'category' => 'News',
				'preview'  => '#7c3aed',
				'html'     => self::tpl_announcement(),
			],
			[
				'id'       => 'sys_event',
				'name'     => 'Event Invitation',
				'category' => 'Events',
				'preview'  => '#d97706',
				'html'     => self::tpl_event(),
			],
		];
	}

	public static function get_system_template_by_id( $id ) {
		foreach ( self::get_system_templates() as $tpl ) {
			if ( $tpl['id'] === $id ) return $tpl;
		}
		return null;
	}

	// ── User-saved templates ───────────────────────────────────────────────

	public function get_all() {
		global $wpdb;
		return $wpdb->get_results( "SELECT * FROM {$this->table} ORDER BY created_at DESC" );
	}

	public function get_by_id( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table} WHERE id = %d", $id ) );
	}

	public function save( $name, $subject, $html ) {
		global $wpdb;
		$wpdb->insert( $this->table, [
			'name'    => sanitize_text_field( $name ),
			'subject' => sanitize_text_field( $subject ),
			'html'    => $html,
		] );
		return $wpdb->insert_id;
	}

	public function delete( $id ) {
		global $wpdb;
		return $wpdb->delete( $this->table, [ 'id' => $id ], [ '%d' ] );
	}

	// ── Template HTML definitions ──────────────────────────────────────────

	private static function tpl_newsletter() {
		return '<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Newsletter</title></head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:32px 0;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);">

      <!-- Header -->
      <tr><td style="background:#2563eb;padding:32px 40px;text-align:center;">
        <h1 style="color:#ffffff;margin:0;font-size:26px;font-weight:700;letter-spacing:-0.5px;">Your Newsletter</h1>
        <p style="color:rgba(255,255,255,.8);margin:8px 0 0;font-size:14px;">Month 2025</p>
      </td></tr>

      <!-- Greeting -->
      <tr><td style="padding:36px 40px 0;">
        <p style="font-size:16px;color:#374151;margin:0 0 8px;">Hi {{first_name}},</p>
        <p style="font-size:15px;color:#6b7280;line-height:1.7;margin:0;">Welcome to this month\'s edition. Here\'s everything you need to know.</p>
      </td></tr>

      <!-- Divider -->
      <tr><td style="padding:24px 40px;"><hr style="border:none;border-top:1px solid #e5e7eb;margin:0;"></td></tr>

      <!-- Featured story -->
      <tr><td style="padding:0 40px 28px;">
        <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#2563eb;margin:0 0 8px;">Featured Story</p>
        <h2 style="font-size:22px;color:#111827;margin:0 0 12px;line-height:1.3;">Your main headline goes here</h2>
        <p style="font-size:15px;color:#6b7280;line-height:1.7;margin:0 0 20px;">This is where your main story or message goes. Write 2-3 sentences that draw the reader in and make them want to click your call to action.</p>
        <table cellpadding="0" cellspacing="0"><tr><td style="background:#2563eb;border-radius:6px;">
          <a href="#" style="display:inline-block;padding:12px 24px;color:#ffffff;text-decoration:none;font-size:14px;font-weight:600;">Read More &rarr;</a>
        </td></tr></table>
      </td></tr>

      <!-- 2-column section -->
      <tr><td style="padding:0 40px 32px;">
        <table width="100%" cellpadding="0" cellspacing="0">
          <tr>
            <td width="48%" style="background:#f9fafb;border-radius:8px;padding:20px;vertical-align:top;">
              <p style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#9ca3af;margin:0 0 8px;">Quick Update</p>
              <p style="font-size:15px;font-weight:600;color:#111827;margin:0 0 8px;">Secondary headline</p>
              <p style="font-size:14px;color:#6b7280;line-height:1.6;margin:0;">Short update or secondary story goes here. Keep it brief.</p>
            </td>
            <td width="4%"></td>
            <td width="48%" style="background:#f9fafb;border-radius:8px;padding:20px;vertical-align:top;">
              <p style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#9ca3af;margin:0 0 8px;">Tip of the Month</p>
              <p style="font-size:15px;font-weight:600;color:#111827;margin:0 0 8px;">Another headline</p>
              <p style="font-size:14px;color:#6b7280;line-height:1.6;margin:0;">Share a useful tip, resource, or insight with your audience.</p>
            </td>
          </tr>
        </table>
      </td></tr>

      <!-- Footer -->
      <tr><td style="background:#f9fafb;border-top:1px solid #e5e7eb;padding:24px 40px;text-align:center;">
        <p style="font-size:13px;color:#111827;font-weight:600;margin:0 0 4px;">Your Company Name</p>
        <p style="font-size:12px;color:#9ca3af;margin:0;">123 Main Street, City, State 00000</p>
        <p style="font-size:12px;color:#9ca3af;margin:12px 0 0;">
          <a href="{{unsubscribe_url}}" style="color:#9ca3af;text-decoration:underline;">Unsubscribe</a> &nbsp;&bull;&nbsp; Powered by <a href="https://ideaboss.io" style="color:#9ca3af;">ideaBoss</a>
        </p>
      </td></tr>

    </table>
  </td></tr>
</table>
</body></html>';
	}

	private static function tpl_promo() {
		return '<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Flash Sale</title></head>
<body style="margin:0;padding:0;background:#fef2f2;font-family:Arial,Helvetica,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#fef2f2;padding:32px 0;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);">

      <!-- Hero banner -->
      <tr><td style="background:#dc2626;padding:48px 40px;text-align:center;">
        <p style="color:rgba(255,255,255,.9);font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:2px;margin:0 0 12px;">Limited Time Offer</p>
        <h1 style="color:#ffffff;font-size:52px;font-weight:900;margin:0;line-height:1;">50% OFF</h1>
        <p style="color:rgba(255,255,255,.9);font-size:18px;margin:12px 0 0;">Everything in our store</p>
      </td></tr>

      <!-- Body -->
      <tr><td style="padding:40px;text-align:center;">
        <p style="font-size:16px;color:#374151;margin:0 0 8px;">Hey {{first_name}},</p>
        <p style="font-size:15px;color:#6b7280;line-height:1.7;margin:0 0 28px;">This is the deal you\'ve been waiting for. For the next 48 hours only, use code below for 50% off your entire order.</p>

        <!-- Coupon box -->
        <table cellpadding="0" cellspacing="0" style="margin:0 auto 28px;">
          <tr><td style="border:2px dashed #dc2626;border-radius:8px;padding:16px 32px;text-align:center;">
            <p style="font-size:12px;color:#6b7280;margin:0 0 4px;text-transform:uppercase;letter-spacing:1px;">Your coupon code</p>
            <p style="font-size:28px;font-weight:900;color:#dc2626;margin:0;letter-spacing:4px;">SAVE50</p>
          </td></tr>
        </table>

        <!-- CTA -->
        <table cellpadding="0" cellspacing="0" style="margin:0 auto 28px;">
          <tr><td style="background:#dc2626;border-radius:8px;">
            <a href="#" style="display:inline-block;padding:16px 40px;color:#ffffff;text-decoration:none;font-size:16px;font-weight:700;">Shop Now &rarr;</a>
          </td></tr>
        </table>

        <!-- Urgency -->
        <table width="100%" cellpadding="0" cellspacing="0" style="background:#fef2f2;border-radius:8px;margin-bottom:8px;">
          <tr><td style="padding:16px;text-align:center;">
            <p style="font-size:14px;color:#dc2626;font-weight:600;margin:0;">&#9203; Offer expires in 48 hours &mdash; don\'t miss out!</p>
          </td></tr>
        </table>
      </td></tr>

      <!-- Footer -->
      <tr><td style="background:#f9fafb;border-top:1px solid #e5e7eb;padding:24px 40px;text-align:center;">
        <p style="font-size:12px;color:#9ca3af;margin:0;">
          &copy; 2025 Your Company &nbsp;&bull;&nbsp;
          <a href="{{unsubscribe_url}}" style="color:#9ca3af;text-decoration:underline;">Unsubscribe</a> &nbsp;&bull;&nbsp;
          <a href="https://ideaboss.io" style="color:#9ca3af;">Powered by ideaBoss</a>
        </p>
      </td></tr>

    </table>
  </td></tr>
</table>
</body></html>';
	}

	private static function tpl_welcome() {
		return '<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Welcome</title></head>
<body style="margin:0;padding:0;background:#f0fdf4;font-family:Arial,Helvetica,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0fdf4;padding:32px 0;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);">

      <!-- Header -->
      <tr><td style="background:#16a34a;padding:40px;text-align:center;">
        <div style="width:64px;height:64px;background:rgba(255,255,255,.2);border-radius:50%;margin:0 auto 16px;line-height:64px;font-size:28px;">&#127881;</div>
        <h1 style="color:#ffffff;font-size:28px;font-weight:700;margin:0;">Welcome aboard!</h1>
      </td></tr>

      <!-- Body -->
      <tr><td style="padding:40px;">
        <p style="font-size:18px;color:#111827;font-weight:600;margin:0 0 12px;">Hi {{first_name}}, we\'re so glad you\'re here.</p>
        <p style="font-size:15px;color:#6b7280;line-height:1.7;margin:0 0 20px;">Thank you for joining us. You\'re now part of a community of people who care about [what you offer]. Here\'s what happens next:</p>

        <!-- Steps -->
        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
          <tr>
            <td width="36" style="vertical-align:top;padding-top:2px;">
              <div style="width:28px;height:28px;background:#dcfce7;border-radius:50%;text-align:center;line-height:28px;font-size:13px;font-weight:700;color:#16a34a;">1</div>
            </td>
            <td style="padding-left:12px;padding-bottom:16px;">
              <p style="font-size:14px;font-weight:600;color:#111827;margin:0 0 4px;">Check your inbox</p>
              <p style="font-size:14px;color:#6b7280;margin:0;">We\'ll send you useful tips and updates regularly.</p>
            </td>
          </tr>
          <tr>
            <td width="36" style="vertical-align:top;padding-top:2px;">
              <div style="width:28px;height:28px;background:#dcfce7;border-radius:50%;text-align:center;line-height:28px;font-size:13px;font-weight:700;color:#16a34a;">2</div>
            </td>
            <td style="padding-left:12px;padding-bottom:16px;">
              <p style="font-size:14px;font-weight:600;color:#111827;margin:0 0 4px;">Explore our resources</p>
              <p style="font-size:14px;color:#6b7280;margin:0;">Visit our website to discover everything we have to offer.</p>
            </td>
          </tr>
          <tr>
            <td width="36" style="vertical-align:top;padding-top:2px;">
              <div style="width:28px;height:28px;background:#dcfce7;border-radius:50%;text-align:center;line-height:28px;font-size:13px;font-weight:700;color:#16a34a;">3</div>
            </td>
            <td style="padding-left:12px;">
              <p style="font-size:14px;font-weight:600;color:#111827;margin:0 0 4px;">Reach out anytime</p>
              <p style="font-size:14px;color:#6b7280;margin:0;">Reply to this email — we read and respond to every message.</p>
            </td>
          </tr>
        </table>

        <table cellpadding="0" cellspacing="0" style="margin:0 auto;">
          <tr><td style="background:#16a34a;border-radius:8px;">
            <a href="#" style="display:inline-block;padding:14px 32px;color:#ffffff;text-decoration:none;font-size:15px;font-weight:600;">Get Started &rarr;</a>
          </td></tr>
        </table>
      </td></tr>

      <!-- Footer -->
      <tr><td style="background:#f9fafb;border-top:1px solid #e5e7eb;padding:24px 40px;text-align:center;">
        <p style="font-size:12px;color:#9ca3af;margin:0;">
          &copy; 2025 Your Company &nbsp;&bull;&nbsp;
          <a href="{{unsubscribe_url}}" style="color:#9ca3af;text-decoration:underline;">Unsubscribe</a>
        </p>
      </td></tr>

    </table>
  </td></tr>
</table>
</body></html>';
	}

	private static function tpl_announcement() {
		return '<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Announcement</title></head>
<body style="margin:0;padding:0;background:#f5f3ff;font-family:Arial,Helvetica,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f3ff;padding:32px 0;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);">

      <!-- Header -->
      <tr><td style="background:#7c3aed;padding:36px 40px;text-align:center;">
        <p style="color:rgba(255,255,255,.8);font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:2px;margin:0 0 8px;">Announcement</p>
        <h1 style="color:#ffffff;font-size:28px;font-weight:700;margin:0;line-height:1.3;">We have exciting news to share</h1>
      </td></tr>

      <!-- Body -->
      <tr><td style="padding:40px;">
        <p style="font-size:16px;color:#374151;margin:0 0 16px;">Hi {{first_name}},</p>
        <p style="font-size:15px;color:#6b7280;line-height:1.7;margin:0 0 20px;">We\'re thrilled to announce something we\'ve been working hard on. This is a big moment for us, and we wanted you to be one of the first to know.</p>

        <!-- Highlight box -->
        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
          <tr><td style="background:#f5f3ff;border-left:4px solid #7c3aed;border-radius:0 8px 8px 0;padding:20px 24px;">
            <p style="font-size:16px;font-weight:700;color:#5b21b6;margin:0 0 8px;">What\'s changing</p>
            <p style="font-size:14px;color:#6b7280;line-height:1.6;margin:0;">Describe the change or announcement here. Be specific, clear, and enthusiastic. Your subscribers will appreciate transparency.</p>
          </td></tr>
        </table>

        <p style="font-size:15px;color:#6b7280;line-height:1.7;margin:0 0 28px;">If you have any questions, feel free to reply to this email. We\'re here for you every step of the way.</p>

        <table cellpadding="0" cellspacing="0">
          <tr><td style="background:#7c3aed;border-radius:8px;">
            <a href="#" style="display:inline-block;padding:13px 28px;color:#ffffff;text-decoration:none;font-size:14px;font-weight:600;">Learn More</a>
          </td></tr>
        </table>
      </td></tr>

      <!-- Signature -->
      <tr><td style="padding:0 40px 32px;">
        <p style="font-size:14px;color:#374151;margin:0;">Best,<br><strong>The Team at Your Company</strong></p>
      </td></tr>

      <!-- Footer -->
      <tr><td style="background:#f9fafb;border-top:1px solid #e5e7eb;padding:20px 40px;text-align:center;">
        <p style="font-size:12px;color:#9ca3af;margin:0;">
          <a href="{{unsubscribe_url}}" style="color:#9ca3af;text-decoration:underline;">Unsubscribe</a> &nbsp;&bull;&nbsp; Powered by <a href="https://ideaboss.io" style="color:#9ca3af;">ideaBoss</a>
        </p>
      </td></tr>

    </table>
  </td></tr>
</table>
</body></html>';
	}

	private static function tpl_event() {
		return '<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Event Invitation</title></head>
<body style="margin:0;padding:0;background:#fffbeb;font-family:Arial,Helvetica,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#fffbeb;padding:32px 0;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);">

      <!-- Header -->
      <tr><td style="background:#d97706;padding:40px;text-align:center;">
        <p style="color:rgba(255,255,255,.85);font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:2px;margin:0 0 12px;">You\'re Invited</p>
        <h1 style="color:#ffffff;font-size:30px;font-weight:700;margin:0 0 8px;">Event Name Here</h1>
        <p style="color:rgba(255,255,255,.85);font-size:16px;margin:0;">Join us for an unforgettable experience</p>
      </td></tr>

      <!-- Event Details -->
      <tr><td style="padding:40px;">
        <p style="font-size:16px;color:#374151;margin:0 0 20px;">Dear {{first_name}},</p>
        <p style="font-size:15px;color:#6b7280;line-height:1.7;margin:0 0 28px;">We\'d love for you to join us at our upcoming event. It\'s going to be a great opportunity to [connect / learn / celebrate] with others in our community.</p>

        <!-- Details grid -->
        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
          <tr>
            <td width="32%" style="background:#fffbeb;border-radius:8px;padding:16px;text-align:center;vertical-align:top;">
              <p style="font-size:22px;margin:0 0 8px;">&#128197;</p>
              <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#d97706;margin:0 0 4px;">Date</p>
              <p style="font-size:14px;font-weight:600;color:#111827;margin:0;">Saturday, Jan 15<br>2026</p>
            </td>
            <td width="2%"></td>
            <td width="32%" style="background:#fffbeb;border-radius:8px;padding:16px;text-align:center;vertical-align:top;">
              <p style="font-size:22px;margin:0 0 8px;">&#9201;</p>
              <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#d97706;margin:0 0 4px;">Time</p>
              <p style="font-size:14px;font-weight:600;color:#111827;margin:0;">6:00 PM &ndash;<br>9:00 PM EST</p>
            </td>
            <td width="2%"></td>
            <td width="32%" style="background:#fffbeb;border-radius:8px;padding:16px;text-align:center;vertical-align:top;">
              <p style="font-size:22px;margin:0 0 8px;">&#128205;</p>
              <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#d97706;margin:0 0 4px;">Location</p>
              <p style="font-size:14px;font-weight:600;color:#111827;margin:0;">Venue Name<br>City, State</p>
            </td>
          </tr>
        </table>

        <!-- RSVP -->
        <table width="100%" cellpadding="0" cellspacing="0" style="background:#fffbeb;border-radius:8px;margin-bottom:8px;">
          <tr><td style="padding:24px;text-align:center;">
            <p style="font-size:14px;color:#92400e;margin:0 0 16px;">Seats are limited &mdash; reserve yours today!</p>
            <table cellpadding="0" cellspacing="0" style="margin:0 auto;">
              <tr><td style="background:#d97706;border-radius:8px;">
                <a href="#" style="display:inline-block;padding:14px 40px;color:#ffffff;text-decoration:none;font-size:15px;font-weight:700;">RSVP Now &rarr;</a>
              </td></tr>
            </table>
          </td></tr>
        </table>
      </td></tr>

      <!-- Footer -->
      <tr><td style="background:#f9fafb;border-top:1px solid #e5e7eb;padding:20px 40px;text-align:center;">
        <p style="font-size:12px;color:#9ca3af;margin:0;">
          &copy; 2025 Your Company &nbsp;&bull;&nbsp;
          <a href="{{unsubscribe_url}}" style="color:#9ca3af;text-decoration:underline;">Unsubscribe</a>
        </p>
      </td></tr>

    </table>
  </td></tr>
</table>
</body></html>';
	}
}
