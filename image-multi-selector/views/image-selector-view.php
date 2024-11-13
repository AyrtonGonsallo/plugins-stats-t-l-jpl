<div class="wrap">
    <h1>Multiple images tagger</h1>
    <?php 
        $paged = isset($_GET["paged"]) ? $_GET["paged"] : 1;
        $galerie_id = isset($_GET["galerie"]) ? $_GET["galerie"] : 0;

    ?>
    


    <form id="image-selector-form" method="POST" action="">
         <!-- Ajout du formulaire pour sélectionner les judokas et la saison -->
         <div class="image-jdk-form">
         
         <div class="image-flx-1">

         <div class="image-recherche-form">
    <form id="search-form" method="GET" action="" >
        <input type="text" name="search_title" placeholder="Rechercher par titre" value="<?php echo isset($_GET['search_title']) ? esc_attr($_GET['search_title']) : ''; ?>" />
        <input type="hidden" name="paged" value="<?php echo esc_attr($paged); ?>" />
        <input type="hidden" name="galerie_id" value="<?php echo esc_attr($galerie_id); ?>" />
        <input type="hidden" name="page" value="image-multi-selector" />
        <button type="submit" class="rech-btn">Rechercher</button>
    </form>
    </div>
       
         </div>
         <div class="image-meta-form">
            <div class="images-meta-title">Associer des judokas et une saison aux images :</div>

            <?php
            // Liste des judokas
            $selected_judokas = get_posts(array('post_type' => 'judoka', 'numberposts' => -1)); 
            $saisons = array(
                '2023-2024' => '2023-2024',
                '2024-2025' => '2024-2025',
            );
            ?>
            <div class="image-flx-forms">
                <!-- Ajouter Select2 CSS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />

<!-- Ajouter Select2 JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

            <div>
                <!-- Champ pour sélectionner Judoka 1 -->
                <label for="judoka1">Sélectionner Judoka 1 :</label>
                <select name="judoka1" id="judoka1" class="select2">
                    <option value="">Choisir un judoka</option>
                    <?php foreach ($selected_judokas as $judoka): ?>
                        <option value="<?php echo esc_attr($judoka->ID); ?>"><?php echo esc_html($judoka->post_title); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <!-- Champ pour sélectionner Judoka 2 -->
                <label for="judoka2">Sélectionner Judoka 2 :</label>
                <select name="judoka2" id="judoka2" class="select2">
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
                        <option <?echo ($value=='2024-2025')?'selected':'';?> value="<?php echo esc_attr($key); ?>"><?php echo esc_html($value); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            </div>
            
        </div>
        </div>

        <div class="select-actions">
        <div class="col-1"> <input type="checkbox" id="select-all" /> <label for="select-all">Tout choisir</label></div> 
        <div class="col-1"> <input type="checkbox" id="deselect-all" /> <label for="deselect-all">Tout retirer</label></div> 
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
                $img_j1_id=get_post_meta($image->ID, 'related_judoka_1', true);
                $img_j2_id=get_post_meta($image->ID, 'related_judoka_2', true);
                $img_saison=get_post_meta($image->ID, 'related_saison', true);
                echo '<div class="image-container">';
                    echo '<label>';
                    echo '<input type="checkbox" class="image-checkbox" value="' . esc_attr($image->ID) . '" name="selected_images[]">';
                    echo '</label>';
                    echo '<img class="lightbox-trigger" width="100px" src="' . esc_url($image_url) . '" alt="' . esc_attr(get_the_title($image->ID)) . '">';
                    echo '<div>';
                        if($img_j1_id){
                            echo '<div>Judoka 1 : '.esc_html(get_the_title($img_j1_id)).'</div>'; // Récupère la valeur liée à 'related_judoka_1'
                        }
                        if($img_j2_id){
                            echo '<div>Judoka 2 : '.esc_html(get_the_title($img_j2_id)).'</div>'; // Récupère la valeur liée à 'related_judoka_2'
                        }
                        if($img_saison){
                            echo '<div>Saison : '.esc_html(($img_saison)).'</div>'; // Récupère la valeur liée à 'related_saison'
                        }
                    echo '</div>';
                echo '</div>';
            }
        } else if ($images_galerie) {
             echo '<h3>'.$titre_galerie.'</h3>';
            foreach ($images_galerie as $image) {
                $image_url = wp_get_attachment_url($image["ID"]);
                $img_j1_id=get_post_meta($image["ID"], 'related_judoka_1', true);
                $img_j2_id=get_post_meta($image["ID"], 'related_judoka_2', true);
                $img_saison=get_post_meta($image["ID"], 'related_saison', true);
                echo '<div class="image-container">';
                    echo '<label>';
                        echo '<input type="checkbox" class="image-checkbox" value="' . esc_attr($image["ID"]) . '" name="selected_images[]">';
                    echo '</label>';
                    echo '<img class="lightbox-trigger" width="100px" src="' . esc_url($image_url) . '" alt="' . esc_attr(get_the_title($image["ID"])) . '">';
                    echo '<div>';
                    if($img_j1_id){
                        echo '<div>Judoka 1 : '.esc_html(get_the_title($img_j1_id)).'</div>'; // Récupère la valeur liée à 'related_judoka_1'
                    }
                    if($img_j2_id){
                        echo '<div>Judoka 2 : '.esc_html(get_the_title($img_j2_id)).'</div>'; // Récupère la valeur liée à 'related_judoka_2'
                    }
                    if($img_saison){
                        echo '<div>Saison : '.esc_html(($img_saison)).'</div>'; // Récupère la valeur liée à 'related_saison'
                    }
                    echo '</div>';
                echo '</div>';
            }
        } 
        else {
            echo '<p>Aucune image trouvée.</p>';
        }

        if(!$galerie_id){
            // Pagination
            $total_images = count(get_posts(array(
                'post_type' => 'attachment',
                'post_mime_type' => 'image',
                'numberposts' => -1,
                's' => $search_title
            )));
            $total_pages = ceil($total_images / $posts_per_page);

            echo '<div class="pagination">';
            echo '<div class="image-flx-forms">';
            if ($paged > 1) {
                echo '<a href="' . add_query_arg(array('paged' => $paged - 1, 'search_title' => $search_title)) . '">&laquo; Précédent</a>';
            }
            if ($paged < $total_pages) {
                echo '<a href="' . add_query_arg(array('paged' => $paged + 1, 'search_title' => $search_title)) . '">Suivant &raquo;</a>';
            }
            echo '</div>';
                echo '<div class="btn-image-form-2">';
                echo '<button type="submit" id="submit-selected-images">Valider</button>';
                echo '</div>';
            echo '</div>';
        }else{ ?>
            <div class="btn-image-form-2">
                <button type="submit" id="submit-selected-images">Valider</button>
            </div>
        <?php }
        
        ?>

       
    </form>
</div>
<div id="lightbox" class="lightbox">
    <span class="close">&times;</span>
    <img class="lightbox-content" id="lightbox-img">
</div>
