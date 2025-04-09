<?php
defined('ABSPATH') || exit;
//autoload images

/**
 * Register necessary images into the WordPress Media Library.
 */
function skydropx_register_images()
{
    $image_files = [
        'skydropx-logo.png' => 'skydropx-logo.png',
        'skydropx-success.png' => 'skydropx-success.png',
    ];

    foreach ($image_files as $image_name => $relative_path) {
        // Adjust the path to locate images in the assets/images directory
        $image_path = plugin_dir_path(dirname(__FILE__)) . 'assets/images/' . $relative_path;

        // Check if the image is already registered
        if (file_exists($image_path) && !skydropx_image_already_registered($image_name)) {
            skydropx_upload_image_to_media_library($image_path, $image_name);
        }
    }
}

/**
 * Check if the image is already registered in the Media Library.
 *
 * @param string $image_name The name of the image.
 * @return bool True if registered, false otherwise.
 */
function skydropx_image_already_registered($image_name)
{
    $query = new WP_Query([
        'post_type'      => 'attachment',
        'post_status'    => 'inherit',
        'posts_per_page' => 1, // Only need to know if it exists
        'meta_query'     => [
            [
                'key'   => '_wp_attachment_image_name',
                'value' => $image_name,
                'compare' => '=',
            ],
        ],
        'fields' => 'ids', // Only return IDs, minimal data
    ]);

    return $query->have_posts();
}

/**
 * Upload and register an image in the WordPress Media Library.
 *
 * @param string $file_path The full path to the image file.
 * @param string $file_name The file name.
 */
function skydropx_upload_image_to_media_library($file_path, $file_name)
{
    $file_type = wp_check_filetype($file_name);
    $allowed_mime_types = get_allowed_mime_types();

    if (!in_array($file_type['type'], $allowed_mime_types)) {
        return; // Skip invalid file types
    }

    $upload_dir = wp_upload_dir();
    $upload_file = $upload_dir['path'] . '/' . basename($file_name);

    if (!copy($file_path, $upload_file)) {
        return;
    }

    $attachment = [
        'guid'           => $upload_dir['url'] . '/' . basename($file_name),
        'post_mime_type' => $file_type['type'],
        'post_title'     => sanitize_file_name($file_name),
        'post_content'   => '',
        'post_status'    => 'inherit',
        'meta_input'     => [
            '_wp_attachment_image_name' => $file_name,
        ],
    ];

    $attachment_id = wp_insert_attachment($attachment, $upload_file);

    if (is_wp_error($attachment_id)) {
        return;
    }

    require_once ABSPATH . 'wp-admin/includes/image.php';
    $attach_data = wp_generate_attachment_metadata($attachment_id, $upload_file);

    wp_update_attachment_metadata($attachment_id, $attach_data);
}
