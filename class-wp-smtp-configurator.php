<?php

namespace WP_SMTP_Configurator;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

/**
 * WP SMTP Configurator
 */
class WP_SMTP_Configurator {
	/**
	 * @var string
	 */
	public const VERSION = '1.0.0';

	/**
	 * @var string
	 */
	public const PLUGIN_ID = 'wp-smtp-configurator';

	/**
	 * @var string
	 */
	public const MENU_SLUG = 'wp-smtp-configurator';

	/**
	 * @var string
	 */
	public const CONFIG_NAME = 'wp_smtp_configurator_config';

	/**
	 * @var string
	 */
	protected const NONCE_ACTION = 'wp_smtp_configurator_nonce_action';

	/**
	 * @var string
	 */
	protected const NONCE_NAME = 'wp_smtp_configurator_nonce_name';

	/**
	 * @var array<string, mixed>
	 */
	protected const DEFAULT_CONFIG = array(
		'is_enable'   => false,
		'from'        => 'wordpress@example.com',
		'from_name'   => 'WordPress',
		'is_smtp'     => true,
		'host'        => 'example.com',
		'port'        => SMTP::DEFAULT_SECURE_PORT,
		'smtp_auth'   => true,
		'username'    => 'username',
		'password'    => 'password',
		'smtp_secure' => PHPMailer::ENCRYPTION_SMTPS,
	);

	/**
	 * @var static
	 */
	protected static $instance;

	/**
	 * @var array<string, mixed>
	 */
	protected $config;

