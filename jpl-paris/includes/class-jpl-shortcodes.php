<?php
class JPL_Shortcodes {
    public static function register_shortcodes() {
        add_shortcode('profil_joueur', [__CLASS__, 'render_profil']);
        add_action('template_redirect', [__CLASS__, 'handle_update']); // traitement POST

         // Actions AJAX
        add_action('wp_ajax_get_player_popup', [__CLASS__, 'get_player_popup']);
        add_action('wp_ajax_nopriv_get_player_popup', [__CLASS__, 'get_player_popup']);

        add_shortcode('jpl_verify_email', function() {
            if(isset($_GET['verify_email'], $_GET['uid'])){
                $uid = intval($_GET['uid']);
                $token = sanitize_text_field($_GET['verify_email']);
                $saved_token = get_user_meta($uid, 'email_verification_token', true);

                if($saved_token && $saved_token === $token){
                    update_user_meta($uid, 'email_verified', 1);
                    delete_user_meta($uid, 'email_verification_token');
                     wp_redirect(home_url('judopronoschallenge/?vstatus=validation_succeed'));
                    return '<p>Votre email a été validé ! Vous pouvez maintenant vous connecter.</p>';
                   
                } else {
                     wp_redirect(home_url('judopronoschallenge/?vstatus=validation_failled'));
                    return '<p>Lien de validation invalide ou expiré.</p>';
                }
            }
            return '<p>Lien de validation manquant.</p>';
        });
    }

    

    public static function email_to_pseudo($email) {
        // Partie avant @
        $pseudo = explode('@', $email)[0];

        // Remplacer les points par des espaces
        $pseudo = str_replace('.', ' ', $pseudo);

        // Limiter à 30 caractères
        if(mb_strlen($pseudo) > 15){
            $pseudo = mb_substr($pseudo, 0, 15) . '...';
        }

        return $pseudo;
    }

    // Affichage profil + formulaire
    public static function render_profil($atts) {
        if (!is_user_logged_in()) {
            return '<p>Veuillez vous connecter pour voir votre profil.</p>';
        }

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
        $user_id = get_current_user_id();
        $user = wp_get_current_user();

        $pseudo = get_field('pseudo', 'user_'.$user_id) ?: $user->display_name;
        $prenom = get_user_meta($user_id, 'first_name', true);
        $nom = get_user_meta($user_id, 'last_name', true);
        $avatar_id = get_field('avatar', 'user_'.$user_id);
        $avatar_url = ($avatar_id)?$avatar_id:"/wp-content/uploads/2025/08/user-icon.png";
        $equipe_user_id  = get_field('equipe', 'user_'.$user_id);
        $points = get_field('total_de_points', 'user_'.$user_id) ?: 0;
        $serie = get_field('serie_en_cours', 'user_'.$user_id) ?: 0;

        ob_start(); ?>
        <div class="jpl-profil">
            
            <h3><?php echo esc_html($pseudo); ?></h3>
            <p><strong>Prénom :</strong> <?php echo esc_html($prenom); ?></p>
            <p><strong>Nom :</strong> <?php echo esc_html($nom); ?></p>
            <p><strong>Points :</strong> <?php echo intval($points); ?></p>
            <p><strong>Série en cours :</strong> <?php echo intval($serie); ?></p>

            <h4>Modifier votre profil :</h4>
            <form method="post" enctype="multipart/form-data">
                <p>
                    <label>Nouveau pseudo :</label><br>
                    <input type="text" name="jpl_new_pseudo" value="<?php echo esc_attr($pseudo); ?>" required>
                </p>
                <p>
                    <label>Prénom :</label><br>
                    <input type="text" name="jpl_new_first_name" value="<?php echo esc_attr($prenom); ?>">
                </p>
                
                <p>
                    <label>Nom :</label><br>
                    <input type="text" name="jpl_new_last_name" value="<?php echo esc_attr($nom); ?>">
                </p>
                    <p>
                    <label>Choisis ton équipe :</label><br>
                    <select name="team_id" required>
                        <option value="">-- Sélectionne une équipe --</option>
                        <?php foreach ($equipes as $equipe):
                            $id = $equipe->ID;
                            $title = get_the_title($id);
                            $selected = ($id == $equipe_user_id) ? 'selected' : '';
                        ?>
                        <option value="<?php echo $id; ?>" <?php echo $selected; ?>>
                            <?php echo esc_html($title); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </p>
                <p>
                    <label>Nouvel avatar :</label><br>
                    <input type="file" name="jpl_new_avatar" accept="image/*" onchange="document.getElementById('jpl-avatar-preview').src = window.URL.createObjectURL(this.files[0])">
                </p>
                <p>
                    <button type="submit" name="jpl_update_profile">Mettre à jour</button>
                </p>
            </form>




        </div>

        <?php
        return ob_get_clean();
    }

