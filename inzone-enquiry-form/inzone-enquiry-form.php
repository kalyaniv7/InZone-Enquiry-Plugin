<?php
/**
 * Plugin Name: Inzone Enquiry Form
 * Description: A custom WordPress plugin providing an AJAX-based enquiry form with reCAPTCHA and SMTP/WP Mail settings.
 * Version: 1.0
 * Author: Kalyani Verma
 * Text Domain: inzone-enquiry-form
 * Date: 2025-03-23
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main Plugin Class
 */
class Inzone_Enquiry_Form {

    // Unique option key for storing plugin settings in WP options table
    private $option_key = 'inzone_enquiry_form_options';
    // Cached copy of plugin settings
    private $settings   = array();

    /**
     * Constructor - Set up hooks
     */
    public function __construct() {
        // Load plugin text domain if needed (for translations)
        // $this->load_textdomain();

        // Register activation hook
        register_activation_hook( __FILE__, array( $this, 'activate_plugin' ) );
        // Register deactivation hook
        register_deactivation_hook( __FILE__, array( $this, 'deactivate_plugin' ) );

        // Admin settings
        add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );

        // Shortcode
        add_shortcode( 'enquiry_form', array( $this, 'render_enquiry_form' ) );

        // AJAX endpoints
        add_action( 'wp_ajax_submit_enquiry_form', array( $this, 'handle_ajax_form_submission' ) );
        add_action( 'wp_ajax_nopriv_submit_enquiry_form', array( $this, 'handle_ajax_form_submission' ) );

