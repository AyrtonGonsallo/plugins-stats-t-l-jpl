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
            'orderby'        => 'meta_value',
            'order'          => 'ASC',
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
                'posts_per_page' => 100,
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
        $batch = 0;
        if (isset($_GET['batch'])) {
            $batch = $_GET['batch'];
        }else{
            wp_die('Parametre batch manquant ❌');
        }

        // 🔒 Sécurité
        $secret = 'SECRET123'; // à personnaliser
        if (!isset($_GET['key']) || $_GET['key'] !== $secret) {
            wp_die('Accès refusé ❌');
        }

        // 🎯 Étape 1 : récupérer toutes les séries
       

        $limit = 150;
        $offset = $batch * $limit;

        $series = get_posts([
            'post_type'      => 'serie_de_paris',
            'posts_per_page' => $limit,
            'post_status'    => 'publish',
            'orderby'        => 'ID',
            'order'          => 'ASC',
            'offset'         => $offset
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
                'meta_key'       => 'date',
                'orderby'        => 'meta_value',
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
                       // 'value'   => 'calcule',
                       'value'   => ['calcule','termine'],
                       'compare' => 'IN'
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
                $status_pari       = get_field('status', $pari->ID);
                $juste          = 'score_et_vainqueur_juste';
                $multiplicateur="x1";
                $gagne = in_array($resultat, ['score_juste','vainqueur_juste','score_et_vainqueur_juste']);
                $is_se = in_array($resultat, ['score_juste','score_et_vainqueur_juste']);
                $bon = $gagne;
                
                echo "\n--- Pari ID : {$pari->ID} ---\n";
                echo "Total actuel : {$total_points}\n";
                echo "Bonus : {$bonus_applique}\n";
                echo "Points obtenus : {$points_obtenus}\n";
                echo "Résultat : {$resultat}\n";
                echo "Status pari : {$status_pari}\n";
                if ($gagne) {
                    $total_paris_gagnes_actuels+=1;
                }
                 if ($is_se) {
                    $total_score_exact_actuel+=1;
                    echo "✅ Score exact\n";
                }
               if ($bon) {
    echo "✅ Bon pari\n";
    $bons_pronos_consecutifs++;

    // 🔥 MAJ de la meilleure série atteinte
    $total_meilleure_serie_actuelle = max($total_meilleure_serie_actuelle, $bons_pronos_consecutifs);

    // ⚡️ Multiplicateurs
    if ($bons_pronos_consecutifs == 3) {
        $points_obtenus *= 3;
        echo "Multiplicateur par 3 de ce score : {$points_obtenus}\n";
        $multiplicateur="x3";
    } elseif ($bons_pronos_consecutifs == 6) {
        $points_obtenus *= 6;
        echo "Multiplicateur par 6 de ce score : {$points_obtenus}\n";
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
        // ❗ Même logique : on ne touche pas à la meilleure série
        $total_meilleure_serie_actuelle = max($total_meilleure_serie_actuelle, $bons_pronos_consecutifs);

        $total_points += $points_obtenus;

    } else {
        echo "❌ Mauvais pari : série cassée\n";

        // ❗ On casse la série, mais la meilleure série reste intacte
        $bons_pronos_consecutifs = 0;

        $total_points += $points_obtenus;
    }
}

                
                if( $status_pari=="calcule"){
                    update_field('status', 'termine', $pari->ID);
                    update_field('multiplicateur', $multiplicateur, $pari->ID);
                }
                
                
                
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
                echo "Points calculée maintenant : {$total_points}\n";
                echo "score_exact actuels : {$total_score_exact_actuel}\n";
                echo "score_exact calculée maintenant : {$score_exact}\n";
                echo "score_exact mis a jour : score_exact actuels {$score_exact} + total_score_exact_actuel {$total_score_exact_actuel}, 'user_' . {$user_id}\n";
                echo "Séries jouées actuelles : {$series_jouees}\n";
                echo "Séries jouées (calculée maintenant) : 1\n";
                echo "Meilleure série actuelle : {$meilleure_serie}\n";
                echo "Meilleure série (calculée maintenant) : {$total_meilleure_serie_actuelle}\n";
                echo "Paris gagnés actuels : {$paris_gagnes}\n";
                echo "Paris gagnés (calculée maintenant) : {$total_paris_gagnes_actuels}\n";
			
                
                update_field('total_de_points',  $total_points, 'user_' . $user_id);
                update_field('serie_en_cours', $serie->ID, 'user_' . $user_id);
                update_field('meilleure_serie',  $total_meilleure_serie_actuelle, 'user_' . $user_id); // garde la meilleure
                update_field('paris_gagnes',  $total_paris_gagnes_actuels, 'user_' . $user_id); // cumul des paris gagnés
                update_field('paris_effectues',  $total_paris_effectues_actuels, 'user_' . $user_id); // cumul des paris effectues
                update_field('score_exact',  $total_score_exact_actuel, 'user_' . $user_id);
                update_field('series_jouees', $series_jouees + 1, 'user_' . $user_id); // incrémente le compteur
                
            }

            echo "-------------------------\n\n";
        }

        echo "✅ Scores mis à jour\n";
        echo "</pre>";


        /* test
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

        */

        exit;




    }



    /**
 * ⚡️ CRON : trouve les séries multiples des utilisateurs et les fusionne
 * Appel via URL : http://rimo0631.odns.fr/?cron_check_multiple_user_series=1&key=SECRET123
 */
