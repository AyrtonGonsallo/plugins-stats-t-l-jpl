<?php
/**
 * Plugin Name: Ippon de la semaine
 * Description: Système de vote hebdomadaire avec statistiques.
 * Version: 1.0.0
 * Author: Gonsallo Ayrton
 */

if (!defined('ABSPATH')) exit;
function generate_embed_url($url){
    $url = trim($url);

    // YouTube classique
    if(preg_match('#youtube\.com/watch\?v=([a-zA-Z0-9_-]+)#', $url, $matches)){
        return 'https://www.youtube.com/embed/' . $matches[1];
    }

    // YouTube courte
    if(preg_match('#youtu\.be/([a-zA-Z0-9_-]+)#', $url, $matches)){
        return 'https://www.youtube.com/embed/' . $matches[1];
    }

    // Vimeo
    if(preg_match('#vimeo\.com/([0-9]+)#', $url, $matches)){
        return 'https://player.vimeo.com/video/' . $matches[1];
    }

    // Autres URL → on laisse telle quelle (assure-toi qu'elle accepte iframe)
    return $url;
}

class IpponSemaine {

    private $table_ippons;
    private $table_votes;

    public function __construct() {
        global $wpdb;
        $this->table_ippons = $wpdb->prefix . 'ippons';
        $this->table_votes  = $wpdb->prefix . 'ippon_votes';

        // Activation
        register_activation_hook(__FILE__, [$this, 'create_tables']);

        // CPT Admin
        add_action('init', [$this, 'register_cpt']);

        // Admin menu pour stats
        add_action('admin_menu', [$this, 'admin_menu']);

        // Shortcode front
        add_shortcode('ippons_semaine', [$this, 'shortcode_ippons']);

         add_shortcode('ippons_semaine_historique_et_vainqueur', [$this, 'shortcode_ippons_semaine_historique_et_vainqueur']);

        // Ajax vote
        add_action('wp_ajax_prol_vote', [$this, 'handle_vote']);
        add_action('wp_ajax_nopriv_prol_vote', [$this, 'handle_vote']);

        // Dans le constructeur du plugin
        add_action('add_meta_boxes', [$this, 'add_ippon_metabox']);
        add_action('save_post', [$this, 'save_ippon_metabox']);

        add_action('before_delete_post', function($post_id) {
            if (get_post_type($post_id) === 'ippon') {
                global $wpdb;
                $wpdb->delete(
                    $this->table_ippons,
                    ['id' => $post_id],
                    ['%d']
                );
            }
        });

       





    }


    
public function add_ippon_metabox() {
    add_meta_box(
        'ippon_details',
        'Détails du combat',
        [$this, 'render_ippon_metabox'],
        'ippon',
        'normal',
        'high'
    );
}

public function render_ippon_metabox($post) {
    wp_nonce_field('save_ippon_metabox','ippon_metabox_nonce');

    // Récupération des valeurs existantes
    $semaine_id = get_post_meta($post->ID,'semaine_id',true);
    $judoka1 = get_post_meta($post->ID,'judoka1_id',true);
    $judoka2 = get_post_meta($post->ID,'judoka2_id',true);
    $equipe1 = get_post_meta($post->ID,'equipe1_id',true);
    $equipe2 = get_post_meta($post->ID,'equipe2_id',true);
    $termine = get_post_meta($post->ID, 'termine', true);


    // Ici tu récupères les listes de judokas et équipes depuis tes tables
    // --- Récupérer les équipes pour la saison 2025-2026 ---
    $equipes = get_posts(array(
        'numberposts' => -1,
        'post_type'   => 'equipes',
        'orderby'     => 'title',
        'order'       => 'ASC',
        'meta_query'  => array(
            array(
                'key'     => 'saisons',
                'compare' => 'LIKE',
                'value'   => '2025-2026'
            )
        )
    ));

    $semaines = get_posts(array(
        'numberposts' => -1,
        'post_type'   => 'semaine',
        'meta_key' => 'date_de_debut',		
        'orderby' => 'meta_value_num',		
        'order' => 'DESC',	
    ));

    global $wpdb;
    $saison_value="2025-2026";
    $judokas = $wpdb->get_results($wpdb->prepare(
        "SELECT * 
        FROM prol_judokas_saisons 
        WHERE saison = %s 
        ORDER BY nom asc,prenom asc,sexe asc",
        $saison_value
    ));

 

    // --- Dropdown Judoka 1 ---
    echo '<p>Judoka 1: <select name="judoka1_id">';
    foreach($judokas as $jj){
        $j = get_post($jj->judoka_id);
        $sel = ($judoka1==$j->ID)?'selected':'';
        $date_creation = date_i18n('d/m/Y', strtotime($j->post_date));

        echo "<option value='{$j->ID}' $sel>{$j->post_title} ({$date_creation})</option>";
    }
    echo '</select></p>';

    // --- Dropdown Judoka 2 ---
    echo '<p>Judoka 2: <select name="judoka2_id">';
    foreach($judokas as $jj){
        $j = get_post($jj->judoka_id);
        $sel = ($judoka2==$j->ID)?'selected':'';
            $date_creation = date_i18n('d/m/Y', strtotime($j->post_date));

        echo "<option value='{$j->ID}' $sel>{$j->post_title} ({$date_creation})</option>";
    }
    echo '</select></p>';

    // --- Dropdown Équipe 1 ---
    echo '<p>Équipe 1: <select name="equipe1_id">';
    foreach($equipes as $e){
        $sel = ($equipe1==$e->ID)?'selected':'';
        echo "<option value='{$e->ID}' $sel>{$e->post_title}</option>";
    }
    echo '</select></p>';

    // --- Dropdown Équipe 2 ---
    echo '<p>Équipe 2: <select name="equipe2_id">';
    foreach($equipes as $e){
        $sel = ($equipe2==$e->ID)?'selected':'';
        echo "<option value='{$e->ID}' $sel>{$e->post_title}</option>";
    }
    echo '</select></p>';
   


     // --- Dropdown Semaine ---
    echo '<p>Semaine: <select name="semaine_id">';
    foreach($semaines as $s){
        $sel = ($semaine_id==$s->ID)?'selected':'';
        $nom = get_field('nom',$s->ID);
        echo "<option value='{$s->ID}' $sel>{$nom}</option>";
    }
    echo '</select></p>';
    
    

    $video_url = get_post_meta($post->ID, 'video_url', true);

    echo '<p>URL de la vidéo: <input type="text" name="video_url" value="'.esc_attr($video_url).'" size="50" placeholder="https://..."></p>';

}



public function save_ippon_metabox($post_id){
    if(!isset($_POST['ippon_metabox_nonce'])) return;
    if(!wp_verify_nonce($_POST['ippon_metabox_nonce'],'save_ippon_metabox')) return;
    if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    global $wpdb;
    $table_ippons =  $wpdb->prefix . 'ippons';

    // --- Meta fields ---
    $fields = ['semaine_id','judoka1_id','judoka2_id','equipe1_id','equipe2_id','video_url','date_debut','date_fin'];
    $data = [];
    foreach($fields as $f){
        if(isset($_POST[$f])){
            $data[$f] = ($f=='video_url') ? esc_url_raw($_POST[$f]) : intval($_POST[$f]);
            update_post_meta($post_id,$f,$data[$f]);
        }
    }

    // --- Insérer ou mettre à jour dans la table SQL ---
    // Vérifier si l’ippon existe déjà
    $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_ippons WHERE id=%d", $post_id));
    $now = current_time('timestamp'); // timestamp actuel
    $day_of_week = date('w', $now); // 0 = dimanche, 1 = lundi, ..., 6 = samedi

    // Calcul lundi 00:00
    $monday = strtotime('-'.($day_of_week==0?6:$day_of_week-1).' days', $now);
    $date_debut = date('Y-m-d 00:00:00', $monday);

    // Calcul dimanche 23:59
    $sunday = strtotime('+'.($day_of_week==0?0:7-$day_of_week).' days', $now);
    $date_fin = date('Y-m-d 23:59:59', $sunday);
    // ✅ Sauvegarde du champ "termine"
    $termine = isset($_POST['termine']) ? 1 : 0;


    if($exists){
        // Update
        $wpdb->update(
            $table_ippons,
            array(
                'titre'        => get_the_title($post_id),
                'description'  => get_post_field('post_content', $post_id),
                'video_url'    => $data['video_url'] ?? '',
                'date_debut'   => $date_debut,
                'date_fin'     => $date_fin,
                'semaine_id'   => $data['semaine_id'],
                'judoka1_id'   => $data['judoka1_id'] ?? 0,
                'judoka2_id'   => $data['judoka2_id'] ?? 0,
                'equipe1_id'   => $data['equipe1_id'] ?? 0,
                'equipe2_id'   => $data['equipe2_id'] ?? 0,
            ),
            array('id' => $post_id),
            array('%s','%s','%s','%s','%s','%d','%d','%d','%d'),
            array('%d')
        );
    } else {
        // Insert
        $wpdb->insert(
            $table_ippons,
            array(
                'id'           => $post_id, // On conserve l'ID WP
                'titre'        => get_the_title($post_id),
                'description'  => get_post_field('post_content', $post_id),
                'video_url'    => $data['video_url'] ?? '',
                'date_debut'   => $date_debut,
                'date_fin'     => $date_fin,
                'semaine_id'   => $data['semaine_id'],
                'judoka1_id'   => $data['judoka1_id'] ?? 0,
                'judoka2_id'   => $data['judoka2_id'] ?? 0,
                'equipe1_id'   => $data['equipe1_id'] ?? 0,
                'equipe2_id'   => $data['equipe2_id'] ?? 0,
                'votes_total'  => 0
            ),
            array('%d','%s','%s','%s','%s','%s','%d','%d','%d','%d','%d')
        );
    }
}


