<?php
/*
Plugin Name: Dynamic Image Plugin
Description: Plugin to add text to a dynamic image.
Version: 1.0
Author: Josef Weslley
*/

// Adiciona um metabox para upload de imagem no painel de edição de postagem
add_action('add_meta_boxes', 'dynamic_image_meta_box');

function dynamic_image_meta_box() {
    add_meta_box(
        'dynamic_image_meta_box',
        __('Dynamic Image Settings', 'dynamic-image-plugin'),
        'dynamic_image_meta_box_callback',
        'post', // Pode ajustar para 'page' se quiser que esteja disponível apenas para páginas
        'side',
        'default'
    );
}

// Callback para exibir o conteúdo do metabox
function dynamic_image_meta_box_callback($post) {
    // Adiciona um campo de upload de imagem
    wp_nonce_field('dynamic_image_meta_box', 'dynamic_image_meta_box_nonce');
    $image_url = get_post_meta($post->ID, 'dynamic_image_url', true);
    ?>
    <label for="dynamic_image"><?php _e('Select Image:', 'dynamic-image-plugin'); ?></label><br>
    <input type="text" id="dynamic_image" name="dynamic_image_url" value="<?php echo esc_attr($image_url); ?>" readonly>
    <button id="upload_image_button" class="button"><?php _e('Upload Image', 'dynamic-image-plugin'); ?></button>
    <script>
        jQuery(document).ready(function($) {
            $('#upload_image_button').click(function(e) {
                e.preventDefault();
                var custom_uploader = wp.media({
                    title: '<?php _e('Choose Image', 'dynamic-image-plugin'); ?>',
                    button: {
                        text: '<?php _e('Use Image', 'dynamic-image-plugin'); ?>'
                    },
                    multiple: false
                });
                custom_uploader.on('select', function() {
                    var attachment = custom_uploader.state().get('selection').first().toJSON();
                    $('#dynamic_image').val(attachment.url);
                });
                custom_uploader.open();
            });
        });
    </script>
    <?php
}

// Salva a URL da imagem quando a postagem for salva
add_action('save_post', 'save_dynamic_image_url');

function save_dynamic_image_url($post_id) {
    if (!isset($_POST['dynamic_image_meta_box_nonce']) || !wp_verify_nonce($_POST['dynamic_image_meta_box_nonce'], 'dynamic_image_meta_box')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (isset($_POST['post_type']) && 'page' == $_POST['post_type']) {
        if (!current_user_can('edit_page', $post_id)) {
            return;
        }
    } else {
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }
    if (!isset($_POST['dynamic_image_url'])) {
        return;
    }
    $image_url = sanitize_text_field($_POST['dynamic_image_url']);
    update_post_meta($post_id, 'dynamic_image_url', $image_url);
}

// Adiciona o shortcode para inserir texto na imagem
add_shortcode('dynamic_image', 'dynamic_image_shortcode');

function dynamic_image_shortcode($atts, $content = null) {
    // Recupera a URL da imagem salva no metadado da postagem
    global $post;
    $image_url = get_post_meta($post->ID, 'dynamic_image_url', true);

    // Verifica se há uma URL de imagem válida
    if (empty($image_url)) {
        return __('Please select an image from the Dynamic Image Settings metabox.', 'dynamic-image-plugin');
    }

    // Carrega a imagem
    $image = imagecreatefromjpeg($image_url);

    // Define a cor do texto (preto)
    $text_color = imagecolorallocate($image, 0, 0, 0);

    // Define a fonte do texto (substitua com o caminho para sua fonte)
    $font = 'caminho/para/sua/fonte.ttf';

    // Define o texto a ser inserido (você pode ajustar a posição conforme necessário)
    $text = $content;

    // Insere o texto na imagem (ajuste as coordenadas conforme necessário)
    imagettftext($image, 20, 0, 50, 50, $text_color, $font, $text);

    // Define os cabeçalhos para uma imagem JPEG
    header('Content-Type: image/jpeg');

    // Exibe a imagem
    imagejpeg($image);

    // Libera a memória ocupada pela imagem
    imagedestroy($image);
}
