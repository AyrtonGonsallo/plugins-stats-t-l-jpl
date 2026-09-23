<?php
class JPL_Roles {
    public static function register_roles() {
        add_role(
            'joueur_jpl',
            'Joueur JPL',
            [
                'read' => true,
                'edit_posts' => false,
                'delete_posts' => false,
            ]
        );

       //remove_role('admin_equipe_jpl');

        add_role(
            'admin_equipe_jpl',
            'admin_equipe JPL',
            [
                'read' => true,
                'upload_files' => true,

                // Équipes
                'edit_equipe' => true,
                'edit_equipes' => true,
                'edit_published_equipes' => true,
                'publish_equipes' => false,
                'delete_equipe' => false,
                'delete_equipes' => false,
                'read_private_equipes' => true,
                'edit_others_equipes' => true,

                // Judokas
                'edit_judoka' => true,
                'edit_judokas' => true,
                'edit_published_judokas' => true,
                'publish_judokas' => false,
                'delete_judoka' => false,
                'delete_judokas' => false,
                'read_private_judokas' => true,
                'edit_others_judokas' => true,
            ]
        );

        add_role(
            'photographe_jpl',
            'Photographe JPL',
            [
                   'read'                  => true,
                    'upload_files'          => true,  // accéder à la médiathèque
                    'edit_galerie'          => true,  // modifier sa galerie
                    'edit_galeries'         => true,  // modifier ses galeries
                    'edit_published_galeries' => true, // modifier ses galeries publiées
                    'publish_galeries'      => true,
                    'delete_galerie'        => false, // ne pas supprimer
                    'delete_galeries'       => false,
                    'read_private_galeries' => true,
                    'edit_others_galeries' => false,  // interdit les galeries des autres
            ]
        );

    }
}
