<?php
class JPL_ACF {
    public static function register_user_fields() {
        if( function_exists('acf_add_local_field_group') ) {
            
            acf_add_local_field_group([
                'key' => 'group_jpl_user',
                'title' => 'Profil Joueur JPL',
                'fields' => [
                    [
                        'key' => 'field_pseudo',
                        'label' => 'Pseudo',
                        'name' => 'pseudo',
                        'type' => 'text',
                    ],
                    [
                        'key' => 'field_avatar',
                        'label' => 'Avatar',
                        'name' => 'avatar',
                        'type' => 'image',
                        'return_format' => 'url',
                    ],
                    [
                        'key' => 'field_points',
                        'label' => 'Total de points',
                        'name' => 'total_de_points',
                        'type' => 'number',
                        'default_value' => 0,
                    ],
                    [
                        'key' => 'field_series_jouees',
                        'label' => 'Séries jouées',
                        'name' => 'series_jouees',
                        'type' => 'number',
                        'default_value' => 0,
                    ],
                    [
                        'key' => 'field_ms',
                        'label' => 'Meilleure série',
                        'name' => 'meilleure_serie',
                        'type' => 'number',
                        'default_value' => 0,
                    ],
                    [
                        'key' => 'field_pg',
                        'label' => 'Paris gagnés',
                        'name' => 'paris_gagnes',
                        'type' => 'number',
                        'default_value' => 0,
                    ],
                    [
                        'key' => 'field_pe',
                        'label' => 'Paris éffectués',
                        'name' => 'paris_effectues',
                        'type' => 'number',
                        'default_value' => 0,
                    ],
                     [
                        'key' => 'field_class',
                        'label' => 'Classement',
                        'name' => 'classement',
                        'type' => 'number',
                        'default_value' => 0,
                    ],
                    [
                        'key' => 'field_score_exact',
                        'label' => 'Score exact',
                        'name' => 'score_exact',
                        'type' => 'number',
                        'default_value' => 0,
                    ],
                    [
                        'key' => 'field_serie_en_cours',
                        'label' => 'Série en cours',
                        'name' => 'serie_en_cours',
                        'type' => 'number',
                        'default_value' => 0,
                    ],
                    // Nouveau champ : lien vers un post_type "equipes"
                    [
                        'key' => 'field_equipe',
                        'label' => 'Équipe',
                        'name' => 'equipe',
                        'type' => 'post_object',
                        'post_type' => ['equipes'], // ton CPT
                        'return_format' => 'id', // ou 'object' si tu veux l'objet complet
                        'ui' => 1, // active la sélection via interface
                        'allow_null' => 1,
                    ],
                    [
                        'key' => 'field_nom',
                        'label' => 'Nom',
                        'name' => 'nom',
                        'type' => 'text',
                    ],
                    [
                        'key' => 'field_prenom',
                        'label' => 'Prénom',
                        'name' => 'prenom',
                        'type' => 'text',
                    ],
                    [
                        'key' => 'field_date_naissance',
                        'label' => 'Date de naissance',
                        'name' => 'date_naissance',
                        'type' => 'date_picker',
                        'display_format' => 'd/m/Y',
                        'return_format'  => 'Y-m-d',
                        'first_day'      => 1,
                    ],
                    [
                        'key' => 'field_newsletter',
                        'label' => 'Newsletter',
                        'name' => 'newsletter',
                        'type' => 'true_false',
                        'message' => 'Je souhaite recevoir la newsletter',
                        'ui' => 1,
                    ],
                    [
                        'key' => 'field_offres',
                        'label' => 'Offres',
                        'name' => 'offres',
                        'type' => 'true_false',
                        'message' => 'Je souhaite recevoir les offres partenaires',
                        'ui' => 1,
                    ],
                    [
                        'key' => 'field_consentement_donnees',
                        'label' => 'Consentement d\'utilisation des données',
                        'name' => 'consentement_utilisation_de_donnees',
                        'type' => 'true_false',
                        'message' => 'J’accepte l’utilisation de mes données personnelles',
                        'ui' => 1,
                        'required' => 1,
                    ],
                    [
                        'key' => 'field_email_verified',
                        'label' => 'Email_verified',
                        'name' => 'email_verified',
                        'type' => 'true_false',
                        'ui' => 1,
                    ],
                ],
                'location' => [
                    [
                        [
                            'param' => 'user_role',
                            'operator' => '==',
                            'value' => 'joueur_jpl',
                        ],
                    ],
                ],
            ]);
        }

       
    }

   
}


