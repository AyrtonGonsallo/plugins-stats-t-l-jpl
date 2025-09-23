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
    }
}
