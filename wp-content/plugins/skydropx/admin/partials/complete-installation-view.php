<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Get the attachment ID by its slug.
 *
 * @param string $slug The slug of the attachment.
 * @return int|null The attachment ID or null if not found.
 */
function get_attachment_id_by_slug($slug) {
    $args = [
        'post_type'   => 'attachment',
        'name'        => sanitize_title($slug),
        'post_status' => 'inherit',
        'numberposts' => 1,
    ];

    $attachments = get_posts($args);

    if (!empty($attachments)) {
        return $attachments[0]->ID;
    }

    return null;
}

// Get the image IDs for registered images
$logo_image_id = get_attachment_id_by_slug('skydropx-logo.png');
$plugin_image_id = get_attachment_id_by_slug('skydropx-success.png');
?>

<div class="center-div">
    <div class="skydropx-content">
        <div class="skydropx-logo-container">
            <?php 
            if ($logo_image_id) {
                echo wp_get_attachment_image($logo_image_id, 'full', false, [
                    // translators: Alt text for the Skydropx logo.
                    'alt' => esc_attr__('Skydropx logo', 'skydropx'),
                ]);
            } else {
                // translators: Fallback message when the Skydropx logo is not found.
                echo '<p>' . esc_html__('Logo not found.', 'skydropx') . '</p>';
            }
            ?>
        </div>
        <div class="skydropx-description-container">
            <p class="skydropx-description">
                <?php 
                // translators: Success message after plugin activation.
                esc_html_e('¡Activación completada exitosamente!', 'skydropx'); 
                ?>
            </p>
            <p class="skydropx-description-sub-content">
                <?php 
                // translators: Message to guide the user to integrate their store with Skydropx.
                esc_html_e('Para acceder a todas las funcionalidades, vincule su tienda con nuestra plataforma Skydropx.', 'skydropx'); 
                ?>
            </p>
            <?php if (!$is_first_time) : ?>
                <p class="skydropx-description-sub-content">
                    <?php 
                    // translators: Message advising the user to deactivate/reactivate the plugin or contact support.
                    esc_html_e('Si ya vinculaste tu tienda en Skydropx y no aparece en el plugin, porfavor, desactiva y activa nuevamente el plugin o contacta a soporte.', 'skydropx'); 
                    ?>
                </p>
            <?php endif; ?>
        </div>
        <?php if (!empty($button_link) && !empty($button_text)) : ?>
            <a class="skydropx-btn" href="<?php echo esc_url($button_link); ?>">
                <?php 
                // translators: Text for the integration action button.
                echo esc_html($button_text); 
                ?>
            </a>
        <?php else : ?>
            <p class="skydropx-no-action">
                <?php 
                // translators: Message displayed when no actions are available.
                esc_html_e('No hay acciones disponibles en este momento.', 'skydropx'); 
                ?>
            </p>
        <?php endif; ?>
        <div class="skydropx-image-container">
            <?php 
            if ($plugin_image_id) {
                echo wp_get_attachment_image($plugin_image_id, 'full', false, [
                    // translators: Alt text for the Skydropx plugin image.
                    'alt' => esc_attr__('Skydropx plugin image', 'skydropx'),
                ]);
            } else {
                // translators: Fallback message when the Skydropx plugin image is not found.
                echo '<p>' . esc_html__('Plugin image not found.', 'skydropx') . '</p>';
            }
            ?>
        </div>
    </div>
</div>