public static function cron_check_multiple_user_series() {
    if (!isset($_GET['cron_check_multiple_user_series'])) {
        return;
    }

    // 🔒 Sécurité
    $secret = 'SECRET123'; // à personnaliser
    if (!isset($_GET['key']) || $_GET['key'] !== $secret) {
        wp_die('Accès refusé ❌');
    }

    $batch = 0; // changer si besoin (pagination manuelle)
    $limit = 500; // combien de séries à traiter par passe
    $offset = $batch * $limit;

    // 🎯 Étape 1 : récupérer les séries
    $series = get_posts([
        'post_type'      => 'serie_de_paris',
        'posts_per_page' => $limit,
        'post_status'    => 'publish',
        'orderby'        => 'ID',
        'order'          => 'ASC',
        'offset'         => $offset
    ]);

    if (!$series) {
        echo "❌ Aucune série trouvée";
        exit;
    }

    echo "<pre>";
    echo "=== CRON MERGE SERIES ===\n";
    echo "Nombre de séries chargées : " . count($series) . "\n\n";

    // Regrouper séries par utilisateur
    $user_series = [];
    foreach ($series as $serie) {
        $user = get_field('user', $serie->ID); // relation utilisateur

    

        if (!$user) continue;
        $user_id = $user['ID'];

        if (!isset($user_series[$user_id])) {
            $user_series[$user_id] = [];
        }
        $user_series[$user_id][] = $serie;
    }

    // 🎯 Étape 2 : fusionner
    foreach ($user_series as $user_id => $list) {
        if (count($list) <= 1) {
            continue; // rien à fusionner
        }
        $user_info = get_userdata($user_id);
        if ($user_info) {
            $nom    = get_field('nom','user_' . $user_id);
            $prenom  = get_field('prenom','user_' . $user_id);
             $email  = $user_info->user_email;
           
            echo "\n👤 Utilisateur lié : {$user_id} - {$prenom} {$nom} ({$email})\n";
            echo "👤 Utilisateur ID $user_id a " . count($list) . " séries\n";
        }


       
        // garder la plus ancienne (plus petit ID)
        usort($list, function($a, $b) {
            return $a->ID <=> $b->ID;
        });
        $main = array_shift($list);

        echo "   ✅ Série principale gardée : {$main->ID}\n";
        
        // Pour chaque série secondaire
        foreach ($list as $duplicate) {
            echo "   🔄 Fusion de la série {$duplicate->ID} vers {$main->ID}\n";

            // Récupérer ses paris
            $paris = get_posts([
                'post_type'      => 'pari',
                'posts_per_page' => -1,
                'meta_query'     => [
                    [
                        'key'     => 'serie',
                        'value'   => $duplicate->ID,
                        'compare' => 'LIKE'
                    ]
                ]
            ]);

            foreach ($paris as $pari) {
                // Réassigner à la série principale
                update_field('serie', $main->ID, $pari->ID);
                echo "      → Pari {$pari->ID} réattribué à série {$main->ID}\n";
            }

            // Supprimer la série en double
            wp_delete_post($duplicate->ID, true);
            echo "   ❌ Série {$duplicate->ID} supprimée\n";
        }
            

        echo "\n";
    }

    echo "=== FIN CRON ===";
    echo "</pre>";
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
        $date_debut_rencontre=get_field('date_de_debut',$rencontre_id,false, false);

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
            update_field('date', $date_debut_rencontre, $pari_id);
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
                update_field('date', $date_debut_rencontre, $pari_id);

                wp_send_json_success("Pari créé avec succès !");
            }
        }

        wp_send_json_error("Erreur lors de l'enregistrement du pari");
    }





