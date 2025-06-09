<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://example.com/
 * @since      1.0.0
 *
 * @package    Novel_AI_Chatbot
 * @subpackage Novel_AI_Chatbot/public
 */

class Novel_AI_Chatbot_Public {

    private $plugin_name;
    private $version;
    private $chat_history;

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->chat_history = new Novel_AI_Chatbot_Chat_History( $plugin_name, $version );
        add_filter( 'script_loader_tag', array( $this, 'add_type_attribute' ), 10, 3 );
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     * This is the corrected and final version of the function.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        if (is_admin()) return;

        $custom_options = get_option('novel_ai_chatbot_chat_customization_options', array());
        $version = isset($custom_options['customization_version']) ? $custom_options['customization_version'] : $this->version;
        wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/novel-ai-chatbot-public.css', array(), $version, 'all' );

        $primary_color = isset($custom_options['chat_primary_color']) ? sanitize_hex_color($custom_options['chat_primary_color']) : '#3B82F6';
        $bg_color = isset($custom_options['chat_bg_color']) ? sanitize_hex_color($custom_options['chat_bg_color']) : '#ffffff';
        $user_msg_bg_color = isset($custom_options['user_msg_bg_color']) ? sanitize_hex_color($custom_options['user_msg_bg_color']) : '#e6f0ff';
        $bot_msg_bg_color = isset($custom_options['bot_msg_bg_color']) ? sanitize_hex_color($custom_options['bot_msg_bg_color']) : '#f0f0f0';
        $text_color = isset($custom_options['chat_text_color']) ? sanitize_hex_color($custom_options['chat_text_color']) : '#333333';
        
        $widget_position = isset($custom_options['widget_position']) ? $custom_options['widget_position'] : 'bottom-right';
        $position_css = '';
        
        if ($widget_position === 'bottom-left') {
            $position_css = "
                #novel-ai-chatbot-app {
                    right: auto;
                    left: 20px;
                    bottom: 20px;
                    z-index: 2147483640;
                }
            ";
        } else {
            $position_css = "
                #novel-ai-chatbot-app {
                    left: auto;
                    right: 20px;
                    bottom: 20px;
                    z-index: 2147483640;
                }
            ";
        }

        $custom_css = "
            :root {
                --nac-primary-color: {$primary_color};
                --nac-bg-color: {$bg_color};
                --nac-user-msg-bg: {$user_msg_bg_color};
                --nac-bot-msg-bg: {$bot_msg_bg_color};
                --nac-text-color: {$text_color};
            }
            {$position_css}
        ";

        wp_add_inline_style( $this->plugin_name, $custom_css );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        if (is_admin()) return;

        $custom_options = get_option('novel_ai_chatbot_chat_customization_options', array());
        $version = isset($custom_options['customization_version']) ? $custom_options['customization_version'] : time();
        
        wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/novel-ai-chatbot-public.js', array( 'jquery' ), $version, true );

        $display_method = isset( $custom_options['display_method'] ) ? $custom_options['display_method'] : 'floating';
        $initial_bot_message_text = isset( $custom_options['initial_bot_message'] ) ? esc_html( $custom_options['initial_bot_message'] ) : esc_html__( 'Hello there! Ask me anything about this website.', 'novel-ai-chatbot' );
        
        $session_id = $this->chat_history->generate_session_id();

        wp_localize_script(
            $this->plugin_name,
            'novel_ai_chatbot_public_vars',
            array(
                'ajax_url'            => admin_url( 'admin-ajax.php' ),
                'nonce'               => wp_create_nonce( 'novel-ai-chatbot-public-nonce' ),
                'session_id'          => $session_id,
                'initialBotMessage'   => $initial_bot_message_text,
                'i18n' => [
                    'request_failed'      => __( 'ارسال پیام با خطا مواجه شد.', 'novel-ai-chatbot' ),
                    'unknownError'        => __( 'یک خطای نامشخص رخ داده است.', 'novel-ai-chatbot' ),
                    'networkError'        => __( 'خطای شبکه. لطفا دوباره تلاش کنید.', 'novel-ai-chatbot' ),
                    'wait_for_operator'   => __( 'درخواست شما برای صحبت با اپراتور ثبت شد. لطفاً منتظر بمانید...', 'novel-ai-chatbot' ),
                    'thank_you_feedback'  => __( 'از بازخورد شما متشکریم!', 'novel-ai-chatbot' )
                ]
            )
        );
    }

    /**
     * Renders the chatbox HTML into the footer if the display method is 'floating'.
     *
     * @since 1.0.0
     */
    public function render_chatbox_if_floating() {
        if (is_admin()) return;
        $custom_options = get_option( 'novel_ai_chatbot_chat_customization_options', array() );
        $display_method = isset( $custom_options['display_method'] ) ? $custom_options['display_method'] : 'floating';
        if ( 'floating' === $display_method ) {
            include_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/partials/novel-ai-chatbot-chatbox-display.php';
        }
    }

    /**
     * Shortcode callback to render the chatbox.
     *
     * @since 1.0.0
     * @param array $atts Shortcode attributes.
     * @return string The HTML for the chatbox.
     */
    public function render_chatbox_shortcode( $atts ) {
        if (is_admin()) return '';
        $this->enqueue_styles();
        $this->enqueue_scripts();
        ob_start();
        include plugin_dir_path( dirname( __FILE__ ) ) . 'public/partials/novel-ai-chatbot-chatbox-display.php';
        $output = ob_get_clean();
        return $output;
    }

    /**
     * Adds type="module" attribute to our specific script tag.
     *
     * @since 1.4.0
     */
    public function add_type_attribute( $tag, $handle, $src ) {
        if ( $this->plugin_name === $handle ) {
            $tag = '<script type="module" src="' . esc_url( $src ) . '" id="' . esc_attr($handle) . '-js"></script>';
        }
        return $tag;
    }
}