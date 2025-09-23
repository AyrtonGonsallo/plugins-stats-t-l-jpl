<?php

class JPL_Auth {
    public static function init() {
        // On hooke avant l'affichage pour traiter les POST
        add_action('template_redirect', [__CLASS__, 'handle_post']);
        add_action('wp_ajax_check_email_exists', [__CLASS__, 'check_email_exists']);
        add_action('wp_ajax_nopriv_check_email_exists', [__CLASS__, 'check_email_exists']);
    }

    // Fonction helper pour envoyer un mail HTML personnalisé
    public static function jpl_envoyer_mail($to, $subject, $pseudo, $message) {
        $headers = array('Content-Type: text/html; charset=UTF-8');

        $body = '
        <div style="font-family:Arial,sans-serif; font-size:14px; color:#333;">
            
            <p>' . nl2br(wp_kses_post($message)) . '</p>

            <p>
                <img src="https://judoproleague.com/wp-content/uploads/2023/07/logo-jpl.png" 
                    alt="Logo Judo Pro League" 
                    style="max-width:150px; margin-top:10px;" />
            </p>
        </div>
        ';

        wp_mail($to, $subject, $body, $headers);
    }

    public static function handle_post() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return; // ne rien faire si pas POST
        }

        $action = sanitize_text_field($_POST['jpl_auth_action'] ?? '');

        switch ($action) {
            case 'login':
                self::handle_login();
                break;

            case 'register':
                self::handle_register();
                break;

            case 'logout':
                self::logout();
                break;

            case 'forgot':
                self::handle_forgot();
                break;

            default:
                // pas un formulaire du plugin → on ignore
                return;
        }
    }

    private static function handle_login() {
        $email = sanitize_email($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $user = wp_authenticate($email, $password);
        if (is_wp_error($user)) {
            wp_redirect(home_url('judopronoschallenge/?error=conn'));
            exit;
        }
        $email_verified = get_user_meta($user->ID, 'email_verified', true);
        if (!$email_verified) {
            wp_redirect(home_url('judopronoschallenge/?error=no_verified'));
            exit;
        }

        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);
        wp_redirect(home_url('/module-de-paris-home'));
        exit;
    }

     private static function logout() {
        wp_logout();
        wp_redirect(home_url()); // redirection après logout
            
        exit;
    }

    public static function check_email_exists() {
    // Nettoyage des entrées
    $email  = sanitize_email($_POST['email'] ?? '');
    $pseudo = sanitize_user($_POST['pseudo'] ?? '');

    $email_exists  = false;
    $pseudo_exists = false;

    // Vérifier l'email
    if (!empty($email) && get_user_by('email', $email)) {
        $email_exists = true;
    }

    // Vérifier le pseudo (login / username)
    if (!empty($pseudo) && username_exists($pseudo)) {
        $pseudo_exists = true;
    }

    // Retour JSON
    wp_send_json([
        'email_exists'  => $email_exists,
        'pseudo_exists' => $pseudo_exists,
    ]);
}


    private static function handle_register() {
        $email = sanitize_email($_POST['email'] ?? '');
        $pseudo = sanitize_text_field($_POST['pseudo'] ?? '');
        $pass1 = $_POST['password'] ?? '';
        $pass2 = $_POST['password2'] ?? '';
        $team_id = intval($_POST['team_id'] ?? 0); // récupérer le team_id

        // Nouveaux champs
        $nom      = sanitize_text_field($_POST['nom'] ?? '');
        $prenom   = sanitize_text_field($_POST['prenom'] ?? '');
        $date_naissance = sanitize_text_field($_POST['date_naissance'] ?? '');
        $newsletter     = !empty($_POST['newsletter']) ? 1 : 0;
        $offres         = !empty($_POST['offres']) ? 1 : 0;
        $consentement   = !empty($_POST['consentement_utilisation_de_donnees']) ? 1 : 0;

        if ($pass1 !== $pass2) {
            //wp_die("Les mots de passe ne correspondent pas.");
            wp_redirect(home_url('judopronoschallenge/?error=pass_match'));
            exit;
        }

        // Vérif consentement obligatoire
        if (!$consentement) {
            wp_redirect(home_url('judopronoschallenge/?error=consent'));
            exit;
        }


        $user_id = wp_create_user($pseudo, $pass1, $email);
        if (is_wp_error($user_id)) {
           // wp_die("Erreur à l'inscription : " . $user_id->get_error_message());
            wp_redirect(home_url('judopronoschallenge/?error=insc'));
            exit;
        }
        // Assigner le rôle personnalisé
        $user = new WP_User($user_id);
        $user->set_role('joueur_jpl'); // <-- rôle personnalisé

         // Associer l'utilisateur à l'équipe via ACF
        if ($team_id) {
            update_field('field_equipe', $team_id, 'user_' . $user_id);
        }
         // Sauvegarder les champs ACF du profil joueur
        update_field('field_nom', $nom, 'user_' . $user_id);
        update_field('field_prenom', $prenom, 'user_' . $user_id);
        update_field('field_date_naissance', $date_naissance, 'user_' . $user_id);
        update_field('field_newsletter', $newsletter, 'user_' . $user_id);
        update_field('field_offres', $offres, 'user_' . $user_id);
        update_field('field_consentement_donnees', $consentement, 'user_' . $user_id);
        update_field('field_email_verified', 0, 'user_' . $user_id);

      //  wp_set_current_user($user_id);
      //  wp_set_auth_cookie($user_id);

      // --- Générer token unique pour validation email ---
    $token = wp_generate_password(20, false);
    update_user_meta($user_id, 'email_verification_token', $token);
     // --- Construire le lien de validation ---
    $verify_link = add_query_arg([
        'verify_email' => $token,
        'uid' => $user_id
    ], home_url('/verification-email/')); // <-- créer cette page / shortcode

        // Définir l'expéditeur
                    add_filter( 'wp_mail_from', function( $email ) {
                        return 'contact@judoproleague.com';
                    });
                    add_filter( 'wp_mail_from_name', function( $name ) {
                        return 'Judo Pro League';
                    });

        // Mail au créateur
        self::jpl_envoyer_mail(
            $email,
            "Bienvenue dans l’aventure Judo Pronos Challenge",
            $pseudo, // pseudo du créateur
            "Salut " .$prenom. ",\n
            Ton compte Judo Pronos Challenge vient d’être créé avec succès.\n
            Il ne te reste plus qu’une étape pour rejoindre l’aventure : confirme ton adresse email et commence à faire tes pronos dès maintenant.\n
            Sans validation, tu ne pourras pas accéder à ton compte.\n
            <a href='{$verify_link}' style='background:#0073aa;color:#fff;padding:10px 15px;text-decoration:none;border-radius:5px;'>Valider mon email</a>

            \n\n\n
            À très vite sur les tatamis virtuels !\n
            — L’équipe Judo Pronos Challenge\n"
        );

         wp_redirect(home_url('judopronoschallenge/?error=created_and_no_verified'));
        exit;
    }

    private static function handle_forgot() {
        $email = sanitize_email($_POST['email'] ?? '');
        if (!email_exists($email)) {
            wp_redirect(home_url('judopronoschallenge/?error=no_email'));
            exit;
        }

        $user = get_user_by('email', $email);

        // Ajouter filtres spécifiques AVANT l'appel
        add_filter('wp_mail_from', [self::class, 'custom_mail_from']);
        add_filter('wp_mail_from_name', [self::class, 'custom_mail_from_name']);
        add_filter('wp_mail_content_type', [self::class, 'custom_mail_content_type']);
        add_filter('retrieve_password_title', [self::class, 'custom_reset_title'], 10, 3);
        add_filter('retrieve_password_message', [self::class, 'custom_reset_message'], 10, 4);

        // Déclenche l'email standard WP (mais filtré uniquement ici)
        retrieve_password($user->user_login);

        // Supprimer filtres pour ne pas polluer les autres mails
        remove_filter('wp_mail_from', [self::class, 'custom_mail_from']);
        remove_filter('wp_mail_from_name', [self::class, 'custom_mail_from_name']);
        remove_filter('wp_mail_content_type', [self::class, 'custom_mail_content_type']);
        remove_filter('retrieve_password_title', [self::class, 'custom_reset_title']);
        remove_filter('retrieve_password_message', [self::class, 'custom_reset_message']);

        wp_redirect(home_url('judopronoschallenge/?forgot_status=sent'));
        exit;
    }

    public static function custom_mail_from($email) {
    return 'contact@judoproleague.com';
}