        // Attempt to load the settings for usage
        $this->load_settings();
    }

    /**
     * Loads plugin settings from the database.
     */
    private function load_settings() {
        $defaults = array(
            'mail_method'       => 'wp_mail', // or 'smtp'
            'smtp_host'         => '',
            'smtp_port'         => '',
            'smtp_username'     => '',
            'smtp_password'     => '',
            'to_email'          => get_option( 'admin_email' ),
            'subject'           => 'InZone Enquiry from',
            'message_template'  => "This is a new enquiry:\n\nName: [NAME]\nEmail: [EMAIL]\nPhone: [PHONE]\nMessage: [TEXT]",
            'success_message'   => 'Thank you for your Enquiry! Your enquiry has been sent to the Administrator.',
            'recaptcha_site_key'    => '',
            'recaptcha_secret_key'  => ''
        );

        $saved = get_option( $this->option_key, array() );
        $this->settings = wp_parse_args( $saved, $defaults );
    }

    /**
     * Activate plugin callback
     */
    public function activate_plugin() {
        // Ensure default settings are stored
        $this->load_settings();
        update_option( $this->option_key, $this->settings );
    }

    /**
     * Deactivate plugin callback
     */
    public function deactivate_plugin() {
        // You can remove settings or do cleanup if desired
        // delete_option( $this->option_key );
    }

    /**
     * Register the plugin admin menu
     */
    public function register_admin_menu() {
        add_menu_page(
            __( 'Inzone Enquiry Form Settings', 'inzone-enquiry-form' ),
            __( 'InZone Enquiry Form', 'inzone-enquiry-form' ),
            'manage_options',
            'inzone-enquiry-form-settings',
            array( $this, 'render_settings_page' ),
            'dashicons-email-alt',
            59
        );
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting( 'inzone_enquiry_form_settings_group', $this->option_key );

        // You can add settings sections and fields if you want more structure.
        // For simplicity, we’ll handle them directly on the settings page callback.
    }

    /**
     * Render the plugin settings page
     */
    public function render_settings_page() {
        // Must check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Process form submission if posted
        if ( isset( $_POST[ $this->option_key ] ) ) {
            check_admin_referer( 'inzone_enquiry_form_save_settings' );

            // Sanitize and store
            $posted = $_POST[ $this->option_key ];
            $this->settings['mail_method']          = sanitize_text_field( $posted['mail_method'] );
            $this->settings['smtp_host']            = sanitize_text_field( $posted['smtp_host'] );
            $this->settings['smtp_port']            = sanitize_text_field( $posted['smtp_port'] );
            $this->settings['smtp_username']        = sanitize_text_field( $posted['smtp_username'] );
            $this->settings['smtp_password']        = sanitize_text_field( $posted['smtp_password'] );
            $this->settings['to_email']             = sanitize_email( $posted['to_email'] );
            $this->settings['subject']              = sanitize_text_field( $posted['subject'] );
            $this->settings['message_template']     = wp_kses_post( $posted['message_template'] );
            $this->settings['success_message']      = sanitize_text_field( $posted['success_message'] );
            $this->settings['recaptcha_site_key']   = sanitize_text_field( $posted['recaptcha_site_key'] );
            $this->settings['recaptcha_secret_key'] = sanitize_text_field( $posted['recaptcha_secret_key'] );

            update_option( $this->option_key, $this->settings );

            echo '<div class="updated"><p>' . __( 'Settings saved.', 'inzone-enquiry-form' ) . '</p></div>';
        }

        // Output settings form
        ?>
        <div class="wrap">
            <h1><?php _e( 'Enquiry Form Settings', 'inzone-enquiry-form' ); ?></h1>
            <form method="post" action="">
                <?php wp_nonce_field( 'inzone_enquiry_form_save_settings' ); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e( 'Mail Method', 'inzone-enquiry-form' ); ?></th>
                        <td>
                            <select name="<?php echo $this->option_key; ?>[mail_method]">
                                <option value="wp_mail" <?php selected( $this->settings['mail_method'], 'wp_mail' ); ?>>WP Mail</option>
                                <option value="smtp" <?php selected( $this->settings['mail_method'], 'smtp' ); ?>>SMTP</option>
                            </select>
                            <p class="description">Choose how emails should be sent.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e( 'SMTP Host', 'inzone-enquiry-form' ); ?></th>
                        <td>
                            <input type="text" name="<?php echo $this->option_key; ?>[smtp_host]" value="<?php echo esc_attr( $this->settings['smtp_host'] ); ?>" class="regular-text" />
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e( 'SMTP Port', 'inzone-enquiry-form' ); ?></th>
                        <td>
                            <input type="text" name="<?php echo $this->option_key; ?>[smtp_port]" value="<?php echo esc_attr( $this->settings['smtp_port'] ); ?>" class="regular-text" />
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e( 'SMTP Username', 'inzone-enquiry-form' ); ?></th>
                        <td>
                            <input type="text" name="<?php echo $this->option_key; ?>[smtp_username]" value="<?php echo esc_attr( $this->settings['smtp_username'] ); ?>" class="regular-text" />
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e( 'SMTP Password', 'inzone-enquiry-form' ); ?></th>
                        <td>
                            <input type="password" name="<?php echo $this->option_key; ?>[smtp_password]" value="<?php echo esc_attr( $this->settings['smtp_password'] ); ?>" class="regular-text" />
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e( 'Send Enquiry To (TO Email)', 'inzone-enquiry-form' ); ?></th>
                        <td>
                            <input type="text" name="<?php echo $this->option_key; ?>[to_email]" value="<?php echo esc_attr( $this->settings['to_email'] ); ?>" class="regular-text" />
                            <p class="description">If blank, it will default to WordPress admin email.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e( 'Email Subject', 'inzone-enquiry-form' ); ?></th>
                        <td>
                            <input type="text" name="<?php echo $this->option_key; ?>[subject]" value="<?php echo esc_attr( $this->settings['subject'] ); ?>" class="regular-text" />
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e( 'Message Template', 'inzone-enquiry-form' ); ?></th>
                        <td>
                            <textarea name="<?php echo $this->option_key; ?>[message_template]" rows="6" class="large-text"><?php echo esc_textarea( $this->settings['message_template'] ); ?></textarea>
                            <p class="description">Use [NAME], [EMAIL], [PHONE], [TEXT] as placeholders for user data.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e( 'Success Message', 'inzone-enquiry-form' ); ?></th>
                        <td>
                            <input type="text" name="<?php echo $this->option_key; ?>[success_message]" value="<?php echo esc_attr( $this->settings['success_message'] ); ?>" class="regular-text" />
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e( 'reCAPTCHA Site Key', 'inzone-enquiry-form' ); ?></th>
                        <td>
                            <input type="text" name="<?php echo $this->option_key; ?>[recaptcha_site_key]" value="<?php echo esc_attr( $this->settings['recaptcha_site_key'] ); ?>" class="regular-text" />
                            <p class="description">Get your site key from Google reCAPTCHA admin panel.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e( 'reCAPTCHA Secret Key', 'inzone-enquiry-form' ); ?></th>
                        <td>
                            <input type="text" name="<?php echo $this->option_key; ?>[recaptcha_secret_key]" value="<?php echo esc_attr( $this->settings['recaptcha_secret_key'] ); ?>" class="regular-text" />
                            <p class="description">Get your secret key from Google reCAPTCHA admin panel.</p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render the Enquiry Form via shortcode
     */
    public function render_enquiry_form() {
        // Enqueue CSS and JS *only* if the shortcode is used
        $this->enqueue_assets();

        // We need the reCAPTCHA site key
        $site_key = esc_attr( $this->settings['recaptcha_site_key'] );

        ob_start();
        ?>
        <div class="enquiry-form-wrapper">
            <form id="enquiry-form" method="post" action="#">
                <div class="enquiry-field">
                    <label for="enquiry_name"><?php _e( 'Full Name', 'inzone-enquiry-form' ); ?></label>
                    <input type="text" id="enquiry_name" name="enquiry_name" required />
                </div>

                <div class="enquiry-field">
                    <label for="enquiry_email"><?php _e( 'Email Address', 'inzone-enquiry-form' ); ?></label>
                    <input type="email" id="enquiry_email" name="enquiry_email" required />
                </div>

                <div class="enquiry-field">
                    <label for="enquiry_phone"><?php _e( 'Phone', 'inzone-enquiry-form' ); ?></label>
                    <input type="text" id="enquiry_phone" name="enquiry_phone" required />
                </div>

                <div class="enquiry-field">
                    <label for="enquiry_message"><?php _e( 'Message', 'inzone-enquiry-form' ); ?></label>
                    <textarea id="enquiry_message" name="enquiry_message" required></textarea>
                </div>

                <?php if ( $site_key ) : ?>
                    <!-- Google reCAPTCHA -->
                    <div class="g-recaptcha" data-sitekey="<?php echo $site_key; ?>"></div>
                <?php endif; ?>

                <button type="submit" id="enquiry-submit-btn">
                    <?php _e( 'Submit', 'inzone-enquiry-form' ); ?>
                </button>
            </form>

            <!-- Error/Success message container -->
            <div id="enquiry-form-response" class="enquiry-form-response"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Enqueue CSS and JS for the form only when shortcode is used
     */
    private function enqueue_assets() {
        // Plugin dir URL
        $plugin_url = plugin_dir_url( __FILE__ );

        // Enqueue CSS
        wp_enqueue_style(
            'inzone-enquiry-form-css',
            $plugin_url . 'assets/css/inzone-enquiry-form.css',
            array(),
            '1.0',
            'all'
        );

        // Enqueue JS
        wp_enqueue_script(
            'inzone-enquiry-form-js',
            $plugin_url . 'assets/js/inzone-enquiry-form.js',
            array( 'jquery' ), // depends on jQuery
            '1.0',
            true
        );

        // Localize data for AJAX
        wp_localize_script( 'inzone-enquiry-form-js', 'EnquiryFormVars', array(
            'ajax_url'  => admin_url( 'admin-ajax.php' ),
            'site_key'  => $this->settings['recaptcha_site_key'],
        ) );

        // If reCAPTCHA is used, load the official JS
        if ( ! empty( $this->settings['recaptcha_site_key'] ) ) {
            wp_enqueue_script( 'google-recaptcha', 'https://www.google.com/recaptcha/api.js', array(), null, true );
        }
    }

    /**
     * Handle the AJAX form submission
     */
    public function handle_ajax_form_submission() {
        // Parse the form data
        $name    = isset( $_POST['enquiry_name'] ) ? sanitize_text_field( $_POST['enquiry_name'] ) : '';
        $email   = isset( $_POST['enquiry_email'] ) ? sanitize_email( $_POST['enquiry_email'] ) : '';
        $phone   = isset( $_POST['enquiry_phone'] ) ? sanitize_text_field( $_POST['enquiry_phone'] ) : '';
        $message = isset( $_POST['enquiry_message'] ) ? sanitize_textarea_field( $_POST['enquiry_message'] ) : '';
        $recaptcha_response = isset( $_POST['g-recaptcha-response'] ) ? $_POST['g-recaptcha-response'] : '';

        // Validate required fields
        if ( empty( $name ) || empty( $email ) || empty( $phone ) || empty( $message ) ) {
            wp_send_json_error( array( 'message' => __( 'All fields are required.', 'inzone-enquiry-form' ) ) );
        }

        if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid email address.', 'inzone-enquiry-form' ) ) );
        }

        // Validate reCAPTCHA if site key/secret key are set
        if ( ! empty( $this->settings['recaptcha_site_key'] ) && ! empty( $this->settings['recaptcha_secret_key'] ) ) {
            $secret_key = $this->settings['recaptcha_secret_key'];
            $verify_url = "https://www.google.com/recaptcha/api/siteverify?secret={$secret_key}&response={$recaptcha_response}";

            $response = wp_remote_get( $verify_url );
            if ( is_wp_error( $response ) ) {
                wp_send_json_error( array( 'message' => __( 'reCAPTCHA verification failed. Please try again.', 'inzone-enquiry-form' ) ) );
            }
            $body = wp_remote_retrieve_body( $response );
            $result = json_decode( $body, true );

            if ( empty( $result['success'] ) ) {
                wp_send_json_error( array( 'message' => __( 'Invalid reCAPTCHA. Please try again.', 'inzone-enquiry-form' ) ) );
            }
        }

        // All good: Send email
        $mail_method = $this->settings['mail_method'];
        $to_email    = ! empty( $this->settings['to_email'] ) ? $this->settings['to_email'] : get_option( 'admin_email' );
        $subject     = $this->settings['subject'];
        $msg_tmpl    = $this->settings['message_template'];

        // Replace placeholders
        $msg_body = str_replace(
            array('[NAME]', '[EMAIL]', '[PHONE]', '[TEXT]'),
            array($name, $email, $phone, $message),
            $msg_tmpl
        );

        // If SMTP is selected, hook into PHPMailer
        if ( 'smtp' === $mail_method ) {
            add_action( 'phpmailer_init', array( $this, 'configure_smtp' ) );
        }

        // Construct headers
        $headers = array( 'Content-Type: text/plain; charset=UTF-8' );
        // If WP mail, set From to Admin email or optional
        if ( 'wp_mail' === $mail_method ) {
            $headers[] = 'From: ' . get_option( 'blogname' ) . ' <' . get_option( 'admin_email' ) . '>';
        }

        $mail_sent = wp_mail( $to_email, $subject, $msg_body, $headers );

        if ( $mail_sent ) {
            $success_message = ! empty( $this->settings['success_message'] ) ? $this->settings['success_message'] : __( 'Thank you, your enquiry has been sent.', 'inzone-enquiry-form' );
            wp_send_json_success( array( 'message' => $success_message ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Unable to send email. Please try again later.', 'inzone-enquiry-form' ) ) );
        }
    }

    /**
     * Configure SMTP settings for PHPMailer when mail_method is SMTP
     */
    public function configure_smtp( $phpmailer ) {
        $phpmailer->isSMTP();
        $phpmailer->Host       = $this->settings['smtp_host'];
        $phpmailer->Port       = $this->settings['smtp_port'];
        $phpmailer->SMTPAuth   = true;
        $phpmailer->Username   = $this->settings['smtp_username'];
        $phpmailer->Password   = $this->settings['smtp_password'];
        $phpmailer->SMTPSecure = 'tls';  // or '' / 'ssl' as required by your SMTP
        $phpmailer->From       = $this->settings['smtp_username'];
        $phpmailer->FromName   = get_option( 'blogname' ); // or any name
    }

    /**
     * (Optional) Load textdomain for translations
     */
    private function load_textdomain() {
        // load_plugin_textdomain( 'inzone-enquiry-form', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }
}

// Initialize plugin
new Inzone_Enquiry_Form();
