<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://www.cliengo.com
 * @since      1.0.0
 *
 * @package    Cliengo
 * @subpackage Cliengo/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Cliengo
 * @subpackage Cliengo/admin
 * @author     Your Name <email@example.com>
 */
class Cliengo_Form {

  /**
   * The ID of this plugin.
   *
   * @since    1.0.0
   * @access   private
   * @var      string    $plugin_name    The ID of this plugin.
   */
  private $plugin_name;

  /**
   * The version of this plugin.
   *
   * @since    1.0.0
   * @access   private
   * @var      string    $version    The current version of this plugin.
   */
  private $version;

  /**
   * Indicates whether we are in production or development state
   */
  const PROD_ENV = true;

  /**
   * Regex pattern for validating chatbot tokens (two 24-char hex strings separated by a dash)
   */
  const CHATBOT_TOKEN_PATTERN = '/^[0-9a-f]{24}-[0-9a-f]{24}$/i';

  /**
   * Initialize the class and set its properties.
   *
   * @since    1.0.0
   * @param      string    $plugin_name       The name of this plugin.
   * @param      string    $version    The version of this plugin.
   */

  public function __construct( $plugin_name, $version ) {

    $this->plugin_name = $plugin_name;
    $this->version = $version;
  }

  /**
   * Validates a chatbot token server-side.
   * Token must be two 24-character hex strings separated by a dash.
   *
   * @param string $token The chatbot token to validate.
   * @return bool Whether the token is valid.
   */
  private static function validate_chatbot_token( $token ) {
    return is_string( $token ) && preg_match( self::CHATBOT_TOKEN_PATTERN, $token );
  }

  /**
   * Fetches and returns all session variables
   */
  public function restore_session() {
    if ( ! current_user_can( 'manage_options' ) ) {
      wp_die( 'Error: You do not have sufficient permissions to perform this action.' );
    }

    check_ajax_referer( 'restore_session_action', 'restore_session_nonce' );

    $account = get_option( 'cliengo_session' );
    $session = array(
      'token' => stripslashes( get_option( 'cliengo_chatbot_token' ) ),
      'account' => $account != null ? json_decode(stripslashes($account)) : '',
      'position' => stripslashes( get_option( 'cliengo_chatbot_position' ) )
    );

    echo wp_json_encode($session);
    wp_die();
  }

  /**
   * Updates or clears the chabot token in DB
   */
  public function update_chatbot_token()
  {
    if ( ! current_user_can( 'manage_options' ) ) {
      wp_die( 'Error: You do not have sufficient permissions to perform this action. Please contact the site administrator.' );
    }

    check_ajax_referer( 'update_chatbot_token_action', 'update_chatbot_token_nonce' );

    $raw_token = isset( $_POST['chatbot_token'] ) ? wp_unslash( $_POST['chatbot_token'] ) : '';
    $chatbot_token = sanitize_text_field( $raw_token );
    $position = isset( $_POST['position_chatbot'] ) ? sanitize_text_field( wp_unslash( $_POST['position_chatbot'] ) ) : 'right';

    // Reject if sanitization altered the value (indicates malicious input)
    if ( $chatbot_token !== $raw_token ) {
      wp_die( 'Error: Invalid chatbot token — contains disallowed characters.' );
    }

    // Whitelist position values
    if ( ! in_array( $position, array( 'left', 'right' ), true ) ) {
      $position = 'right';
    }

    if ( empty( $chatbot_token ) ) {
      // Clear token and remove script
      $this->update_cliengo_option( 'cliengo_chatbot_token', '' );
      $this->update_cliengo_option( 'cliengo_chatbot_position', $position );
      wp_delete_file( plugin_dir_path( __FILE__ ) . '../public/js/script_install_cliengo.js' );
      echo wp_json_encode( true );
      wp_die();
    }

    // Validate token format server-side
    if ( ! self::validate_chatbot_token( $chatbot_token ) ) {
      wp_die( 'Error: Invalid chatbot token format.' );
    }

    $response = $this->update_cliengo_option( 'cliengo_chatbot_token', $chatbot_token )
      && $this->update_cliengo_option( 'cliengo_chatbot_position', $position );

    if ( $response ) {
      Cliengo_Form::create_install_code_cliengo( $chatbot_token );
    }

    echo wp_json_encode($response);

    wp_die();
  }

