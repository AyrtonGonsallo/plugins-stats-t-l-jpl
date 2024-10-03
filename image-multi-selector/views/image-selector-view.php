<div class="wrap">
    <h1>Multiple images tagger</h1>
    <?php 
        $paged = isset($_GET["paged"]) ? $_GET["paged"] : 1;
        $galerie_id = isset($_GET["galerie"]) ? $_GET["galerie"] : 0;

    ?>
    <form id="search-form" method="GET" action="">
        <input type="text" name="search_title" placeholder="Rechercher par titre" value="<?php echo isset($_GET['search_title']) ? esc_attr($_GET['search_title']) : ''; ?>" />
        <input type="hidden" name="paged" value="<?php echo esc_attr($paged); ?>" />
        <input type="hidden" name="galerie_id" value="<?php echo esc_attr($galerie_id); ?>" />
        <input type="hidden" name="page" value="image-multi-selector" />
        <button type="submit">Rechercher</button>
    </form>

   



    <form id="image-selector-form" method="POST" action="">
         <!-- Ajout du formulaire pour sélectionner les judokas et la saison -->
         <div class="image-meta-form">
            <div>Associer des judokas et une saison aux images</div>

            <?php
            // Liste des judokas
            $selected_judokas = get_posts(array('post_type' => 'judoka', 'numberposts' => -1)); 
            $saisons = array(
                '2023-2024' => '2023-2024',
                '2024-2025' => '2024-2025',
            );
            ?>
            <div>
                <!-- Champ pour sélectionner Judoka 1 -->
                <label for="judoka1">Sélectionner Judoka 1 :</label>
                <select name="judoka1" id="judoka1">
                    <option value="">Choisir un judoka</option>
                    <?php foreach ($selected_judokas as $judoka): ?>
                        <option value="<?php echo esc_attr($judoka->ID); ?>"><?php echo esc_html($judoka->post_title); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <!-- Champ pour sélectionner Judoka 2 -->
                <label for="judoka2">Sélectionner Judoka 2 :</label>
                <select name="judoka2" id="judoka2">
                    <option value="">Choisir un judoka</option>
                    <?php foreach ($selected_judokas as $judoka): ?>
                        <option value="<?php echo esc_attr($judoka->ID); ?>"><?php echo esc_html($judoka->post_title); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="saison">Sélectionner la Saison :</label>
                <select name="saison" id="saison">
                    <option value="">Choisir une saison</option>
                    <?php foreach ($saisons as $key => $value): ?>
                        <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($value); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <button type="submit" id="submit-selected-images">Associer aux images sélectionnées</button>
            </div>
        </div>


        <div class="select-actions">
            <input type="checkbox" id="select-all" /> <label for="select-all">Tout choisir</label>
            <input type="checkbox" id="deselect-all" /> <label for="deselect-all">Tout retirer</label>
        </div> 

        <?php
        // Pagination variables
        $posts_per_page = 100; // Nombre d'images par page
        $search_title = isset($_GET['search_title']) ? sanitize_text_field($_GET['search_title']) : '';
        if($galerie_id==0){
            // Get images
            $images_query_args = array(
                'post_type' => 'attachment',
                'post_mime_type' => 'image',
                'numberposts' => $posts_per_page,
                'offset' => ($paged - 1) * $posts_per_page,
            );

            // Si une recherche par titre est effectuée, ajoutez un filtre
            if (!empty($search_title)) {
                $images_query_args['s'] = $search_title;
            }

            $images = get_posts($images_query_args);
        }else{
            
            $images_galerie = get_field('photos',$galerie_id);
            $titre_galerie = get_field('titre',$galerie_id);
        }
                    

        if ($images) {
            
            foreach ($images as $image) {
                $image_url = wp_get_attachment_url($image->ID);
                
                echo '<div class="image-container">';
                echo '<label>';
                echo '<input type="checkbox" class="image-checkbox" value="' . esc_attr($image->ID) . '" name="selected_images[]">';
                echo '</label>';
                echo '<img class="lightbox-trigger" width="100px" src="' . esc_url($image_url) . '" alt="' . esc_attr(get_the_title($image->ID)) . '">';

                echo '</div>';
            }
        } else if ($images_galerie) {
             echo '<h3>'.$titre_galerie.'</h3>';
            foreach ($images_galerie as $image) {
                $image_url = wp_get_attachment_url($image["ID"]);
                
                echo '<div class="image-container">';
                echo '<label>';
                echo '<input type="checkbox" class="image-checkbox" value="' . esc_attr($image["ID"]) . '" name="selected_images[]">';
                echo '</label>';
                echo '<img class="lightbox-trigger" width="100px" src="' . esc_url($image_url) . '" alt="' . esc_attr(get_the_title($image->ID)) . '">';

                echo '</div>';
            }
        } 
        else {
            echo '<p>Aucune image trouvée.</p>';
        }

        // Pagination
        $total_images = count(get_posts(array(
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'numberposts' => -1,
            's' => $search_title
        )));
        $total_pages = ceil($total_images / $posts_per_page);

        echo '<div class="pagination">';
        if ($paged > 1) {
            echo '<a href="' . add_query_arg(array('paged' => $paged - 1, 'search_title' => $search_title)) . '">&laquo; Précédent</a>';
        }
        if ($paged < $total_pages) {
            echo '<a href="' . add_query_arg(array('paged' => $paged + 1, 'search_title' => $search_title)) . '">Suivant &raquo;</a>';
        }
        echo '</div>';
        ?>

       
    </form>
</div>
<div id="lightbox" class="lightbox">
    <span class="close">&times;</span>
    <img class="lightbox-content" id="lightbox-img">
</div>