public static function custom_mail_from_name($name) {
    return 'Judo Pro League';
}

public static function custom_mail_content_type() {
    return 'text/html';
}

public static function custom_reset_title($title, $user_login, $user_data) {
    return 'Réinitialise ton mot de passe';
}

public static function custom_reset_message($message, $key, $user_login, $user_data) {
    $reset_url = esc_url(get_site_url()).'/reset-password/';
     // Récupérer le prénom
    $prenom = get_user_meta($user_data->ID, 'first_name', true);

    // Si pas de prénom renseigné, fallback sur login
    if (empty($prenom)) {
        $prenom = $user_login;
    }

   return '
    <div style="font-family:Arial,sans-serif; font-size:14px; color:#333;">
        <p>Salut ' . esc_html($prenom) . ',</p>
        <p>Tu viens de demander à réinitialiser ton mot de passe.</p>
        <p>Pas de panique, ça arrive même aux meilleurs combattants.</p>
        <p>Clique sur le bouton ci-dessous pour créer ton nouveau mot de passe et retrouver ton compte.</p>
        <p><a href="' . esc_url($reset_url) . '" style="background:#0073aa;color:#fff;padding:10px 15px;text-decoration:none;border-radius:5px;">Créer un nouveau mot de passe</a></p>
        <p>Si tu n’es pas à l’origine de cette demande, ignore simplement ce message.</p>
        <br><br>
        <p>— L’équipe Judo Pronos Challenge</p>
        <p><img src="https://judoproleague.com/wp-content/uploads/2023/07/logo-jpl.png" alt="Logo" style="max-width:150px; margin-top:10px;" /></p>
    </div>
';

}

}




JPL_Auth::init();
