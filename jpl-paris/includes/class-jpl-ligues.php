<?php

class JPL_Ligues {


 

    public static function init() {
        add_shortcode('form_creer_ligue', [__CLASS__, 'render_creer_ligue']);
        add_shortcode('form_rejoindre_ligue', [__CLASS__, 'render_rejoindre_ligue']);

        
        add_action('init', [__CLASS__, 'handle_form']);

        // ✅ Action AJAX pour utilisateurs connectés
        add_action('wp_ajax_update_ligue_title', [__CLASS__, 'update_ligue_title_callback']);
    }


     // Méthode callback AJAX
    public static function update_ligue_title_callback() {
        // Vérification du nonce
        if (empty($_POST['ligue_nonce']) || !wp_verify_nonce($_POST['ligue_nonce'], 'update_ligue_nonce')) {
            wp_send_json_error('Nonce invalide');
        }

        $post_id = intval($_POST['post_id'] ?? 0);
        $new_title = sanitize_text_field($_POST['new_title'] ?? '');

        if (!$post_id || empty($new_title)) {
            wp_send_json_error('Paramètres invalides');
        }

    

        // Met à jour le titre du post
        $updated_post_id = wp_update_post([
            'ID' => $post_id,
            'post_title' => $new_title
        ], true);

        if (is_wp_error($updated_post_id)) {
            wp_send_json_error('Erreur lors de la mise à jour');
        }

        // Met à jour le champ ACF si besoin
        update_field('nom', $new_title, $post_id);

        wp_send_json_success([
            'new_title' => $new_title
        ]);
    }



    public static function render_creer_ligue() {
        if (!is_user_logged_in()) {
            return '<p>Veuillez vous connecter.</p>';
        }
        ob_start(); ?>
        <form method="post" enctype="multipart/form-data" class="pari-form">
            <div class="col-2-form">
                <label for="ligue_nom">Comment tu veux l'appeler ?</label>
                <input type="text" name="ligue_nom" required>
            </div>
            <div class="col-2-form">
                <label for="ligue_logo">Un petit symbole ?</label>
                <input type="file" name="ligue_logo" accept="image/*">
            </div>
            <div class="col-2-form">
                <div>
                    <label for="ligue_status">
                        Est-ce qu'on ouvre ta ligue :
                        <span class="info-bulle" title="Sélectionnez 'Ouvert' pour permettre aux joueurs de rejoindre la ligue, 'Fermé' pour la verrouiller.">ℹ️</span>

                    </label>
                </div>
                <div>
                    <input type="radio" id="status_ferme" name="ligue_status" value="ferme">
                    <label for="status_ferme">Non, on reste entre potes</label><br>
                    <input type="radio" id="status_ouvert" name="ligue_status" value="ouvert" checked>
                    <label for="status_ouvert">oui, plus on est de fous...</label><br>
                </div>
            </div>
            
            <input type="hidden" name="action" value="creer_ligue">
            <?php wp_nonce_field('creer_ligue_nonce', 'ligue_nonce'); ?>
            <button type="submit">Créer</button>
        </form>
        <?php return ob_get_clean();
    }