    // Traitement POST
    public static function handle_update() {
        if (!isset($_POST['jpl_update_profile']) || !is_user_logged_in()) return;

        $user_id = get_current_user_id();

        // Pseudo
        if (!empty($_POST['jpl_new_pseudo'])) {
            $new_pseudo = sanitize_text_field($_POST['jpl_new_pseudo']);
            update_field('pseudo', $new_pseudo, 'user_'.$user_id);
            wp_update_user(['ID' => $user_id, 'display_name' => $new_pseudo]);
        }

        // Prénom et Nom
        if (!empty($_POST['jpl_new_first_name'])) {
            update_user_meta($user_id, 'first_name', sanitize_text_field($_POST['jpl_new_first_name']));
        }
        if (!empty($_POST['jpl_new_last_name'])) {
            update_user_meta($user_id, 'last_name', sanitize_text_field($_POST['jpl_new_last_name']));
        }

        if (!empty($_POST['team_id'])) {
            $team_id = intval($_POST['team_id']);
            update_field('field_equipe', $team_id, 'user_' . $user_id);
        }

        // Avatar
        if (!empty($_FILES['jpl_new_avatar']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');

            $attachment_id = media_handle_upload('jpl_new_avatar', 0);
            if (!is_wp_error($attachment_id)) {
                update_field('avatar', $attachment_id, 'user_'.$user_id); // on stocke l'ID pour ACF

            }
        }

        wp_redirect(get_permalink());
        exit;
    }


    public static function get_player_popup() {
        $user_id = intval($_POST['user_id'] ?? 0);
        $user_info = get_userdata($user_id);

        $pseudo = get_field('pseudo', 'user_'.$user_id) ?: $user_info->display_name;
        $prenom = get_user_meta($user_id, 'first_name', true);
        $nom = get_user_meta($user_id, 'last_name', true);
        if (empty($prenom) && empty($nom)) {
            // Si vide, assigner un pseudo
            $pseudo = self::email_to_pseudo($pseudo);   // Mettre un pseudo par défaut si nécessaire
        }else {
            // Sinon, concaténer prénom et nom
            $pseudo = $prenom . ' ' . $nom;
        }
        $score_exact = (int) get_field('score_exact', 'user_' . $user_id);
        $serie_en_cours = (int) get_field('serie_en_cours', 'user_' . $user_id);
        $meilleure_serie = (int) get_field('meilleure_serie', 'user_' . $user_id);
        $classement = (int) get_field('classement', 'user_' . $user_id);
        $paris_effectues = (int) get_field('paris_effectues', 'user_' . $user_id);
        $paris_gagnes = (int) get_field('paris_gagnes', 'user_' . $user_id);
        $total_de_points = (int) get_field('total_de_points', 'user_' . $user_id);
        $ratio = $paris_effectues > 0  ? ceil($paris_gagnes *100/ $paris_effectues)   : 0;

        if (!$user_id) {
            echo "<p>Utilisateur introuvable.</p>";
            wp_die();
        }

        $user_info = get_userdata($user_id);
        if (!$user_info) {
            echo "<p>Aucun joueur trouvé.</p>";
            wp_die();
        }

        echo "<h3>Profil de $pseudo </h3>";
        echo "<p>Points : $total_de_points </p>";
        echo "<p>Classement : $classement </p>";
        echo "<p>Paris effectués : $paris_effectues </p>";
        echo "<p>Paris gagnés : $paris_gagnes </p>";
        echo "<p>Ratio : $ratio %</p>";
        echo "<p>Série en cours : $meilleure_serie </p>";
        echo "<p>Scores exacts : $score_exact </p>";

        wp_die();
    }
}

// Initialisation
add_action('plugins_loaded', ['JPL_Shortcodes', 'register_shortcodes']);