  /**
   * Updates the chatbot position
   */
  public function update_chatbot_position()
  {
    if ( ! current_user_can( 'manage_options' ) ) {
      wp_die( 'Error: You do not have sufficient permissions to perform this action. Please contact the site administrator.' );
    }

    check_ajax_referer( 'update_chatbot_position_action', 'update_chatbot_position_nonce' );

    $position = isset( $_POST['position_chatbot'] ) ? sanitize_text_field( wp_unslash( $_POST['position_chatbot'] ) ) : 'right';

    // Whitelist position values
    if ( ! in_array( $position, array( 'left', 'right' ), true ) ) {
      $position = 'right';
    }

    echo wp_json_encode( $this->update_cliengo_option( 'cliengo_chatbot_position', $position ) );

    wp_die();
  }

  /**
   * Updates session (which is the response obtained from wordpress_login and wp_registration)
   */
  public function update_session()
  {
    if ( ! current_user_can( 'manage_options' ) ) {
      wp_die( 'Error: You do not have sufficient permissions to perform this action. Please contact the site administrator.' );
    }

    check_ajax_referer( 'update_session_action', 'update_session_nonce' );

    $response = false;

    if ( isset( $_POST['chatbot_session'] ) ) {
      $raw_session = wp_unslash( $_POST['chatbot_session'] );

      // Validate JSON structure if non-empty
      if ( ! empty( $raw_session ) ) {
        $decoded = json_decode( $raw_session, true );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
          wp_die( 'Error: Invalid session data format.' );
        }
        // Re-encode to sanitize the JSON
        $sanitized_session = wp_json_encode( $decoded );
      } else {
        $sanitized_session = '';
      }

      $response = $this->update_cliengo_option( 'cliengo_session', $sanitized_session );
    }

    echo wp_json_encode($response);