    public static function render_rejoindre_ligue() {
        if (!is_user_logged_in()) {
            return '<p>Veuillez vous connecter.</p>';
        }
        $user_id = get_current_user_id();
        // récupérer les ligues existantes
        $ligues = get_posts([
            'post_type' => 'ligue',
            'numberposts' => -1,
            'meta_query' => array(
                array(
                    'key'     => 'createur', // Replace with your field name
                    'value'   =>  $user_id , // Serialize the ID to match the stored format
                     'compare' => '!=', // Exclure les posts dont le createur == user_id
                    'type'    => 'NUMERIC', // très important si c'est un ID
                ),
            ),
        ]);
      

        ob_start(); ?>
        <form method="post">
            <label>Choisir une ligue</label>
            <select name="ligue_id" required>
                <?php foreach($ligues as $ligue): ?>
                    <option value="<?php echo $ligue->ID; ?>">
                        <?php echo esc_html($ligue->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input type="hidden" name="action" value="rejoindre_ligue">
            <?php wp_nonce_field('rejoindre_ligue_nonce', 'ligue_nonce'); ?>
            <br>
            <button type="submit">Rejoindre</button>
        </form>
        <?php return ob_get_clean();
    }

    public static function handle_form() {
        if (empty($_POST['action']) || !isset($_POST['ligue_nonce'])) return;

        if ($_POST['action'] === 'creer_ligue' && wp_verify_nonce($_POST['ligue_nonce'], 'creer_ligue_nonce')) {
            $user_id = get_current_user_id();
            $post_id = wp_insert_post([
                'post_type' => 'ligue',
                'post_title' => sanitize_text_field($_POST['ligue_nom']),
                'post_content' => sanitize_textarea_field($_POST['ligue_nom']),
                'post_status' => 'publish',
            ]);
            if ($post_id) {
                update_field('nom', sanitize_text_field($_POST['ligue_nom']), $post_id);
                update_field('status', ($_POST['ligue_status']), $post_id);
                update_field('description', sanitize_textarea_field($_POST['ligue_nom']), $post_id);
                update_field('createur', $user_id, $post_id);
                // ✅ Gestion du champ image "logo"
                if (!empty($_FILES['ligue_logo']['name'])) {
                    require_once(ABSPATH . 'wp-admin/includes/file.php');
                    require_once(ABSPATH . 'wp-admin/includes/media.php');
                    require_once(ABSPATH . 'wp-admin/includes/image.php');

                    $attachment_id = media_handle_upload('ligue_logo', $post_id);
                    if (!is_wp_error($attachment_id)) {
                        update_field('logo', $attachment_id, $post_id);
                    }else{
                    }
                }

            }
        }

        if ($_POST['action'] === 'rejoindre_ligue' && wp_verify_nonce($_POST['ligue_nonce'], 'rejoindre_ligue_nonce')) {
            $user_id  = get_current_user_id();
            $ligue_id = intval($_POST['ligue_id']);
            $user_id = intval($_POST['user_id']);
            // Récupérer l'objet utilisateur
            $user = get_userdata($user_id);
            $user_name = $user ? $user->display_name : 'Utilisateur '.$user_id;

            // Récupérer le nom de la ligue
            $ligue_title = get_the_title($ligue_id);

            // Créer le post "demande"
            $demande_id = wp_insert_post([
                'post_type'   => 'demande',
                'post_title'  => 'Demande de '.$user_name.' pour la ligue '.$ligue_title,
                'post_status' => 'publish'
            ]);

            if ($demande_id) {
                 // champs ACF
                update_field('date', current_time('mysql'), $demande_id);
                update_field('parieur', $user_id, $demande_id);
                update_field('ligue', $ligue_id, $demande_id);
                update_field('status', 'en_attente', $demande_id);
                 // envoyer mail au créateur
                $createur_id = get_field('createur', $ligue_id);
                 $createur_user = get_user_by('ID', $createur_id);
                
                $parieur_user = get_user_by('ID', $user_id);

                   // Définir l'expéditeur
                    add_filter( 'wp_mail_from', function( $email ) {
                        return 'contact@judoproleague.com';
                    });
                    add_filter( 'wp_mail_from_name', function( $name ) {
                        return 'Judo Pro League';
                    });
                if ($createur_user) {
                    $createur_prenom = get_user_meta($createur_user->ID, 'first_name', true);
                    $parieur_prenom = get_user_meta($parieur_user->ID, 'first_name', true);
                  
                    self::jpl_envoyer_mail(
                        $parieur_user->user_email,
                        "Ta demande d’accès est bien enregistrée",
                        $parieur_prenom, // prenom
                        "Un utilisateur souhaite rejoindre ta ligue " . get_the_title($ligue_id) . ".\n
                        Sa demande est en attente de ta validation.\n
                        Tu peux gérer les demandes depuis ton espace Ligue.\n
                        <a href='https://judoproleague.com/module-de-paris-home/' style='background:#0073aa;color:#fff;padding:10px 15px;text-decoration:none;border-radius:5px;'>Gérer les demandes d’accès</a>
                        \n\n
                        — L’équipe Judo Pronos Challenge"
                    );

                    // Mail au créateur
                    self::jpl_envoyer_mail(
                        $createur_user->user_email,
                        "Nouvelle demande pour rejoindre ta ligue",
                        $createur_prenom, // prenom du créateur
                        "Ta demande pour rejoindre la ligue " . get_the_title($ligue_id) . " a bien été envoyée.\n
                        Elle est désormais entre les mains de l’administrateur de la ligue.\n
                        Tu recevras un mail dès qu’il aura validé ton entrée.\n
                        \n\n
                        — L’équipe Judo Pronos Challenge"
                    );
                }

               

            } else {
                echo "<p style='color:red'>Erreur lors de la création de la demande.</p>";
            }
        }


        

    }


    

    // Fonction helper pour envoyer un mail HTML personnalisé
    public static function jpl_envoyer_mail($to, $subject, $pseudo, $message) {
        $headers = array('Content-Type: text/html; charset=UTF-8');

        $body = '
        <div style="font-family:Arial,sans-serif; font-size:14px; color:#333;">
            <p>Salut ' . esc_html($pseudo) . ',</p>
            
            <p>' . nl2br(wp_kses_post($message)) . '</p>

            <p>Cordialement,</p>
            <p>L\'équipe Judo Pro League</p>
            
            <p>
                <img src="https://judoproleague.com/wp-content/uploads/2023/07/logo-jpl.png" 
                    alt="Logo Judo Pro League" 
                    style="max-width:150px; margin-top:10px;" />
            </p>
        </div>
        ';

        wp_mail($to, $subject, $body, $headers);
    }
}


// Initialisation
add_action('plugins_loaded', ['JPL_Ligues', 'init']);
