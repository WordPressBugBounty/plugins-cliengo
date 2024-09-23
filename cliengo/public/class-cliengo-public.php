<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Cliengo
 * @subpackage Cliengo/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Cliengo
 * @subpackage Cliengo/public
 * @author     Your Name <email@example.com>
 */
class Cliengo_Public {

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
   * Initialize the class and set its properties.
   *
   * @since    1.0.0
   * @param      string    $plugin_name       The name of the plugin.
   * @param      string    $version    The version of this plugin.
   */
  public function __construct( $plugin_name, $version ) {

    $this->plugin_name = $plugin_name;
    $this->version = $version;

  }

  /**
   * Register the stylesheets for the public-facing side of the site.
   *
   * @since    1.0.0
   */
  public function enqueue_styles() {
    /**
     * This function is provided for demonstration purposes only.
     *
     * An instance of this class should be passed to the run() function
     * defined in Cliengo_Loader as all of the hooks are defined
     * in that particular class.
     *
     * The Cliengo_Loader will then create the relationship
     * between the defined hooks and the functions defined in this
     * class.
     */
    if ( ! is_admin() ) {
      wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/cliengo-public.css', array(), $this->version, 'all' );
    }

  }

  /**
   * Register the JavaScript for the public-facing side of the site.
   *
   * @since    1.0.0
   */
  public function enqueue_scripts() {
    if ( ! is_admin() ) {
      $position_chatbot = get_option('cliengo_chatbot_position');
      $chatbot_token = get_option('cliengo_chatbot_token');

      /**
       * PATCH:
       * Since we fucked it up with script_install_cliengo.js dev -> prod, we should must regenerate script before rendering
       */
      if (isset($chatbot_token) && !empty($chatbot_token)) {
        Cliengo_Form::create_install_code_cliengo($chatbot_token);
      }

      wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/cliengo-public.js', array( 'jquery' ), $this->version, false );
      wp_enqueue_script( 'script-install-cliengo', plugin_dir_url( __FILE__ ) . 'js/script_install_cliengo.js', array(), $this->version, true );



      if ($position_chatbot == 'left')
      {
        wp_enqueue_script( 'position_chatbot', plugin_dir_url( __FILE__ ) . 'js/position_chatbot.js', array(), $this->version, false );
      }
    }
  }
}