    wp_die();
  }

  /**
   * Attempt to log in to Cliengo via the plugin's admin console.  Response will include company ID and website IDs so
   * the user can choose which website that the chatbot is being installed in.
   */
  public function wordpress_login()
  {
    if ( ! current_user_can( 'manage_options' ) ) {
      wp_die( 'Error: You do not have sufficient permissions to perform this action. Please contact the site administrator.' );
    }

    check_ajax_referer( 'wordpress_login_action', 'wordpress_login_nonce' );

    $username = isset( $_POST['username'] ) ? sanitize_email( wp_unslash( $_POST['username'] ) ) : '';
    $password = isset( $_POST['password'] ) ? wp_unslash( $_POST['password'] ) : '';

    $api_host = Cliengo_Form::PROD_ENV ? 'https://api.cliengo.com' : 'https://api.stagecliengo.com';
    $body = array( 'username' => $username, 'password' => $password );
    $api_response = wp_remote_request("$api_host/1.0/wordpress/login", array(
        'method' => 'POST',
        'headers' => array(
            'content-type' => 'application/json',
        ),
        'body' => wp_json_encode($body),
        'timeout' => 30
    ));

    if ( is_wp_error( $api_response ) ) {
      wp_die( 'Error: Could not connect to Cliengo API.', 500 );
    }

    if ($api_response['response']['code'] == 200) {
        echo wp_json_encode($api_response['body']);
        wp_die();
    } else {
        wp_die("Couldn't log in from the plugin", esc_html($api_response['response']['code']));
    }
  }

  /**
   * Updates or creates a WP option entry
   * @param $option - the option's (unique) name
   * @param $new_value - the option's value.
   * @return bool indicating if update was perform successfully or not
   */
  private function update_cliengo_option ($option, $new_value)
  {
    // Whitelist allowed option names
    $allowed_options = array( 'cliengo_chatbot_token', 'cliengo_chatbot_position', 'cliengo_session' );
    if ( ! in_array( $option, $allowed_options, true ) ) {
      return false;
    }

    $current = get_option($option);

    $response = true;
    if ($current !== false)
    {
      if (strcmp($current, $new_value) !== 0)
        $response = update_option($option, $new_value);
    }
    else
    {
      $response = add_option($option, $new_value);
    }

    return $response;
  }

  /**
   * Bundles the chatbot script installation code and writes it down to the public/js/script_install_cliengo.js file.
   *
   * @param $chatbot_token - the saved chatbot token.
   */
  public static function create_install_code_cliengo($chatbot_token)
  {
    // Validate token format before building script
    if ( ! self::validate_chatbot_token( $chatbot_token ) ) {
      return;
    }

    $array_chatbot_token = explode('-',$chatbot_token);
    // Both parts are already validated as 24-char hex strings by validate_chatbot_token
    $company_id = $array_chatbot_token[0];
    $website_id = $array_chatbot_token[1];

    $install_code_cliengo = '(function(){var ldk=document.createElement("script"); ldk.type="text/javascript";';
    $install_code_cliengo .= 'ldk.async=true; ldk.src="https://s.cliengo.com/weboptimizer/' . $company_id . '/';
    $install_code_cliengo .= $website_id;
    $install_code_cliengo .= '.js?platform=wordpress"; var s=document.getElementsByTagName("script")[0]; s.parentNode.insertBefore(ldk, s);})();';

    $install_code_cliengo = str_replace(array("\r", "\n"), '', $install_code_cliengo);

    Cliengo_Form::write_to_file_install_code_cliengo_js($install_code_cliengo);
  }

  /**
   * Writes down the cliengo installation code into public/js/script_install_cliengo.js,
   * which then gets injected on every client side page rendering.
   * @param $install_code_cliengo
   */
  public static function write_to_file_install_code_cliengo_js($install_code_cliengo)
  {
    if ( ! function_exists( 'WP_Filesystem' ) ) {
      require_once ABSPATH . 'wp-admin/includes/file.php';
    }

    WP_Filesystem();
    global $wp_filesystem;

    $ruta_install_code_cliengo_file = plugin_dir_path( __FILE__ ) . '../public/js/script_install_cliengo.js';

    $wp_filesystem->put_contents(
      $ruta_install_code_cliengo_file,
      $install_code_cliengo,
      FS_CHMOD_FILE
    );
  }

  /**
   * Registers the user at Cliengo and obtains company and website from server response
   */
  public function wp_registration()
  {
    if ( ! current_user_can( 'manage_options' ) ) {
      wp_die( 'Error: You do not have sufficient permissions to perform this action. Please contact the site administrator.' );
    }

    check_ajax_referer( 'wp_registration_action', 'wp_registration_nonce' );

    $username = isset( $_POST['username'] ) ? sanitize_text_field( wp_unslash( $_POST['username'] ) ) : '';
    $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
    $password = isset( $_POST['password'] ) ? wp_unslash( $_POST['password'] ) : '';
    $source_name = isset( $_POST['sourceName'] ) ? esc_url_raw( wp_unslash( $_POST['sourceName'] ) ) : '';
    $account_name = isset( $_POST['accountName'] ) ? sanitize_text_field( wp_unslash( $_POST['accountName'] ) ) : '';
    $origin_url = isset( $_POST['originUrl'] ) ? esc_url_raw( wp_unslash( $_POST['originUrl'] ) ) : '';

    $api_host = Cliengo_Form::PROD_ENV ? 'https://api.cliengo.com' : 'https://api.stagecliengo.com';
    $lang = get_locale();
    $body = array(
      'username' => $username,
      'email' => $email,
      'password' => $password,
      'sourceName' => $source_name,
      'accountName' => $account_name,
      'language' => substr($lang, 0, 2),
      'originUrl' => $origin_url
    );

    $api_response = wp_remote_request("$api_host/1.0/wordpress/signup", array(
      'method' => 'POST',
      'headers' => array(
        'content-type' => 'application/json',
      ),
      'body' => wp_json_encode($body),
      'timeout' => 30
    ));

    if ( is_wp_error( $api_response ) ) {
      wp_die( 'Error: Could not connect to Cliengo API.', 500 );
    }

    echo wp_json_encode($api_response['body']);
    wp_die('', esc_html($api_response['response']['code']));
  }
}