	/**
	 * @return static
	 */
	public static function instance(): static {
		if ( ! isset( static::$instance ) || ! static::$instance instanceof static ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * @return void
	 */
	public static function activation(): void {
		add_option( static::CONFIG_NAME, static::DEFAULT_CONFIG );
	}

	/**
	 * @return void
	 */
	public static function deactivation(): void {
		// Nothing to do.
	}

	/**
	 * @return void
	 */
	public static function uninstall(): void {
		delete_option( static::CONFIG_NAME );
	}

	protected function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * @return void
	 */
	public function init(): void {
		$this->load_config();
		$this->apply_config();

		if ( is_admin() ) {
			add_action( 'admin_init', array( $this, 'save_config' ) );
			add_action( 'admin_menu', array( $this, 'add_submenu_page' ) );
		}
	}

	/**
	 * @return void
	 */
	public function save_config(): void {
		if ( ! $this->check_nonce() ) {
			return;
		}

		$result      = false;
		$prev_config = $this->config;

		foreach ( array_keys( static::DEFAULT_CONFIG ) as $key ) {
			$value = null;
			switch ( $key ) {
				case 'is_enable':
				case 'is_smtp':
				case 'smtp_auth':
					$value = ! empty( $_POST[ $key ] );
					break;
				case 'port':
					if ( ! empty( $_POST[ $key ] ) ) {
						$value = (int) $_POST[ $key ];
					}
					break;
				default:
					if ( ! empty( $_POST[ $key ] ) ) {
						$value = $_POST[ $key ];
					}
					break;
			}
			$this->config[ $key ] = $value;
		}

		if ( $prev_config !== $this->config ) {
			$result = update_option( static::CONFIG_NAME, $this->config );
		} else {
			$result = true;
		}

		add_action(
			'admin_notices',
			fn () => wp_admin_notice(
				$result ? '設定を保存しました。' : '設定が保存できませんでした。',
				array(
					'type'        => $result ? 'success' : 'error',
					'dismissible' => true,
				),
			),
		);
	}

	/**
	 * @return void
	 */
	public function add_submenu_page(): void {
		add_submenu_page(
			'options-general.php',
			'SMTP設定',
			'SMTP設定',
			'manage_options',
			static::MENU_SLUG,
			array( $this, 'show_config_form' ),
		);
	}

	/**
	 * @return void
	 */
	public function show_config_form(): void {
		?>
		<div class="wrap">
			<h1>SMTP設定</h1>
			<form action="" method="post">
				<?php wp_nonce_field( static::NONCE_ACTION, static::NONCE_NAME ); ?>
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">有効／無効</th>
							<td>
								<input name="is_enable" type="checkbox" id="is_enable" value="1" <?php echo esc_attr( $this->config['is_enable'] ? 'checked' : '' ); ?>>
								<label for="is_enable">有効化する</label>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="from">送信元メールアドレス</label>
							</th>
							<td>
								<input name="from" type="email" id="from" value="<?php echo esc_attr( $this->config['from'] ); ?>" class="regular-text">
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="from_name">送信元表示名</label>
							</th>
							<td>
								<input name="from_name" type="text" id="from_name" value="<?php echo esc_attr( $this->config['from_name'] ); ?>" class="regular-text">
							</td>
						</tr>
						<tr>
							<th scope="row">SMTP</th>
							<td>
								<input name="is_smtp" type="checkbox" id="is_smtp" value="1" <?php echo esc_attr( $this->config['is_smtp'] ? 'checked' : '' ); ?>>
								<label for="is_smtp">SMTPを使用する</label>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="host">ホスト</label>
							</th>
							<td>
								<input name="host" type="text" id="host" value="<?php echo esc_attr( $this->config['host'] ); ?>" class="regular-text">
								<label for="port">ポート</label>
								<input name="port" type="number" id="port" value="<?php echo esc_attr( $this->config['port'] ); ?>" class="small-text">
							</td>
						</tr>
						<tr>
							<th scope="row">SMTP認証</th>
							<td>
								<input name="smtp_auth" type="checkbox" id="smtp_auth" value="1" <?php echo esc_attr( $this->config['smtp_auth'] ? 'checked' : '' ); ?>>
								<label for="smtp_auth">SMTP認証を使用する</label>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="username">ユーザー名</label>
							</th>
							<td>
								<input name="username" type="text" id="username" value="<?php echo esc_attr( $this->config['username'] ); ?>" class="regular-text">
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="password">パスワード</label>
							</th>
							<td>
								<input name="password" type="password" id="password" value="<?php echo esc_attr( $this->config['password'] ); ?>" class="regular-text">
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="smtp_secure">暗号化</label>
							</th>
							<td>
								<select name="smtp_secure" id="smtp_secure">
									<option value="">なし</option>
									<option value="<?php echo esc_attr( PHPMailer::ENCRYPTION_STARTTLS ); ?>" <?php echo esc_attr( $this->config['smtp_secure'] === PHPMailer::ENCRYPTION_STARTTLS ? 'selected' : '' ); ?>>
										TLS
									</option>
									<option value="<?php echo esc_attr( PHPMailer::ENCRYPTION_SMTPS ); ?>" <?php echo esc_attr( $this->config['smtp_secure'] === PHPMailer::ENCRYPTION_SMTPS ? 'selected' : '' ); ?>>
										SSL
									</option>
								</select>
							</td>
						</tr>
					</tbody>
				</table>
				<p class="submit">
					<button type="submit" class="button button-primary">変更を保存</button>
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * @return string
	 */
	public function wp_mail_from_filter(): string {
		return $this->config['from'];
	}

	/**
	 * @return string
	 */
	public function wp_mail_from_name_filter(): string {
		return $this->config['from_name'];
	}

	/**
	 * @param \PHPMailer\PHPMailer\PHPMailer $phpmailer
	 * @return void
	 */
	public function phpmailer_init_action( PHPMailer $phpmailer ): void {
		$phpmailer->isSMTP();
		$phpmailer->Host       = $this->config['host'];
		$phpmailer->Port       = $this->config['port'];
		$phpmailer->SMTPAuth   = $this->config['smtp_auth'];
		$phpmailer->Username   = $this->config['smtp_auth'] ? $this->config['username'] : null;
		$phpmailer->Password   = $this->config['smtp_auth'] ? $this->config['password'] : null;
		$phpmailer->SMTPSecure = $this->config['smtp_secure'];
	}

	/**
	 * @param array<string, mixed> $override_config
	 * @return void
	 */
	protected function load_config( array $override_config = array() ): void {
		$config = get_option( static::CONFIG_NAME, static::DEFAULT_CONFIG );

		$load_config = apply_filters( static::CONFIG_NAME, $config );

		$this->config = array( ...$load_config, ...$override_config );
	}

	/**
	 * @return void
	 */
	protected function apply_config(): void {
		remove_filter( 'wp_mail_from', array( $this, 'wp_mail_from_filter' ) );
		remove_filter( 'wp_mail_from_name', array( $this, 'wp_mail_from_name_filter' ) );
		remove_action( 'phpmailer_init', array( $this, 'phpmailer_init_action' ) );

		if ( ! $this->config['is_enable'] ) {
			return;
		}

		add_filter( 'wp_mail_from', array( $this, 'wp_mail_from_filter' ) );
		add_filter( 'wp_mail_from_name', array( $this, 'wp_mail_from_name_filter' ) );

		if ( ! $this->config['is_smtp'] ) {
			return;
		}

		add_action( 'phpmailer_init', array( $this, 'phpmailer_init_action' ) );
	}

	/**
	 * @return bool
	 */
	protected function check_nonce(): bool {
		return ! empty( $_POST[ static::NONCE_NAME ] ) && check_admin_referer( static::NONCE_ACTION, static::NONCE_NAME );
	}
}