public static function get_stats_semaine( $date_from, $date_to ) {
    

    // aide moi gpt a Récupérer les rencontres jouées dans la fourchette champ  'meta_key'       => 'date_de_debut',
   $rencontres = get_posts([
        'post_type'      => 'rencontre',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_query'     => [
            [
                'key'     => 'date_de_debut', // ACF date
                'value'   => [$date_from, $date_to],
                'compare' => 'BETWEEN',
                'type'    => 'DATETIME' // ou 'DATE' selon le format stocké
            ]
        ],
    ]);


    if ( ! $rencontres ) {
        return [];
    }

    // Structure de cumul : user_id => stats
    $stats = [];

    // Boucle sur chaque rencontre
    foreach ( $rencontres as $rencontre ) {
        //recuperer les paris finis sur elle
        $paris = get_posts([
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
                        'value'   => 'termine',
                        'compare' => 'LIKE'
                    ]
                ]
            ]);

           

        


        if ( ! $paris ) {
            continue;
        }
        $cur=0;
        // Récupérer l'utilisateur lié à la série (si tu lies la série à un user via ACF 'user')
        $serie_user = get_field( 'serie', $paris[0]->ID ); // relation ACF (tableau ou array)
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
            $is_bon = ( $gagne ); // si tu définis "bon" comme score+vainqueur

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
             $serie_user_data = get_user_by('id', $serie_user_id);
            $pseudo = get_field('pseudo', 'user_'.$serie_user_id) ?: $serie_user_data->display_name;
            if ( ! isset( $stats[ $serie_user_id ] ) ) {
                $stats[ $serie_user_id ] = [
                    'user_id'               => $serie_user_id,
                    'user_pseudo'               => $pseudo,
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

    // Trier par total_points DESC, paris_gagnes DESC, score_exact DESC, puis pseudo ASC
    usort( $stats, function( $a, $b ) {
        if ( $a['total_points'] === $b['total_points'] ) {
            if ( $a['paris_gagnes'] === $b['paris_gagnes'] ) {
                if ( $a['meilleure_serie'] === $b['meilleure_serie'] ) {
                    return $a['user_pseudo'] <=> $b['user_pseudo']; // ordre alphabétique
                }
                return $b['meilleure_serie'] <=> $a['meilleure_serie'];
            }
            return $b['paris_gagnes'] <=> $a['paris_gagnes'];
        }
        return $b['total_points'] <=> $a['total_points'];
    });

    // Re-indexer et limiter aux 5 premiers
    $ranked = [];
    $pos = 1;
    foreach ( $stats as $s ) {
        if ( $pos > 5 ) { // stop après 5
            break;
        }
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


   /**
 * ⚡️ CRON : cree et sauvegarde les champions de la semaine
 * Appel via URL : http://rimo0631.odns.fr/?cron_update_classement=1&key=SECRET123
 */
public static function cron_update_classement() {
    if (!isset($_GET['cron_update_classement'])) {
        return;
    }

    // 🔒 Sécurité
    $secret = 'SECRET123'; // à personnaliser
    if (!isset($_GET['key']) || $_GET['key'] !== $secret) {
        wp_die('Accès refusé ❌');
    }

    global $wpdb;

    // Récupération et tri des joueurs
    $all_users = $wpdb->get_results("
        SELECT u.ID,
            um_points.meta_value AS total_points,
            um_pg.meta_value AS paris_gagnes,
            um_ms.meta_value AS meilleure_serie
        FROM {$wpdb->users} u
        INNER JOIN {$wpdb->usermeta} um_caps 
            ON u.ID = um_caps.user_id 
            AND um_caps.meta_key = '{$wpdb->prefix}capabilities'
        LEFT JOIN {$wpdb->usermeta} um_points 
            ON u.ID = um_points.user_id 
            AND um_points.meta_key = 'total_de_points'
        LEFT JOIN {$wpdb->usermeta} um_pg 
            ON u.ID = um_pg.user_id 
            AND um_pg.meta_key = 'paris_gagnes'
        LEFT JOIN {$wpdb->usermeta} um_ms 
            ON u.ID = um_ms.user_id 
            AND um_ms.meta_key = 'meilleure_serie'
        WHERE um_caps.meta_value LIKE '%joueur_jpl%'
        ORDER BY 
            CAST(um_points.meta_value AS UNSIGNED) DESC,
            CAST(um_pg.meta_value AS UNSIGNED) DESC,
            CAST(um_ms.meta_value AS UNSIGNED) DESC
    ");

    if (!$all_users) {
        wp_die("❌ Aucun utilisateur trouvé");
    }

    // Tri complémentaire par pseudo quand égalité parfaite
    usort($all_users, function($a, $b) {
        // Comparaison points
        $pa = (int)$a->total_points;
        $pb = (int)$b->total_points;
        if ($pa !== $pb) return $pb - $pa;

        // Comparaison paris_gagnes
        $ga = (int)$a->paris_gagnes;
        $gb = (int)$b->paris_gagnes;
        if ($ga !== $gb) return $gb - $ga;

        // Comparaison meilleure série
        $ma = (int)$a->meilleure_serie;
        $mb = (int)$b->meilleure_serie;
        if ($ma !== $mb) return $mb - $ma;

        // Comparaison pseudo (fallback prénom/nom ou email)
        $user_data_a = get_userdata($a->ID);
        $user_data_b = get_userdata($b->ID);

      
        $pseudo_a = get_field('pseudo', 'user_' . $a->ID) ?: $user_data_a->display_name;

        $pseudo_b = get_field('pseudo', 'user_' . $b->ID) ?: $user_data_b->display_name;

        return strcasecmp($pseudo_a, $pseudo_b);
    });


    echo "<pre>";
    echo "Nombre total d’utilisateurs trouvés : " . count($all_users) . "\n\n";

    $rang = 1;
    foreach ($all_users as $user) {
        $user_id = $user->ID;
        $user_data = get_userdata($user_id);

       
        $pseudo = get_field('pseudo', 'user_' . $user_id) ?: $user_data->display_name;

     

        // Affichage seulement (pas d’update_field encore)
        echo "Rang provisoire #{$rang} | ID {$user_id} | {$pseudo} | Points: {$user->total_points} | Paris gagnés: {$user->paris_gagnes} | Meilleure série: {$user->meilleure_serie}\n";

        // Mise à jour à activer plus tard :
        update_field('classement', $rang, 'user_' . $user_id);

        $rang++;
    }

    echo "</pre>";
    exit;
}

/**
     * ⚡️ CRON : Met à jour les combats individuels des judokas
     * Appel via URL : http://rimo0631.odns.fr/?cron_update_combats=1&key=SECRET123
     */
    public static function cron_update_combats() {
        if (!isset($_GET['cron_update_combats'])) {
            return;
        }

        $rencontres = get_posts([
            'post_type'      => 'rencontre',
                'posts_per_page' => -1,
                'meta_key'       => 'date_de_debut',
                'orderby'        => 'meta_value',
                'order'          => 'ASC',
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
        ]);
        
        echo "<pre>";

        foreach ($rencontres as $rencontre) {
            echo "Rencontre ".get_the_title( $rencontre->ID)."<br>";
            $saison = get_field('saisons', $rencontre->ID); // champ saison sur rencontre
            $matchs_liste = get_field('les_combat', $rencontre->ID);

            if (!$matchs_liste || !is_array($matchs_liste)) {
                continue;
            }

            foreach ($matchs_liste as $bloc) {
                if (!isset($bloc['combats'])) {
                    continue;
                }

                foreach ($bloc['combats'] as $match) {
                    $judoka1 = !empty($match['judoka_equipe_1'][0]) ? $match['judoka_equipe_1'][0]->ID : null;
                    $judoka2 = !empty($match['judoka_equipe_2'][0]) ? $match['judoka_equipe_2'][0]->ID : null;

                    if (!$judoka1 || !$judoka2) {
                        continue; // combat incomplet
                    }

                    // Vérifier si un combat existe déjà pour rencontre + judoka1 + judoka2
                    $existing = get_posts([
                        'post_type'      => 'combat',
                        'posts_per_page' => 1,
                        'meta_query'     => [
                            'relation' => 'AND',
                            ['key' => 'rencontre_id', 'value' => $rencontre->ID],
                            ['key' => 'judoka_equipe_1', 'value' => $judoka1],
                            ['key' => 'judoka_equipe_2', 'value' => $judoka2],
                        ]
                    ]);

                    if ($existing) {
                        echo "Combat ".get_the_title( $judoka1).' vs '.get_the_title( $judoka2).' existant <br>';
                        continue; // déjà migré
                        
                    }
                    echo "Combat ".get_the_title( $judoka1).' vs '.get_the_title( $judoka2).' ajouté <br>';

                    // Crée un nouveau post combat
                    $combat_id = wp_insert_post([
                        'post_type'   => 'combat',
                        'post_status' => 'publish',
                        'post_title'  => get_the_title($rencontre->ID) . ' : '.get_the_title($judoka1).' vs '. get_the_title($judoka2),
                    ]);

                    if (is_wp_error($combat_id)) {
                        continue;
                    }

                    // Champs de base
                    update_field('judoka_equipe_1', $match['judoka_equipe_1'], $combat_id);
                    update_field('judoka_equipe_2', $match['judoka_equipe_2'], $combat_id);
                    update_field('judoka_gagnant', $match['judoka_gagnant'], $combat_id);

                    update_field('valeur_wazari__judoka_1', $match['valeur_wazari__judoka_1'], $combat_id);
                    update_field('valeurs_shidos_judoka_1', $match['valeurs_shidos_judoka_1'], $combat_id);
                    update_field('valeur_ippons_comptes_judoka_1', $match['valeur_ippons_comptés_judoka_1'], $combat_id);
                    update_field('valeur_ippon_judoka_1', $match['valeur_ippon_judoka_1'], $combat_id);
                    update_field('points_judoka_1', $match['points_judoka_1'], $combat_id);
                    update_field('kinza_1', $match['kinza_1'], $combat_id);
                    update_field('yuko_1', $match['yuko_1'], $combat_id);

                    update_field('valeur_wazari__judoka_2', $match['valeur_wazari__judoka_2'], $combat_id);
                    update_field('valeurs_shidos_judoka_2', $match['valeurs_shidos_judoka_2'], $combat_id);
                    update_field('valeur_ippons_comptes_judoka_2', $match['valeur_ippons_comptés_judoka_2'], $combat_id);
                    update_field('valeur_ippon_judoka_2', $match['valeur_ippon_judoka_2'], $combat_id);
                    update_field('points_judoka_2', $match['points_judoka_2'], $combat_id);
                    update_field('kinza_2', $match['kinza_2'], $combat_id);
                    update_field('yuko_2', $match['yuko_2'], $combat_id);

                    update_field('categorie_de_poids', $match['categorie_de_poids'], $combat_id);

                    // Ajout des infos relationnelles
                    update_field('saisons', $saison, $combat_id);        // saison copiée depuis rencontre
                    update_field('rencontre_id', $rencontre->ID, $combat_id); // stocker l’ID de la rencontre
                }
            }
        }

        echo "</pre>";
        exit;

    }



    /**
     * ⚡️ CRON : Met à zero les totaux users pour la saison prochaine
     * Appel via URL : http://rimo0631.odns.fr/?cron_resset_score_total_series=1&batch=0&key=SECRET123
     */
    public static function cron_resset_score_total_series() {
        if (!isset($_GET['cron_resset_score_total_series'])) {
            return;
        }
        $batch = 0;
        if (isset($_GET['batch'])) {
            $batch = $_GET['batch'];
        }else{
            wp_die('Parametre batch manquant ❌');
        }

        // 🔒 Sécurité
        $secret = 'SECRET123'; // à personnaliser
        if (!isset($_GET['key']) || $_GET['key'] !== $secret) {
            wp_die('Accès refusé ❌');
        }

        // 🎯 Étape 1 : récupérer toutes les séries
       

        $limit = 150;
        $offset = $batch * $limit;

        $users = get_users([
            'role'    => 'joueur_jpl',
            'number'  => $limit,
            'offset'  => $offset,
            'orderby' => 'ID',
            'order'   => 'ASC',
        ]);


      

        echo "<pre>";
        echo "=== CRON resset scores ===\n";
        echo "Nombre total de users : " . count($users) . "\n\n";

        foreach ($users as $user) {
           

            update_field('total_de_points', 0, 'user_' . $user->ID);
            update_field('meilleure_serie', 0, 'user_' . $user->ID);
            update_field('paris_gagnes', 0, 'user_' . $user->ID);
            update_field('paris_effectues', 0, 'user_' . $user->ID);
            update_field('score_exact', 0, 'user_' . $user->ID);
            update_field('serie_en_cours', null, 'user_' . $user->ID);

            // uniquement si tu souhaites réellement repartir à zéro
            update_field('series_jouees', 0, 'user_' . $user->ID);
            echo 'Utilisateur ' . $user->ID
        . ' - ' . $user->user_email
        . ' mis à jour.' . PHP_EOL;

    echo "-------------------------" . PHP_EOL;
        }

        echo "✅ Scores users mis à zero\n";
        echo "</pre>";



        exit;




    }




}