    // Création des tables
    public function create_tables() {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $charset = $wpdb->get_charset_collate();

        $sql_ippons = "CREATE TABLE {$this->table_ippons} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            titre VARCHAR(255) NOT NULL,
            description TEXT,
            video_url VARCHAR(500),
            date_debut DATETIME NOT NULL,
            date_fin DATETIME NOT NULL,
            judoka1_id BIGINT UNSIGNED NOT NULL,
            judoka2_id BIGINT UNSIGNED NOT NULL,
            equipe1_id BIGINT UNSIGNED NOT NULL,
            equipe2_id BIGINT UNSIGNED NOT NULL,
            votes_total INT DEFAULT 0,
            semaine_id INT NOT NULL,
            PRIMARY KEY (id)
        ) $charset;";

        $sql_votes = "CREATE TABLE {$this->table_votes} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            ippon_id BIGINT UNSIGNED NOT NULL,
            user_ip VARCHAR(255) NOT NULL,
            vote_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;";

        dbDelta($sql_ippons);
        dbDelta($sql_votes);
    }

    // CPT
    public function register_cpt() {
        register_post_type('ippon', [
            'label' => 'Ippons de la semaine',
            'public' => false,
            'show_ui' => true,
            'supports' => ['title','editor','custom-fields'],
            'menu_icon' => 'dashicons-thumbs-up'
        ]);
    }

    // Admin menu stats
    public function admin_menu() {
        add_submenu_page(
            'edit.php?post_type=ippon',
            'Stats Ippons',
            'Stats',
            'manage_options',
            'ippon-stats',
            [$this, 'render_admin_stats']
        );
    }

    public function render_admin_stats() {
        global $wpdb;
        $ippons = $wpdb->get_results("SELECT * FROM {$this->table_ippons} ORDER BY date_debut DESC");

        echo '<div class="wrap"><h1>Stats des Ippons</h1>';
        echo '<canvas id="chartIppons" width="600" height="400"></canvas>';

        $chart_data = [];
        $ippon_voters = [];
        foreach ($ippons as $i) {
            $votes = $wpdb->get_results($wpdb->prepare(
                "SELECT user_ip, COUNT(*) as nb FROM {$this->table_votes} WHERE ippon_id=%d GROUP BY user_ip", 
                $i->id
            ));
            $chart_data[$i->titre] = [];
            foreach ($votes as $v) {
                $chart_data[$i->titre][$v->user_ip] = intval($v->nb);
            }

             // Récupérer la liste des votants
            $voters = $wpdb->get_results($wpdb->prepare(
                "SELECT v.user_ip 
                FROM {$this->table_votes} v
                WHERE v.ippon_id = %d",
                $i->id
            ));
            $ippon_voters[$i->titre] = $voters;
        }

        echo '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';
        echo '<script>
        const ctx = document.getElementById("chartIppons").getContext("2d");
        const data = {
            labels: ' . json_encode(array_keys($chart_data)) . ',
            datasets: [{
                label: "Votes par ippon",
                data: ' . json_encode(array_map(fn($v) => array_sum($v), $chart_data)) . ',
                backgroundColor: "rgba(54, 162, 235, 0.5)"
            }]
        };
        const chart = new Chart(ctx, { type: "bar", data });
        </script>';
        echo '</div>';

        // Liste des votants par ippon
        foreach ($ippon_voters as $titre => $voters) {
            if($voters){
                echo '<h4>'.esc_html($titre).' - Votants</h4><ul>';
                foreach ($voters as $v) {
                    echo '<li>'.esc_html($v->user_ip).'</li>';
                }
                echo '</ul>';
            }
            
        }
    }

    // Shortcode front
    public function shortcode_ippons() {
       

        global $wpdb;
        $user_ip = '';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $user_ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $user_ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]; // prend la 1ère IP
        } else {
            $user_ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
        $now = current_time('mysql');

        // 🔹 Récupération de la semaine en cours (celle dont "termine" = 0)
        $semaine_curr = get_posts(array(
            'numberposts' => 1,
            'post_type'   => 'semaine',
            'meta_key'    => 'date_de_debut',
            'orderby'     => 'meta_value_num',
            'order'       => 'ASC',
            'meta_query'  => array(
                array(
                    'key'     => 'termine',
                    'value'   => '0',
                    'compare' => '='
                )
            )
        ));

        // 🔹 Si une semaine est trouvée
        if (!empty($semaine_curr)) {
            $semaine_id = $semaine_curr[0]->ID;

            // Récupérer les ippons liés à cette semaine
            $ippons = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$this->table_ippons} WHERE semaine_id = %d",
                $semaine_id
            ));

            if ($ippons) {

                // ... ton traitement habituel ici ...
                // Vérifier si l'utilisateur a déjà voté pour **cette semaine**
                $start_of_week = date('Y-m-d 00:00:00', strtotime('monday this week'));
                $already_voted = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$this->table_votes} WHERE user_ip = %s and vote_date >= %s",
                    $user_ip,$start_of_week,
                ));

                $output = '<div class="ippons-semaine-before">';
                
                if ($already_voted) {
                    // si il a voté on affiche les candidats et le pourcentage
                    // on calcule les bornes de la semaine
                    $end_of_week   = date('Y-m-d 23:59:59', strtotime('sunday this week'));

                    // requête SQL
                    $voted = $wpdb->get_var($wpdb->prepare(
                        "SELECT ippon_id 
                        FROM {$this->table_votes} 
                        WHERE user_ip = %s
                        AND vote_date BETWEEN %s AND %s
                        LIMIT 1",
                        $user_ip,
                        $start_of_week,
                        $end_of_week
                    ));

                
                    // On calcule le total des votes
                    $total_votes = array_sum(array_map(function($ippon) {
                        return (int)$ippon->votes_total;
                    }, $ippons));

                    // Formulaire de vote
                    $output .= '<div class="ippons-semaine">';
                    foreach ($ippons as $i) {
                        $judoka1 = get_post($i->judoka1_id);
                        $judoka2 = get_post($i->judoka2_id);
                        $equipe1 = get_post($i->equipe1_id);
                        $equipe2 = get_post($i->equipe2_id);
                        // Calcul du pourcentage
                        $percent = ($total_votes > 0) ? round(($i->votes_total / $total_votes) * 100, 1) : 0;


                        $image_judoka = get_the_post_thumbnail_url($i->judoka1_id) 
                            ? get_the_post_thumbnail_url($i->judoka1_id) 
                            : '/wp-content/uploads/2023/09/profil.jpg';

                        $image_equipe = get_field('logo_circle', $i->equipe1_id) 
                            ? get_field('logo_circle', $i->equipe1_id) 
                            : get_the_post_thumbnail_url($i->equipe1_id, "thumbnail");

                        $output .= '<div class="ippon">';

                        if ($i->video_url) {
                            $embed_url = ($i->video_url);
                            if ($embed_url != null) {

                                if (strpos($embed_url, 'watch?v=') !== false) {
                                    // URL classique YouTube
                                    $id = explode('watch?v=', $embed_url)[1];
                                } elseif (strpos($embed_url, 'youtu.be/') !== false) {
                                    // URL courte youtu.be
                                    $parts = explode('youtu.be/', $embed_url);
                                    $id = $parts[1];
                                } else {
                                    $id = ''; // URL non reconnue
                                }


                                $video_url='https://youtu.be/'.$id;


                                $overlay = '
                                <div class="ids-overlay">
                                    <div class="video video-grande-taille">'.
                                        do_shortcode('[video_popup url="'.$video_url.'" w="640" h="480" img="'.get_site_url().'/wp-content/uploads/2025/10/play-icon.svg"]').'
                                    </div>
                                </div>';
                            } else {
                                $overlay = '';
                            }
                            // $output .= '<iframe width="560" height="315" src="'.esc_url($embed_url).'" frameborder="0" allowfullscreen></iframe>';
                        }

                        $output .= '<div class="ids-judoka-wrapper">
                        <div class="ids-img-equipe">
                            <img class="jdk-semaine-home" src="'.$image_equipe.'" width="34px">
                        </div>
                            <img src="'.$image_judoka.'" class="ids-img-judoka">
                            '.$overlay.'
                        </div>';

                        $output .= '<span class="ids-judoka-title">'.esc_html($judoka1->post_title ?? '').'</span>';
                        $output .= '<p class="ids-opponement">'.esc_html($equipe1->post_title ?? '').' <br>vs '.esc_html($equipe2->post_title ?? '').'</p>';
                        
                        $is_voted = ($voted && intval($voted) === intval($i->id));
                        $output .= '<form class="ippon-vote-form">';
                        $output .= '<input type="hidden" name="ippon_id" value="'.intval($i->id).'">';

                        if ($voted) {
                            if ($is_voted) {
                                // ippon choisi par l'utilisateur
                                $output .= '<button type="button" class="ids-voter button-ippon-chosed" disabled>' . $percent . '%</button>';
                            } else {
                                // autres ippons -> désactivés
                                $output .= '<button type="button" class="ids-voter button-ippon-not-chosed" disabled>' . $percent . '%</button>';
                            }
                        } else {
                            // pas encore voté
                            $output .= '<button type="submit" class="ids-voter">Voter</button>';
                        }

                        $output .= '</form>';
                        

                        $output .= '</div>';
                    }
                    $output .= '</div>';
                    
                } else { 
                    ///si il n'a pas encore vote le formualaire de vote
                    // Formulaire de vote
                    $output .= '<div class="ippons-semaine">';
                    foreach ($ippons as $i) {
                        $judoka1 = get_post($i->judoka1_id);
                        $judoka2 = get_post($i->judoka2_id);
                        $equipe1 = get_post($i->equipe1_id);
                        $equipe2 = get_post($i->equipe2_id);

                        $image_judoka = get_the_post_thumbnail_url($i->judoka1_id) 
                            ? get_the_post_thumbnail_url($i->judoka1_id) 
                            : '/wp-content/uploads/2023/09/profil.jpg';

                        $image_equipe = get_field('logo_circle', $i->equipe1_id) 
                            ? get_field('logo_circle', $i->equipe1_id) 
                            : get_the_post_thumbnail_url($i->equipe1_id, "thumbnail");

                        $output .= '<div class="ippon">';

                        if ($i->video_url) {
                            $embed_url = ($i->video_url);
                            if ($embed_url != null) {

                                if (strpos($embed_url, 'watch?v=') !== false) {
                                    // URL classique YouTube
                                    $id = explode('watch?v=', $embed_url)[1];
                                } elseif (strpos($embed_url, 'youtu.be/') !== false) {
                                    // URL courte youtu.be
                                    $parts = explode('youtu.be/', $embed_url);
                                    $id = $parts[1];
                                } else {
                                    $id = ''; // URL non reconnue
                                }


                            $video_url='https://youtu.be/'.$id;


                                $overlay = '
                                <div class="ids-overlay">
                                    <div class="video video-grande-taille">'.
                                        do_shortcode('[video_popup url="'.$video_url.'" w="640" h="480" img="'.get_site_url().'/wp-content/uploads/2025/10/play-icon.svg"]').'
                                    </div>
                                </div>';
                            } else {
                                $overlay = '';
                            }
                            // $output .= '<iframe width="560" height="315" src="'.esc_url($embed_url).'" frameborder="0" allowfullscreen></iframe>';
                        }

                        $output .= '<div class="ids-judoka-wrapper">
                                        <div class="ids-img-equipe">
                                            <img class="jdk-semaine-home" src="'.$image_equipe.'" width="34px">
                                        </div>
                                        <img src="'.$image_judoka.'" class="ids-img-judoka">
                                        '.$overlay.'
                                    </div>';

                        $output .= '<span class="ids-judoka-title">'.esc_html($judoka1->post_title ?? '').'</span>';
                        $output .= '<p class="ids-opponement">'.esc_html($equipe1->post_title ?? '').'<br> vs '.esc_html($equipe2->post_title ?? '').'</p>';


                        // 🔥 Formulaire par ippon
                        $output .= '<form class="ippon-vote-form">';
                        $output .= '<input type="hidden" name="ippon_id" value="'.intval($i->id).'">';
                        $output .= '<button type="submit" class="ids-voter">Voter</button>';
                        $output .= '</form>';

                        $output .= '</div>';
                    }
                    $output .= '</div>';

                }

                // JS pour Ajax
                $output .= '<script>
                jQuery(document).ready(function($){
                    $(".ippon-vote-form").on("submit", function(e){
                        e.preventDefault();
                        var ippon_id = $(this).find("input[name=ippon_id]").val();
                        $.post("'.admin_url('admin-ajax.php').'", {
                            action:"prol_vote",
                            ippon_id: ippon_id,
                            security:"'.wp_create_nonce('prol_vote_nonce').'"
                        }, function(resp){
                            if(resp.success) location.reload();
                        });
                    });
                });
                </script>';
            }   
            else {
                // si on a pas de ippon cette semaine prendre les ippons termines
                $semaine_term = get_posts(array(
                    'numberposts' => 1,
                    'post_type'   => 'semaine',
                    'meta_key'    => 'date_de_debut',
                    'orderby'     => 'meta_value_num',
                    'order'       => 'DESC',
                    'meta_query'  => array(
                        array(
                            'key'     => 'termine',
                            'value'   => '1',
                            'compare' => '='
                        )
                    )
                ));
                $semaine_term_id = $semaine_curr[0]->ID;
                $last_ippons = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$this->table_ippons} WHERE semaine_id = %d",
                    $semaine_term_id
                ));
                // On calcule le total des votes
                $total_votes = array_sum(array_map(function($ippon) {
                    return (int)$ippon->votes_total;
                }, $last_ippons));
                //echo '<br>total votes : '.$total_votes;

                // Formulaire de vote
                $output .= '<div class="ippons-semaine">';
                foreach ($last_ippons as $i) {
                    $judoka1 = get_post($i->judoka1_id);
                    $judoka2 = get_post($i->judoka2_id);
                    $equipe1 = get_post($i->equipe1_id);
                    $equipe2 = get_post($i->equipe2_id);
                    // Calcul du pourcentage
                    $percent = ($total_votes > 0) ? round(($i->votes_total / $total_votes) * 100, 1) : 0;


                    $image_judoka = get_the_post_thumbnail_url($i->judoka1_id) 
                        ? get_the_post_thumbnail_url($i->judoka1_id) 
                        : '/wp-content/uploads/2023/09/profil.jpg';

                    $image_equipe = get_field('logo_circle', $i->equipe1_id) 
                        ? get_field('logo_circle', $i->equipe1_id) 
                        : get_the_post_thumbnail_url($i->equipe1_id, "thumbnail");

                    $output .= '<div class="ippon">';

                    if ($i->video_url) {
                        $embed_url = ($i->video_url);
                        if ($embed_url != null) {

                            if (strpos($embed_url, 'watch?v=') !== false) {
                                // URL classique YouTube
                                $id = explode('watch?v=', $embed_url)[1];
                            } elseif (strpos($embed_url, 'youtu.be/') !== false) {
                                // URL courte youtu.be
                                $parts = explode('youtu.be/', $embed_url);
                                $id = $parts[1];
                            } else {
                                $id = ''; // URL non reconnue
                            }
                            
                            $video_url='https://youtu.be/'.$id;


                            $overlay = '
                            <div class="ids-overlay">
                                <div class="video video-grande-taille">'.
                                    do_shortcode('[video_popup url="'.$video_url.'" w="640" h="480" img="'.get_site_url().'/wp-content/uploads/2025/10/play-icon.svg"]').'.
                                </div>
                            </div>';
                        } else {
                            $overlay = '';
                        }
                        // $output .= '<iframe width="560" height="315" src="'.esc_url($embed_url).'" frameborder="0" allowfullscreen></iframe>';
                    }

                    $output .= '<div class="ids-judoka-wrapper">
                    <div class="ids-img-equipe">
                        <img class="jdk-semaine-home" src="'.$image_equipe.'" width="34px">
                    </div>
                        <img src="'.$image_judoka.'" class="ids-img-judoka">
                        '.$overlay.'
                    </div>';

                    $output .= '<span class="ids-judoka-title">'.esc_html($judoka1->post_title ?? '').'</span>';
                    $output .= '<p class="ids-opponement">'.esc_html($equipe1->post_title ?? '').' <br>vs '.esc_html($equipe2->post_title ?? '').'</p>';
                    
                    $is_voted = ($voted && intval($voted) === intval($i->id));
                    $output .= '<form class="ippon-vote-form">';
                    $output .= '<input type="hidden" name="ippon_id" value="'.intval($i->id).'">';

                    
                    
                    // autres ippons -> désactivés
                    $output .= '<button type="button" class="ids-voter button-ippon-not-chosed" disabled>' . $percent . '%</button>';
                    
                    

                    $output .= '</form>';
                    

                    $output .= '</div>';
                }
               

            }
        } 
    


       


            

        

        $output .= '</div>';
        return $output;
    }


     // Shortcode front
    public function shortcode_ippons_semaine_historique_et_vainqueur() {
    global $wpdb;
    $table_ippons = $wpdb->prefix . 'ippons';

    // Récupérer toutes les semaines distinctes
   // On récupère le lundi de la semaine en cours (à minuit)
$lundi = date('Y-m-d 00:00:00', strtotime('monday this week'));

// Récupération des semaines disponibles
$semaines = get_posts(array(
    'numberposts' => -1,
    'post_type'   => 'semaine',
    'meta_key'    => 'date_de_debut',
    'orderby'     => 'meta_value_num',
    'order'       => 'ASC',
     'meta_query'  => array(
        array(
            'key'     => 'termine',
            'compare' => '=',
            'value'   => '1'
        )
    )
));

if (!$semaines) {
    return '<p>Aucune semaine disponible.</p>';
}

// Récupérer tous les ippons correspondants, classés par semaine et votes
$ippons = $wpdb->get_results($wpdb->prepare("
    SELECT *
    FROM $table_ippons
    ORDER BY semaine_id ASC, votes_total DESC
"));

// Regrouper les ippons par semaine_id
$grouped = [];
foreach ($ippons as $i) {
    $sid = $i->semaine_id;
    if (!isset($grouped[$sid])) $grouped[$sid] = [];
    $grouped[$sid][] = $i;
}


    // Générer le select des semaines
    $output = '<div class="ippons-historique">';
    $output .= '<select id="select-semaine">';
   
    foreach ($semaines as $s) {
        $key = $s->ID;
        $nom=get_field("nom",$s->ID);
        $output .= '<option value="'.esc_attr($key).'">'.$nom.'</option>';
        
    }
    $output .= '</select>';


    // 🔹 Affichage des ippons par semaine
    foreach ($grouped as $semaine_id => $list) {
        $visible = ($semaine_id === array_key_first($grouped)) ? 'block' : 'none'; // affiche la 1ère semaine
        $output .= '<div class="liste-ippons" data-semaine="'.esc_attr($semaine_id).'" style="display:'.$visible.';">';
        $output .= '<div class="ids-hist-grid">';

            // ----- GAGNANT -----
            $output .= '<div class="ids-hist-winner">';
                
                $ippon = $list[0]; // premier = top votes
                $judoka1 = get_post($ippon->judoka1_id);
                $judoka2 = get_post($ippon->judoka2_id);
                $equipe1 = get_post($ippon->equipe1_id);
                $equipe2 = get_post($ippon->equipe2_id);
                $embed_url = generate_embed_url($ippon->video_url);

                $image_judoka = get_the_post_thumbnail_url($ippon->judoka1_id) 
                    ? get_the_post_thumbnail_url($ippon->judoka1_id) 
                    : '/wp-content/uploads/2023/09/profil.jpg';

                $image_equipe = get_field('logo_circle', $ippon->equipe1_id) 
                    ? get_field('logo_circle', $ippon->equipe1_id) 
                    : get_the_post_thumbnail_url($ippon->equipe1_id, "thumbnail");

                $output .= '<div class="ippon-item">';
                    $output .= '<div class="ids-winner-title">';
                        $output .= '<div>';
                            $output .= '<span class="ids-judoka-title">'.esc_html($judoka1->post_title ?? '').'</span>';
                            $output .= '<span>'.esc_html($equipe1->post_title ?? '').' vs '.esc_html($equipe2->post_title ?? '').'</span>';
                        $output .= '</div>';
                    $output .= '</div>';
                $output .= '</div>';

                if ($embed_url) {
                    $output .= '<iframe width="560" height="315" src="'.esc_url($embed_url).'" frameborder="0" allowfullscreen></iframe>';
                }

            $output .= '</div>'; // .ids-hist-winner

            // ----- DÉTAILS -----
            $output .= '<div class="ids-hist-details">';

                // Calculs des votes
                $total_votes = array_sum(array_map(fn($i) => intval($i->votes_total), $list));
                $max_votes   = max(array_map(fn($i) => intval($i->votes_total), $list));

                foreach ($list as $ippon) {
                    $judoka1 = get_post($ippon->judoka1_id);
                    $judoka2 = get_post($ippon->judoka2_id);
                    $equipe1 = get_post($ippon->equipe1_id);
                    $equipe2 = get_post($ippon->equipe2_id);

                    $image_judoka = get_the_post_thumbnail_url($ippon->judoka1_id) 
                        ? get_the_post_thumbnail_url($ippon->judoka1_id) 
                        : '/wp-content/uploads/2023/09/profil.jpg';

                    $image_equipe = get_field('logo_circle', $ippon->equipe1_id) 
                        ? get_field('logo_circle', $ippon->equipe1_id) 
                        : get_the_post_thumbnail_url($ippon->equipe1_id, "thumbnail");

                    $votes = intval($ippon->votes_total);
                    $percent = ($total_votes > 0) ? round(($votes / $total_votes) * 100) : 0;
                    $bar_class = ($votes === $max_votes) ? 'progress-bar winner' : 'progress-bar';

                    // Affichage d'un ippon
                    $output .= '<div class="ids-details-grid">';
                        $output .= '<div class="ids-div-img">';
                            $output .= '<img src="'.$image_judoka.'" class="ids-img-judoka-litte">';
                            $output .= '<img class="ids-img-eq" src="'.$image_equipe.'" width="34px">';
                        $output .= '</div>';

                        $output .= '<div class="ids-details-title-total">';
                            $output .= '<div class="ids-details-title">';
                                $output .= '<span class="ids-judoka-title">'.esc_html($judoka1->post_title ?? '').'</span>';
                            $output .= '</div>';
                            $output .= '<div class="ids-details-total">';
                                $output .= '<div class="progress">';
                                    $output .= '<div class="'.$bar_class.'" style="width:'.$percent.'%;">'.$percent.'%</div>';
                                $output .= '</div>';
                            $output .= '</div>';
                        $output .= '</div>';
                    $output .= '</div>'; // .ids-details-grid
                }

            $output .= '</div>'; // .ids-hist-details
        $output .= '</div>'; // .ids-hist-grid
    $output .= '</div>'; // .liste-ippons
    }

    $output .= '</div>'; // conteneur global

    // ----- JS : changement de semaine -----
    $output .= '<script>
    jQuery(document).ready(function($){
        $("#select-semaine").hide();

        $(".ids-winner-title").each(function(){
            var clone = $("#select-semaine").clone()
                .removeAttr("id")
                .addClass("select-semaine")
                .show();
            $(this).append(clone);
        });

        $(document).on("change", ".select-semaine", function(){
            var val = $(this).val();
            $(".liste-ippons").hide();
            $(".liste-ippons[data-semaine=\'" + val + "\']").show();
            $(".select-semaine").val(val);
        });
    });
    </script>';



    return $output;
}


    // Ajax vote
    public function handle_vote() {
        check_ajax_referer('prol_vote_nonce','security');

        global $wpdb;
        $user_ip = '';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $user_ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $user_ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]; // prend la 1ère IP
        } else {
            $user_ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
        $user_role = wp_get_current_user()->roles[0] ?? 'none';
        $ippon_id = intval($_POST['ippon_id']);

        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_votes} WHERE ippon_id=%d AND user_ip=%d",
            $ippon_id, $user_ip
        ));

        if($exists){
            wp_send_json_error(['message'=>'Vous avez déjà voté.']);
        } 

        $wpdb->insert($this->table_votes, [
            'ippon_id' => $ippon_id,
            'user_ip' => $user_ip,
        ]);

        // Met à jour le total de votes
        $wpdb->query($wpdb->prepare(
            "UPDATE {$this->table_ippons} SET votes_total = votes_total + 1 WHERE id=%d",
            $ippon_id
        ));

        wp_send_json_success(['message'=>'Vote enregistré !']);
    }

   
}

new IpponSemaine();
