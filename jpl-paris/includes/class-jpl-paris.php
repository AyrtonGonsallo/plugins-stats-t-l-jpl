<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class JPL_Paris {

    public static function init() {
        // Ajax connecté
        add_action('wp_ajax_enregistrer_pari', [__CLASS__, 'enregistrer_pari']);
        // Ajax non connecté
        add_action('wp_ajax_nopriv_enregistrer_pari', [__CLASS__, 'enregistrer_pari']);

        // Cron déclenché par URL
        add_action('init', [__CLASS__, 'cron_update_paris']);
    }

  public static function normaliser_vainqueur($valeur) {
    // retire les espaces, accents, passe en minuscule
    $valeur = str_replace(' ', '', $valeur);   // supprime espaces
    $valeur = strtolower($valeur);             // minuscule
    $valeur = str_replace(['é','è','ê'], 'e', $valeur); // option : enlever accents
    return $valeur;
}

    /**
     * ⚡️ CRON : Met à jour les paris terminés
     * Appel via URL : http://rimo0631.odns.fr/?cron_update_paris=1&key=SECRET123
     */
    public static function cron_update_paris() {
        if (!isset($_GET['cron_update_paris'])) {
            return;
        }

        // 🔒 Sécurité : clé secrète
        $secret = 'SECRET123'; // <-- change la valeur par un mot de passe fort
        if (!isset($_GET['key']) || $_GET['key'] !== $secret) {
            wp_die('Accès refusé ❌');
        }

        // 1️⃣ Récupérer toutes les rencontres terminées
        $args = [
            'post_type'      => 'rencontre',
            'posts_per_page' => -1,
            'meta_key'       => 'date_de_debut',
            'orderby'        => 'meta_value_num',
            'order'          => 'DESC',
            'meta_query'     => [
                'relation' => 'AND',
                [
                    'key'     => 'statut', // remplace par ton champ ACF exact
                    'value'   => 'terminé',
                    'compare' => 'LIKE'
                ],
                [
                    'key'     => 'saisons',
                    'value'   => '2025-2026',
                    'compare' => 'LIKE'
                ]
            ]
        ];

        $rencontres = new WP_Query($args);

        foreach ($rencontres->posts as $rencontre) {
            $combat        = get_field('les_combat', $rencontre->ID)[0];
            $score_equipe1 = $combat['nombre_de_combat_gagne_equipe_1'][0];
            $score_equipe2 = $combat['nombre_de_combat_gagne_equipe_2'][0];
            $winner        = self::normaliser_vainqueur($combat['equipe_gagnante']);

            // 2️⃣ Récupérer les paris liés encore "a_venir"
            $paris_en_cours = get_posts([
                'post_type'      => 'pari',
                'posts_per_page' => -1,
                'orderby'        => 'ID',
                'order'          => 'ASC',
                'meta_query'     => [
                    'relation' => 'AND',
                    [
                        'key'     => 'rencontre',
                        'value'   => $rencontre->ID,
                        'compare' => 'LIKE'
                    ],
                    [
                        'key'     => 'status',
                        'value'   => 'a_venir',
                        'compare' => 'LIKE'
                    ]
                ]
            ]);

            // 3️⃣ Calcul des points
            foreach ($paris_en_cours as $pari) {
                $pari_vainqueur = get_field('vainqueur', $pari->ID);
                $pari_score1    = get_field('score_equipe_1', $pari->ID);
                $pari_score2    = get_field('score_equipe_2', $pari->ID);
                $bonus          = get_field('bonus_applique', $pari->ID);

                $points = 0;
                $resultat = 'pas_joue'; // valeur par défaut

                // ✅ Vainqueur correct ?
                $vainqueur_ok = ($pari_vainqueur == $winner);

                // ✅ Score correct ?
                $score_ok = ($pari_score1 == $score_equipe1 && $pari_score2 == $score_equipe2);

                // Déterminer les points et le résultat
                if ($score_ok && $vainqueur_ok) {
                    $points = 30; // 10 vainqueur + 20 score
                    $resultat = 'score_et_vainqueur_juste';
                } elseif ($score_ok) {
                    $points = 20;
                    $resultat = 'score_juste';
                } elseif ($vainqueur_ok) {
                    $points = 10;
                    $resultat = 'vainqueur_juste';
                } else {
                    $points = 0;
                    $resultat = 'tout_perdu';
                }

                // ✅ Appliquer bonus
                if ($bonus == 'x2') {
                    $points *= 2;
                } elseif ($bonus == 'x3') {
                    $points *= 3;
                }

                // 4️⃣ Mise à jour du pari
                update_field('status', 'calcule', $pari->ID);
                update_field('score_final_equipe_1', $score_equipe1, $pari->ID);
                update_field('score_final_equipe_2', $score_equipe2, $pari->ID);
                update_field('points_obtenus', $points, $pari->ID);
                update_field('resultats', $resultat, $pari->ID); // ⚡ ici

                // 🟢 Debug visible
                echo "<pre>";
                echo "ID Pari : {$pari->ID}\n";
                echo "Score prédit : {$pari_score1} - {$pari_score2}\n";
                echo "Vainqueur prédit : {$pari_vainqueur}\n";
                echo "Score réel : {$score_equipe1} - {$score_equipe2}\n";
                echo "Vainqueur réel : {$winner}\n";
                echo "Bonus : {$bonus}\n";
                echo "Points obtenus : {$points}\n";
                echo "Résultat : {$resultat}\n";
                echo "---------------------------\n";
                echo "</pre>";
            }

        }


       

        wp_die("CRON terminé ✅");
    }

    /**
     * ⚡️ CRON : Met à jour les totaux users sur les series de paris finis
     * Appel via URL : http://rimo0631.odns.fr/?cron_update_score_total_series=1&key=SECRET123
     */
    public static function cron_update_score_total_series() {
        if (!isset($_GET['cron_update_score_total_series'])) {
            return;
        }

        // 🔒 Sécurité
        $secret = 'SECRET123'; // à personnaliser
        if (!isset($_GET['key']) || $_GET['key'] !== $secret) {
            wp_die('Accès refusé ❌');
        }

        // 🎯 Étape 1 : récupérer toutes les séries
        $series = get_posts([
            'post_type'      => 'serie_de_paris',
            'posts_per_page' => -1,
            'post_status'    => 'publish'
        ]);

        if (!$series) {
            echo "❌ Aucune série trouvée";
            exit;
        }

        echo "<pre>";
        echo "=== CRON UPDATE SCORE SERIES ===\n";
        echo "Nombre total de séries : " . count($series) . "\n\n";

        foreach ($series as $serie) {
            $total_points = 0;
            $total_meilleure_serie_actuelle = 0;
            $total_score_exact_actuel = 0;
            $total_paris_gagnes_actuels = 0;
            $total_paris_effectues_actuels = 0;
            $bons_pronos_consecutifs = 0;

            echo "-------------------------\n";
            echo "📌 Série ID : {$serie->ID} | Titre : {$serie->post_title}\n";

            // 🎯 Étape 2 : récupérer les paris liés
            $paris = get_posts([
                'post_type'      => 'pari',
                'posts_per_page' => -1,
                'orderby'        => 'ID',
                'order'          => 'ASC',
                'meta_query'     => [
                    'relation' => 'AND',
                    [
                        'key'     => 'serie',
                        'value'   =>  $serie->ID ,
                        'compare' => 'LIKE'
                    ],
                    [
                        'key'     => 'status',
                        'value'   => 'calcule',
                        'compare' => 'LIKE'
                    ]
                ]
            ]);

            echo "➡️ Nombre de paris dans cette série : " . count($paris) . "\n";

            if (!$paris) {
                echo "❌ Aucun pari terminé trouvé dans cette série.\n\n";
                continue;
            }

            foreach ($paris as $index => $pari) {
                $total_paris_effectues_actuels+=1;
                $points_obtenus = (int) get_field('points_obtenus', $pari->ID);
                $bonus_applique = get_field('bonus_applique', $pari->ID);
                $resultat       = get_field('resultats', $pari->ID);
                $juste          = 'score_et_vainqueur_juste';
                $multiplicateur="x1";
                $bon = ($resultat == $juste);
                $gagne = in_array($resultat, ['score_juste','vainqueur_juste','score_et_vainqueur_juste']);
                $is_se = in_array($resultat, ['score_juste','score_et_vainqueur_juste']);
                
                echo "\n--- Pari ID : {$pari->ID} ---\n";
                echo "Total actuel : {$total_points}\n";
                echo "Bonus : {$bonus_applique}\n";
                echo "Points obtenus : {$points_obtenus}\n";
                echo "Résultat : {$resultat}\n";
                if ($gagne) {
                    $total_paris_gagnes_actuels+=1;
                }
                 if ($is_se) {
                    $total_score_exact_actuel+=1;
                }
                if ($bon) {
                    echo "✅ Bon pari\n";
                    $bons_pronos_consecutifs++;
                    $total_meilleure_serie_actuelle = $bons_pronos_consecutifs;
                    // ⚡️ Multiplicateurs
                    if ($bons_pronos_consecutifs == 3) {
                        $points_obtenus *= 3;
                        echo "Multiplicateur par 3 de ce score : {$points_obtenus}\n";
                        $multiplicateur="x3";
                    } elseif ($bons_pronos_consecutifs == 6) {
                        $points_obtenus *= 6;
                        echo "Multiplicateur par 3 de ce score : {$points_obtenus}\n";
                        $multiplicateur="x6";
                    } elseif ($bons_pronos_consecutifs == 9) {
                        $points_obtenus *= 9;
                        echo "Multiplicateur par 9 de ce score : {$points_obtenus}\n";
                        $multiplicateur="x9";
                    } elseif ($bons_pronos_consecutifs == 12) {
                        $points_obtenus *= 12;
                        echo "Multiplicateur par 12 de ce score : {$points_obtenus}\n";
                        $multiplicateur="x12";
                    } elseif ($bons_pronos_consecutifs == 15) {
                        $points_obtenus *= 15;
                        echo "Multiplicateur par 15 de ce score : {$points_obtenus}\n";
                        $multiplicateur="x15";
                    }

                    $total_points += $points_obtenus;
                } else {
                    if ($bonus_applique === 'joker') {
                        echo "🎭 Joker utilisé : la série continue\n";
                        $bons_pronos_consecutifs++;
                        $total_meilleure_serie_actuelle = $bons_pronos_consecutifs;
                        $total_points += $points_obtenus;
                        continue;
                    } else {
                        echo "❌ Mauvais pari : série cassée\n";
                        $bons_pronos_consecutifs = 0;
                        $total_points += $points_obtenus;
                    }
                    
                }
                update_field('status', 'termine', $pari->ID);
                update_field('multiplicateur', $multiplicateur, $pari->ID);
                echo "Bons pronos consécutifs : {$bons_pronos_consecutifs}\n";
            } // ✅ fin foreach($paris)

            

            // 🎯 Étape 3 : mettre à jour l’utilisateur lié
            $user = get_field('user', $serie->ID); // relation utilisateur
            
            if ($user) {
                $user_id = $user['ID'];
                $prenom  = $user['user_firstname'];
                $nom     = $user['user_lastname'];
                $email   = $user['user_email'];
                $current_points = (int) get_field('total_de_points', 'user_' . $user_id);
                $series_jouees = (int) get_field('series_jouees', 'user_' . $user_id);
                $meilleure_serie = (int) get_field('meilleure_serie', 'user_' . $user_id);
                $score_exact = (int) get_field('score_exact', 'user_' . $user_id);
                $paris_gagnes = (int) get_field('paris_gagnes', 'user_' . $user_id);
                $paris_effectues = (int) get_field('paris_effectues', 'user_' . $user_id);

                echo "\n👤 Utilisateur lié : {$user_id} - {$prenom} {$nom} ({$email})\n";
                echo "Points actuels : {$current_points}\n";
                echo "Points ajoutés : {$total_points}\n";
                echo "Séries jouées actuelles : {$series_jouees}\n";
                echo "Séries jouées (ajoutée) : 1\n";
                echo "Meilleure série actuelle : {$meilleure_serie}\n";
                echo "Meilleure série (ajouté) : {$total_meilleure_serie_actuelle}\n";
                echo "Paris gagnés actuels : {$paris_gagnes}\n";
                echo "Paris gagnés (ajoutés) : {$total_paris_gagnes_actuels}\n";

                update_field('total_de_points', $current_points + $total_points, 'user_' . $user_id);
                update_field('serie_en_cours', $serie->ID, 'user_' . $user_id);
                update_field('meilleure_serie', max($meilleure_serie, $total_meilleure_serie_actuelle), 'user_' . $user_id); // garde la meilleure
                update_field('paris_gagnes', $paris_gagnes + $total_paris_gagnes_actuels, 'user_' . $user_id); // cumul des paris gagnés
                update_field('paris_effectues', $paris_effectues + $total_paris_effectues_actuels, 'user_' . $user_id); // cumul des paris effectues
                update_field('score_exact', $score_exact + $total_score_exact_actuel, 'user_' . $user_id);
                update_field('series_jouees', $series_jouees + 1, 'user_' . $user_id); // incrémente le compteur
                
            }

            echo "-------------------------\n\n";
        }

        echo "✅ Scores mis à jour\n";
        echo "</pre>";

 global $wpdb;

        // Récupération et tri des joueurs
        $all_users = $wpdb->get_results("
            SELECT u.ID
            FROM {$wpdb->users} u
            INNER JOIN {$wpdb->usermeta} m1 ON u.ID = m1.user_id AND m1.meta_key = 'total_de_points'
            INNER JOIN {$wpdb->usermeta} m2 ON u.ID = m2.user_id AND m2.meta_key = 'paris_gagnes'
            INNER JOIN {$wpdb->usermeta} m3 ON u.ID = m3.user_id AND m3.meta_key = 'meilleure_serie'
            INNER JOIN {$wpdb->usermeta} m4 ON u.ID = m4.user_id AND m4.meta_key = '{$wpdb->prefix}capabilities'
            WHERE m4.meta_value LIKE '%joueur_jpl%'
            ORDER BY 
                CAST(m1.meta_value AS UNSIGNED) DESC,
                CAST(m2.meta_value AS UNSIGNED) DESC,
                CAST(m3.meta_value AS UNSIGNED) DESC
        ");

        // Mise à jour du classement
        if ($all_users) {
            $rang = 1;
            foreach ($all_users as $user) {
                update_field('classement', $rang, 'user_' . $user->ID);
                $rang++;
            }
        }

        exit;




    }



    
    public static function enregistrer_pari() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error("Utilisateur non connecté");
        }

        $rencontre_id   = intval($_POST['rencontre_id'] ?? 0);
        $score1         = intval($_POST['score1'] ?? 0);
        $score2         = intval($_POST['score2'] ?? 0);
        $vainqueur      = sanitize_text_field($_POST['vainqueur'] ?? '');
        $bonus          = sanitize_text_field($_POST['bonus'] ?? 'aucun');

        if (!$rencontre_id || !$vainqueur) {
            wp_send_json_error("Paramètres invalides");
        }

        $email = wp_get_current_user()->user_email;

        // Vérifier si un pari existe déjà pour cet user + cette rencontre
        $existing = get_posts([
            'post_type'      => 'pari',
            'posts_per_page' => 1,
            'meta_query'     => [
                'relation' => 'AND',
                [
                    'key'     => 'user',
                    'value'   => $user_id,
                    'compare' => '=',
                ],
                [
                    'key'     => 'rencontre',
                    'value'   => $rencontre_id,
                    'compare' => '=',
                ]
            ]
        ]);

        // 🔎 Vérifier si une série < 4 jours existe déjà pour cet utilisateur
        $start = new DateTime('monday this week', wp_timezone());
        $end   = new DateTime('sunday this week 23:59:59', wp_timezone());

        // 🔎 Vérifier si une série existe déjà pour cet utilisateur cette semaine
        $serie_existante = get_posts([
            'post_type'      => 'serie_de_paris',
            'posts_per_page' => 1,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => [
                [
                    'key'     => 'user',
                    'value'   => $user_id,
                    'compare' => '=',
                ]
            ],
            'date_query' => [
                [
                    'after'     => $start->format('Y-m-d H:i:s'),
                    'before'    => $end->format('Y-m-d H:i:s'),
                    'inclusive' => true,
                ]
            ]
        ]);

        $serie_id = null;
        if (!empty($serie_existante)) {
            $dernier = $serie_existante[0];
            $date_creation = strtotime($dernier->post_date);
            $jours = (time() - $date_creation) / (60 * 60 * 24);

            if ($jours < 4) {
                $serie_id = $dernier->ID;
            }
        }

        // Si pas de série récente → en créer une nouvelle
        if (!$serie_id) {
            $serie_id = wp_insert_post([
                'post_type'   => 'serie_de_paris',
                'post_status' => 'publish',
                'post_title'  => 'Série de paris de ' . $email . ' créée le ' . date('d/m/Y H:i:s'),
            ]);

            if ($serie_id) {
                update_field('user', $user_id, $serie_id); // lien user
                update_field('total_corrects', 0, $serie_id); // compteur initial
            }
        }

        if (!empty($existing)) {
            // Mise à jour pari existant
            $pari_id = $existing[0]->ID;
            update_field('vainqueur', $vainqueur, $pari_id);
            update_field('score_equipe_1', $score1, $pari_id);
            update_field('score_equipe_2', $score2, $pari_id);
            update_field('bonus_applique', $bonus, $pari_id);
            update_field('serie', $serie_id, $pari_id);

            wp_send_json_success("Votre pronostic a été validé !");
        } else {
            // Création d'un nouveau pari
            $pari_id = wp_insert_post([
                'post_type'   => 'pari',
                'post_status' => 'publish',
                'post_title'  => 'Pari de ' . $email . ' sur la rencontre ' . get_the_title($rencontre_id),
            ]);

            if ($pari_id) {
                update_field('user', $user_id, $pari_id);
                update_field('rencontre', $rencontre_id, $pari_id);
                update_field('vainqueur', $vainqueur, $pari_id);
                update_field('score_equipe_1', $score1, $pari_id);
                update_field('score_equipe_2', $score2, $pari_id);
                update_field('status', 'a_venir', $pari_id);
                update_field('bonus_applique', $bonus, $pari_id);
                update_field('serie', $serie_id, $pari_id);

                wp_send_json_success("Pari créé avec succès !");
            }
        }

        wp_send_json_error("Erreur lors de l'enregistrement du pari");
    }





