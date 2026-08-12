<?php
/*
Plugin Name: Export Galerie ZIP
Description: Ajouter un bouton pour exporter les images ACF (photos) en ZIP
Version: 1.0
*/

if (!defined('ABSPATH')) exit;

// ✅ Ajouter bouton dans l'admin (CPT galerie)
add_action('add_meta_boxes', function() {
    add_meta_box(
        'export_galerie_zip',
        'Exporter la galerie',
        'render_export_button',
        'galerie',
        'side'
    );
});

function render_export_button($post) {
    $url = admin_url('admin-post.php?action=export_galerie_zip&post_id=' . $post->ID);
    echo '<a href="' . esc_url($url) . '" class="button button-primary">📦 Exporter en ZIP</a>';
}

// ✅ Hook export
add_action('admin_post_export_galerie_zip', 'handle_export_galerie_zip');

function handle_export_galerie_zip() {

    if (!current_user_can('edit_posts')) {
        wp_die('Accès refusé');
    }

    $post_id = intval($_GET['post_id']);

    if (!$post_id) {
        wp_die('Post ID invalide');
    }

    // 🔥 Récupérer ACF
    $photos = get_field('photos', $post_id);
    $galerie_titre = get_field('titre', $post_id);

    if (!$photos) {
        wp_die('Aucune image trouvée');
    }

    // 📁 Créer ZIP
    $zip = new ZipArchive();
    $zip_name = sanitize_title($galerie_titre) . '.zip';

    $tmp_file = tempnam(sys_get_temp_dir(), $zip_name);

    if ($zip->open($tmp_file, ZipArchive::CREATE) !== TRUE) {
        wp_die('Impossible de créer le ZIP');
    }

    foreach ($photos as $image) {

        // URL image
        $image_url = $image['url'];

        // récupérer contenu
        $image_data = file_get_contents($image_url);

        if ($image_data) {
            $filename = basename($image_url);
            $zip->addFromString($filename, $image_data);
        }
    }

    $zip->close();

    // 📥 Télécharger
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zip_name . '"');
    header('Content-Length: ' . filesize($tmp_file));

    readfile($tmp_file);
    unlink($tmp_file);

    exit;
}


