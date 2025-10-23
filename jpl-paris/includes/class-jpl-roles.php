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