public static function get_stats_semaine( $date_from, $date_to ) {
    // Normaliser dates (on suppose format correct mais tu peux valider si besoin)
    $date_query = [
        [
            'after'     => $date_from,
            'before'    => $date_to,
            'inclusive' => true,
        ]
    ];

    // Récupérer les séries créées dans la fourchette
    $series = get_posts([
        'post_type'      => 'serie_de_paris',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'date_query'     => $date_query,
    ]);

    if ( ! $series ) {
        return [];
    }

    // Structure de cumul : user_id => stats
    $stats = [];

    // Boucle sur chaque série
    foreach ( $series as $serie ) {
        // Récupérer l'utilisateur lié à la série (si tu lies la série à un user via ACF 'user')
        $serie_user = get_field( 'user', $serie->ID ); // relation ACF (tableau ou array)
        $serie_user_id = 0;
        if ( $serie_user ) {
            // si ACF retourne un array ['ID' => x] ou un WP_User object
            if ( is_array( $serie_user ) && isset( $serie_user['ID'] ) ) {
                $serie_user_id = intval( $serie_user['ID'] );
            } elseif ( is_object( $serie_user ) && isset( $serie_user->ID ) ) {
                $serie_user_id = intval( $serie_user->ID );
            } else {
                $serie_user_id = intval( $serie_user ); // cas où ACF retourne l'ID directement
            }
        }

        // Récupérer tous les paris "terminés" pour cette série
        $paris = get_posts([
            'post_type'      => 'pari',
            'posts_per_page' => -1,
            'orderby'        => 'ID',
            'order'          => 'ASC',
            'meta_query'     => [
                'relation' => 'AND',
                [
                    'key'     => 'serie',
                    'value'   => $serie->ID,
                    'compare' => 'LIKE',
                ],
                [
                    'key'     => 'status',
                    'value'   => 'termine',
                    'compare' => 'LIKE',
                ],
            ],
        ]);

        if ( ! $paris ) {
            continue;
        }
        $cur=0;

        // Pour chaque pari, on doit connaître le user qui a parié.
        // J'assume que chaque post 'pari' contient un champ ACF 'user' / 'membre' / 'parieur' retournant l'ID ou relation.
        foreach ( $paris as $pari ) {
            // Identifiant du parieur pour ce pari (adapter le nom du champ si nécessaire)
            $parieur_field = get_field( 'parieur', $pari->ID ); // change si ton champ s'appelle différemment
            $parieur_id = 0;
            if ( $parieur_field ) {
                if ( is_array( $parieur_field ) && isset( $parieur_field['ID'] ) ) {
                    $parieur_id = intval( $parieur_field['ID'] );
                } elseif ( is_object( $parieur_field ) && isset( $parieur_field->ID ) ) {
                    $parieur_id = intval( $parieur_field->ID );
                } else {
                    $parieur_id = intval( $parieur_field );
                }
            }

            if ( ! $parieur_id ) {
                // éventuellement essayer un autre champ : 'user' ou 'membre'
                $alt = get_field( 'user', $pari->ID );
                if ( $alt ) {
                    if ( is_array( $alt ) && isset( $alt['ID'] ) ) {
                        $parieur_id = intval( $alt['ID'] );
                    } elseif ( is_object( $alt ) && isset( $alt->ID ) ) {
                        $parieur_id = intval( $alt->ID );
                    } else {
                        $parieur_id = intval( $alt );
                    }
                }
            }

            if ( ! $parieur_id ) {
                // pas d'utilisateur associé -> on skip
                continue;
            }

            // Initialiser l'entrée utilisateur si absente
            if ( ! isset( $stats[ $parieur_id ] ) ) {
                $stats[ $parieur_id ] = [
                    'user_id'               => $parieur_id,
                    'paris_effectues'       => 0,
                    'paris_gagnes'          => 0,
                    'score_exact'           => 0,
                    'meilleure_serie'       => 0,
                    'current_serie'         => 0, // compteur courant pour cette agrégation
                    'total_bonus_utilises'  => 0,
                    'total_points'          => 0,
                    'series_count'          => 0, // nb de séries rencontrées (optionnel)
                ];
            }

            // Incrément paris effectues
            $stats[ $parieur_id ]['paris_effectues']++;

            // Récupérer champs du pari
            $points_obtenus = (int) get_field( 'points_obtenus', $pari->ID );
            $bonus_applique = get_field( 'bonus_applique', $pari->ID ); // ex: 'x2','joker','aucun'
            $resultat       = get_field( 'resultats', $pari->ID ); // ex: 'score_juste','vainqueur_juste','score_et_vainqueur_juste'
            $juste          = 'score_et_vainqueur_juste';

            $gagne = in_array( $resultat, ['score_juste', 'vainqueur_juste', 'score_et_vainqueur_juste'], true );
            $is_se = in_array( $resultat, ['score_juste', 'score_et_vainqueur_juste'], true );
            $is_bon = ( $resultat === $juste ); // si tu définis "bon" comme score+vainqueur

            // bonus : considérer "aucun", "aucun " erreurs de saisie -> trim
            $bonus_applique_norm = is_string( $bonus_applique ) ? trim( strtolower( $bonus_applique ) ) : '';

            if ( $gagne ) {
                $stats[ $parieur_id ]['paris_gagnes']++;
            }
            if ( $is_se ) {
                $stats[ $parieur_id ]['score_exact']++;
            }
            if ( $bonus_applique_norm !== '' && $bonus_applique_norm !== 'aucun' && $bonus_applique_norm !== 'aucun ' ) {
                $stats[ $parieur_id ]['total_bonus_utilises']++;
            }

            // Gestion des séries consécutives ET multiplicateurs
            if ( $is_bon ) {
                // incrément current série
                $stats[ $parieur_id ]['current_serie']++;
                $cur = $stats[ $parieur_id ]['current_serie'];

                // appliquer multiplicateur selon la longueur courante de la série
                $multiplier = 1;
                if ( $cur == 15 ) {
                    $multiplier = 15;
                } elseif ( $cur == 12 ) {
                    $multiplier = 12;
                } elseif ( $cur == 9 ) {
                    $multiplier = 9;
                } elseif ( $cur == 6 ) {
                    $multiplier = 6;
                } elseif ( $cur == 3 ) {
                    $multiplier = 3;
                }

                $points_after = $points_obtenus * $multiplier;
                $stats[ $parieur_id ]['total_points'] += $points_after;

                // mettre à jour meilleure série
                if ( $cur > $stats[ $parieur_id ]['meilleure_serie'] ) {
                    $stats[ $parieur_id ]['meilleure_serie'] = $cur;
                }
            } else {
                // pas "score et vainqueur juste"
                // Joker : si joker doit *garder* la série en cours mais ajouter ses points,
                // ici on considère joker = prolonge la série (tu avais ce comportement)
                if ( $bonus_applique_norm === 'joker' ) {
                    $stats[ $parieur_id ]['current_serie']++;
                    // ajouter les points du pari (sans multiplier supplémentaire lié à la série)
                    $stats[ $parieur_id ]['total_points'] += $points_obtenus;
                    // mettre à jour meilleure série si besoin
                    if ( $stats[ $parieur_id ]['current_serie'] > $stats[ $parieur_id ]['meilleure_serie'] ) {
                        $stats[ $parieur_id ]['meilleure_serie'] = $stats[ $parieur_id ]['current_serie'];
                    }
                } else {
                    // pari raté -> casse la série
                    $stats[ $parieur_id ]['current_serie'] = 0;
                    // si tu veux compter les points même pour les mauvais paris, les ajouter ici :
                    $stats[ $parieur_id ]['total_points'] += $points_obtenus;
                }
            }

            
        } 

        // Si tu veux associer la série à son auteur et compter combien de séries il a, tu peux :
        if ( $serie_user_id ) {
            if ( ! isset( $stats[ $serie_user_id ] ) ) {
                $stats[ $serie_user_id ] = [
                    'user_id'               => $serie_user_id,
                    'paris_effectues'       => 0,
                    'paris_gagnes'          => 0,
                    'score_exact'           => 0,
                    'meilleure_serie'       => 0,
                    'current_serie'         => 0,
                    'total_bonus_utilises'  => 0,
                    'total_points'          => 0,
                    'series_count'          => 0,
                ];
            }
            $stats[ $serie_user_id ]['series_count']++;
        }

    } // end foreach $series

    // Nettoyage : supprimer current_serie des résultats finaux, on garde la meilleure_serie
    foreach ( $stats as $uid => &$stat ) {
        unset( $stat['current_serie'] );
    }
    unset( $stat );

    // Trier par total_points DESC, paris_gagnes DESC, score_exact DESC
    usort( $stats, function( $a, $b ) {
        if ( $a['total_points'] === $b['total_points'] ) {
            if ( $a['paris_gagnes'] === $b['paris_gagnes'] ) {
                return $b['score_exact'] <=> $a['score_exact'];
            }
            return $b['paris_gagnes'] <=> $a['paris_gagnes'];
        }
        return $b['total_points'] <=> $a['total_points'];
    } );

    // Re-indexer par position (optionnel)
    $ranked = [];
    $pos = 1;
    foreach ( $stats as $s ) {
        $s['rank'] = $pos++;
        $ranked[ $s['user_id'] ] = $s;
    }

    return $ranked; // tableau associatif user_id => stat_array (avec 'rank')
}



    /**
 * ⚡️ CRON : cree et sauvegarde les champions de la semaine
 * Appel via URL : http://rimo0631.odns.fr/?cron_update_and_save_stats_semaine=1&key=SECRET123
 */
