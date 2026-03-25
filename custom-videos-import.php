<?php
/**
 * Plugin Name: Import de vidéos YouTube (Class Only)
 * Description: Importe les vidéos d'une playlist YouTube dans un CPT.
 * Version: 1.0.0
 * Author: Gonsallo Ayrton
 */

if (!defined('ABSPATH')) exit;

class YoutubePlaylistImporter {

    private $api_key = 'AIzaSyDpWUjA5yuQcuAXPGmJU3Pu_tGgq7Qee80';
    private $playlist_id = 'PLhaq-As0z0QgdwCAbHutyx2CNloGRwQyW';
    private $cpt = 'video_youtube';

    public function __construct() {
        add_action('admin_menu', [$this, 'admin_menu']);
    }

    /* ===============================
       CPT
    =============================== */


    /* ===============================
       ADMIN
    =============================== */
    public function admin_menu() {
        add_submenu_page(
            'edit.php?post_type=' . $this->cpt,
            'Importer playlist',
            'Importer playlist',
            'manage_options',
            'import-youtube',
            [$this, 'import_page']
        );
    }

    public function import_page() {
        if (isset($_POST['import_youtube'])) {
            $result = $this->import_playlist();
            echo '<div class="updated"><p>Import terminé</p></div>';
            echo '<pre>';
            print_r($result);
            echo '</pre>';
        }

        echo '
        <div class="wrap">
            <h1>Importer une playlist YouTube</h1>
            <form method="post">
                <input type="submit" name="import_youtube" class="button button-primary" value="Importer">
            </form>
        </div>';
    }

    /* ===============================
       IMPORT
    =============================== */
    private function import_playlist() {

        $results = [
            'created' => [],
            'updated' => [],
            'skipped' => [],
            'errors'  => []
        ];

        $url = "https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&maxResults=50&playlistId={$this->playlist_id}&key={$this->api_key}";
        $response = wp_remote_get($url);

        if (is_wp_error($response)) return;

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($data['items'])) return;

        foreach ($data['items'] as $item) {

            $video_id = $item['snippet']['resourceId']['videoId'];
            $title = sanitize_text_field($item['snippet']['title']);
            $published_at = $item['snippet']['publishedAt'];
            $date = date('Y-m-d H:i:s', strtotime($published_at));



            // Évite les doublons
            if ($this->video_exists($video_id)){
                $results['skipped'][] = [
                    'video_id' => $video_id,
                    'title'    => $title,
                    'resourceId'    => $item['snippet']['resourceId']
                ];
                continue;
            } 
            

            $iframe = '<iframe width="560" height="315" src="https://www.youtube.com/embed/' . esc_attr($video_id) . '" frameborder="0" allowfullscreen></iframe>';

            $post_id = wp_insert_post([
                'post_title'   => $title,
                'post_content' => $iframe,
                'post_status'  => 'publish',
                'post_type'    => $this->cpt
            ]);

            if ($post_id) {
                update_post_meta($post_id, 'id', $video_id);
                update_post_meta($post_id, 'date_dajout', $date);
                update_post_meta($post_id, 'titre', $title);
                update_post_meta($post_id, 'saison', '2025-2026');
                $categorie = "";
                //ajouter le champ acf categorie
                //si $title ressemble a Laura Duchaussoy – FLAM 91, exactement un tiret $categorie = ippon
                //si $title ressemble a Flam 91 vs Judo Nice Métropole | Eliminatoires Judo Pro League 2025-2026 – Saison 4, si on a saison  $categorie = combat
                //si $title Judo Pro League : le debrief d’US Orléans Judo Loiret VS Montpellier Judo Olympic, si on a debrief  $categorie = editorial
                $titleLower = mb_strtolower($title);
                if (str_contains($titleLower, 'debrief') || str_contains($titleLower, 'débrief')) {
                    $categorie = 'editorial';
                } elseif (preg_match('/\bvs\b/i', $title) || str_contains($titleLower, 'saison')) {
                    $categorie = 'combat';
                } elseif (preg_match('/^[^-–]+[–-][^-–]+$/u', $title)) {
                    $categorie = 'ippon';
                }
                update_post_meta($post_id, 'categorie', $categorie);

                $results['created'][] = [
                    'video_id' => $video_id,
                    'title'    => $title,
                    'resourceId'    => $item['snippet']['resourceId']                
                    ];

            }
        }

        return $results;
    }

    private function video_exists($video_id) {
        $query = new WP_Query([
            'post_type'  => $this->cpt,
            'meta_key'   => 'id',
            'meta_value' => $video_id,
            'fields'     => 'ids'
        ]);
        return !empty($query->posts);
    }
}

new YoutubePlaylistImporter();
