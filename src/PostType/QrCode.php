<?php

namespace Fabrikage\QR\PostType;

use chillerlan\QRCode\QRCode as QRCodeGenerator;
use chillerlan\QRCode\QROptions;

class QrCode
{
    public function __construct()
    {
        add_action('init', [$this, 'register']);
    }

    public static function init(): static
    {
        return new static();
    }

    public function register()
    {
        register_post_type('qr-code', [
            'labels' => [
                'name' => __('QR Codes', 'fabrikage'),
                'singular_name' => __('QR Code', 'fabrikage'),
            ],
            'public' => false,
            'publicly_queryable' => true,
            'show_ui' => true,
            'has_archive' => false,
            'show_in_menu' => true,
            'show_in_nav_menus' => true,
            'show_in_rest' => false,
            'menu_position' => 20,
            'menu_icon' => 'dashicons-admin-links',
            'supports' => ['title'],
            'rewrite' => false
        ]);

        add_action('add_meta_boxes', [$this, 'addMetaBox']);
        add_action('save_post', [$this, 'saveMetaBox']);

        $this->registerRewriteRules();
        add_filter('query_vars', [$this, 'registerQueryVars']);

        add_action('template_redirect', [$this, 'templateRedirect']);
    }

    public function addMetaBox()
    {
        add_meta_box(
            'qr_redirect_url',
            __('URL to redirect to', 'fabrikage'),
            [$this, 'renderMetaBox'],
            'qr-code',
            'normal',
            'default'
        );

        remove_meta_box('wpseo_meta', 'qr-code', 'normal');
    }

    public function renderMetaBox($post)
    {
        $data = get_post_meta($post->ID, 'qr_redirect_url', true);
?>

        <style>
            .qr-wrap {
                display: inline-block;
                width: 200px;
                height: 200px;
            }

            .qr-wrap a {
                display: block;
                width: 100%;
                height: 100%;
            }

            .qr-wrap .qrcode.light {
                fill: red;
            }
        </style>

        <label for="qr_redirect_url"><?php _e('URL to redirect to', 'fabrikage'); ?></label>
        <input type="url" name="qr_redirect_url" id="qr_redirect_url" placeholder="https://example.com" pattern="https://.*" size="30" value="<?php echo esc_attr($data); ?>" required />

        <p><?php _e('Link in QR code', 'fabrikage'); ?>: <a href="<?php echo esc_url(self::getQrCodeUrl($post->ID)); ?>" target="_blank"><?php echo esc_url(self::getQrCodeUrl($post->ID)); ?></a></p>
        <p><?php _e('QR code image', 'fabrikage'); ?>:<br>
        <div class="qr-wrap" style="border: 1px solid #efefef; display: inline-block;">
            <a href="<?php echo esc_url(self::getQrCodeImageUrl($post->ID)); ?>" target="_blank">
                <?php echo self::getQrCodeImage($post->ID); ?>
            </a>
        </div>
        </p>
        <p><?php _e('Number of unique visits', 'fabrikage'); ?>: <?php echo self::getClicks($post->ID); ?></p>

<?php
    }

    public static function getClicks($postId): int
    {
        global $wpdb;

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(id) FROM {$wpdb->prefix}qr_code_redirects WHERE post_id = %d",
                $postId
            )
        );
    }

    public function saveMetaBox($postId)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $postId)) {
            return;
        }

        if (!isset($_POST['qr_redirect_url'])) {
            return;
        }

        $data = sanitize_text_field($_POST['qr_redirect_url']);
        update_post_meta($postId, 'qr_redirect_url', $data);
    }

    public static function getQrCodeData($postId): string
    {
        return get_post_meta($postId, 'qr_redirect_url', true);
    }

    public static function getQrCodeUrl($postId): string
    {
        return home_url('/qr/' . $postId);
    }

    public static function getQrCodeImageUrl($postId): string
    {
        return home_url('/qr/' . $postId . '/image');
    }

    public static function getQrCodeImage($postId): string
    {
        $data = self::getQrCodeData($postId);
        $imageUrl = self::getQrCodeImageUrl($postId);

        if (!empty($data) && !empty($imageUrl)) {
            return '<img src="' . esc_url($imageUrl) . '" />';
        }

        return '';
    }

    public function registerRewriteRules()
    {
        add_rewrite_rule('qr/([0-9]+)/image$', 'index.php?qr_code_image=$matches[1]', 'top');
        add_rewrite_rule('qr/([0-9]+)$', 'index.php?qr_code_redirect=$matches[1]', 'top');
    }

    public function registerQueryVars($vars)
    {
        $vars[] = 'qr_code_image';
        $vars[] = 'qr_code_redirect';

        return $vars;
    }

    public function templateRedirect()
    {
        $postId = get_query_var('qr_code_image');

        if ($postId) {
            $this->serveQrCodeImage($postId);
        }

        $postId = get_query_var('qr_code_redirect');

        if ($postId) {
            $this->registerRedirect($postId);
            $this->redirect($postId);
        }
    }

    public function serveQrCodeImage($postId)
    {
        $data = self::getQrCodeData($postId);

        if (empty($data)) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            get_template_part(404);
            exit;
        }

        $image = static::getQrCodeSvg($postId);

        header('Content-Type: image/svg+xml');
        header('Content-Disposition: inline; filename="indifferent-qrcode-' . $postId . '.svg"');
        echo $image;
        exit;
    }

    public static function getQrCodeSvg($postId): string
    {
        $data = self::getQrCodeData($postId);

        if (empty($data)) {
            return '';
        }

        $qrCode = new QRCodeGenerator();
        $options = new QROptions([
            'imageTransparent' => true,
            'outputBase64' => false,
            'drawLightModules' => false,
        ]);
        $qrCode->setOptions($options);

        return $qrCode->render($data);
    }

    public function redirect($postId)
    {
        $data = self::getQrCodeData($postId);

        if (empty($data)) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            get_template_part(404);
            exit;
        }

        wp_redirect($data);
        exit;
    }

    private function registerRedirect($postId)
    {
        global $wpdb;

        $click = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}qr_code_redirects WHERE post_id = %d AND ip_address = %s AND user_agent = %s",
                $postId,
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT']
            )
        );

        if ($click) {
            return;
        }

        $wpdb->insert(
            $wpdb->prefix . 'qr_code_redirects',
            [
                'post_id' => $postId,
                'created_at' => current_time('mysql'),
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            ]
        );
    }
}