public static function cron_update_and_save_stats_semaine() {
    if (!isset($_GET['cron_update_and_save_stats_semaine'])) {
        return;
    }

    // 🔒 Sécurité
    $secret = 'SECRET123';
    if (!isset($_GET['key']) || $_GET['key'] !== $secret) {
        wp_die('Accès refusé ❌');
    }

    // 📅 Définir la semaine courante (lundi -> dimanche)
    $start = new DateTime('monday this week', wp_timezone());
    $end   = new DateTime('sunday this week 23:59:59', wp_timezone());

    $date_from = $start->format('Y-m-d H:i:s');
    $date_to   = $end->format('Y-m-d H:i:s');

    // 📊 Récup stats
    $stats = self::get_stats_semaine($date_from, $date_to);

    if (empty($stats)) {
        wp_die('Aucune stat trouvée ❌');
    }

    foreach ($stats as $uid => $data) {
        $user = get_user_by('ID', $uid);
        if (!$user) continue;

        // 🔖 Créer un post "champ_semaine"
        $post_id = wp_insert_post([
            'post_type'   => 'champ_semaine',
            'post_title'  => 'Semaine du ' . $start->format('d/m/Y'),
            'post_status' => 'publish',
        ]);

        if (is_wp_error($post_id)) continue;

        // 📝 Mettre à jour les champs (ACF ou meta classiques)
        update_field('user_id', $uid, $post_id);
        update_field('nom', $user->last_name ?: '', $post_id);
        update_field('prenom', $user->first_name ?: '', $post_id);
        update_field('paris_gagnes', $data['paris_gagnes'], $post_id);
        update_field('score_exacts', $data['score_exact'], $post_id);
        update_field('serie_en_cours', $data['meilleure_serie'], $post_id);
        update_field('bonus_utilises', $data['total_bonus_utilises'], $post_id);
        update_field('points', $data['total_points'], $post_id);
        update_field('date_semaine', $start->format('Ymd'), $post_id); 
    }

    wp_die('CRON terminé champions calculés et sauvegardés ✅');
}



}

