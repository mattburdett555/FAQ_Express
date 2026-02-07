<?php
if (!defined('ABSPATH')) exit;

class FAQ_Express {
    public function __construct() {
        add_action('init', [$this,'register_faq_cpt']);
        add_action('add_meta_boxes', [$this,'add_meta_boxes']);
        add_action('save_post', [$this,'save_meta_boxes']);
        add_shortcode('faq', [$this,'faq_shortcode']);
        add_action('wp_enqueue_scripts', [$this,'enqueue_scripts']);
        add_action('template_redirect', [$this,'restrict_frontend']);
    }

    public function register_faq_cpt() {
        $labels = [
            'name' => 'FAQs',
            'singular_name' => 'FAQ',
            'menu_name' => 'FAQs',
            'add_new_item' => 'Add New FAQ',
            'edit_item' => 'Edit FAQ',
            'not_found' => 'No FAQs found.',
            'not_found_in_trash' => 'No FAQs found in Trash.',
        ];
        $args = [
            'labels' => $labels,
            'public' => true,
            'has_archive' => false,
            'publicly_queryable' => false,
            'show_in_nav_menus' => false,
            'show_in_menu' => true,
            'supports' => ['title'],
        ];
        register_post_type('faq',$args);
    }

    public function add_meta_boxes() {
        remove_meta_box('postcustom','faq','normal');
        add_meta_box('faq_items_meta','FAQ Items',[$this,'faq_items_meta_box_callback'],'faq','normal','default');
        add_meta_box('faq_jsonld_meta','Enable JSON-LD Schema',[$this,'jsonld_meta_box_callback'],'faq','side','default');
        add_meta_box('faq_show_title_meta','Display FAQ Title on Frontend',[$this,'show_title_meta_box_callback'],'faq','side','default');
        add_meta_box('faq_shortcode_meta','FAQ Shortcode',[$this,'shortcode_meta_box_callback'],'faq','normal','high');
    }

    public function faq_items_meta_box_callback($post) {
        wp_nonce_field('faq_items_nonce','faq_items_nonce_field');
        $faq_items = get_post_meta($post->ID,'_faq_items',true);
        if(!is_array($faq_items) || empty($faq_items)) $faq_items = [['question'=>'','answer'=>'']];
        ?>
        <div id="faq-items-container">
        <?php foreach($faq_items as $i=>$item): ?>
            <div class="faq-item-row" style="margin-bottom:10px;">
                <input type="text" name="faq_items[<?php echo $i; ?>][question]" placeholder="Question" style="width:100%; margin-bottom:5px;" value="<?php echo esc_attr($item['question']); ?>">
                <textarea name="faq_items[<?php echo $i; ?>][answer]" placeholder="Answer" style="width:100%; height:80px;"><?php echo esc_textarea($item['answer']); ?></textarea>
                <button class="remove-faq-item button" type="button">Remove</button>
            </div>
        <?php endforeach; ?>
        </div>
        <button id="add-faq-item" class="button" type="button">Add Question/Answer</button>
        <script>
        (function($){
            $('#add-faq-item').on('click', function(){
                var i = $('#faq-items-container .faq-item-row').length;
                var row = '<div class="faq-item-row" style="margin-bottom:10px;">'+
                          '<input type="text" name="faq_items['+i+'][question]" placeholder="Question" style="width:100%; margin-bottom:5px;">'+
                          '<textarea name="faq_items['+i+'][answer]" placeholder="Answer" style="width:100%; height:80px;"></textarea>'+
                          '<button class="remove-faq-item button" type="button">Remove</button></div>';
                $('#faq-items-container').append(row);
            });
            $(document).on('click','.remove-faq-item',function(){ $(this).closest('.faq-item-row').remove(); });
        })(jQuery);
        </script>
        <?php
    }

    public function jsonld_meta_box_callback($post) {
        wp_nonce_field('faq_jsonld_nonce','faq_jsonld_nonce_field');
        $v = get_post_meta($post->ID,'_faq_jsonld',true);
        echo '<label><input type="checkbox" name="faq_jsonld" value="1" '.checked(1,$v,false).'> Enable FAQ schema (ld+json)</label>';
    }

    public function show_title_meta_box_callback($post) {
        wp_nonce_field('faq_show_title_nonce','faq_show_title_nonce_field');
        $v = get_post_meta($post->ID,'_faq_show_title',true);
        echo '<label><input type="checkbox" name="faq_show_title" value="1" '.checked(1,$v,false).'> Show FAQ Title</label>';
    }

    public function shortcode_meta_box_callback($post) {
        $sc = '[faq id="'.$post->ID.'"]';
        echo '<input type="text" readonly value="'.esc_attr($sc).'" style="width:100%; font-size:14px;" onclick="this.select();">';
    }

    public function save_meta_boxes($post_id) {
        if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if(!current_user_can('edit_post',$post_id)) return;

        if(isset($_POST['faq_items_nonce_field']) && wp_verify_nonce($_POST['faq_items_nonce_field'],'faq_items_nonce')){
            if(!empty($_POST['faq_items']) && is_array($_POST['faq_items'])){
                $clean = [];
                foreach($_POST['faq_items'] as $item){
                    if(!empty($item['question']) && !empty($item['answer'])){
                        $clean[] = [
                            'question' => sanitize_text_field($item['question']),
                            'answer' => wp_kses($item['answer'], ['a'=>['href'=>[],'target'=>[],'rel'=>[]]])
                        ];
                    }
                }
                update_post_meta($post_id,'_faq_items',$clean);
            } else {
                delete_post_meta($post_id,'_faq_items');
            }
        }

        if(isset($_POST['faq_jsonld_nonce_field']) && wp_verify_nonce($_POST['faq_jsonld_nonce_field'],'faq_jsonld_nonce')){
            $v = isset($_POST['faq_jsonld']) ? 1 : 0;
            update_post_meta($post_id,'_faq_jsonld',$v);
        }

        if(isset($_POST['faq_show_title_nonce_field']) && wp_verify_nonce($_POST['faq_show_title_nonce_field'],'faq_show_title_nonce')){
            $v = isset($_POST['faq_show_title']) ? 1 : 0;
            update_post_meta($post_id,'_faq_show_title',$v);
        }
    }

    public function faq_shortcode($atts){
        $atts = shortcode_atts([
            'id'=>0,
            'html_id'=>'',
            'html_name'=>'',
            'html_class'=>''
        ], $atts, 'faq');

        $args = ['post_type'=>'faq','posts_per_page'=>-1];
        if($atts['id']) $args['p'] = intval($atts['id']);
        $faqs = get_posts($args);
        if(!$faqs) return '<p>No FAQs found.</p>';

        $container_attrs = '';
        if(!empty($atts['html_id'])) $container_attrs .= ' id="'.esc_attr($atts['html_id']).'"';
        if(!empty($atts['html_name'])) $container_attrs .= ' name="'.esc_attr($atts['html_name']).'"';
        $container_classes = 'faq-list';
        if(!empty($atts['html_class'])) $container_classes .= ' '.esc_attr($atts['html_class']);
        $container_attrs .= ' class="'.$container_classes.'"';

        $out = '<div'.$container_attrs.'>';
        $schema = [];

        foreach($faqs as $faq){
            $show_title = get_post_meta($faq->ID,'_faq_show_title',true);
            if($show_title) $out .= '<h2 class="faq-section-title">'.esc_html(get_the_title($faq->ID)).'</h2>';

            $items = get_post_meta($faq->ID,'_faq_items',true);
            if(!$items || !is_array($items)) continue;

            foreach($items as $item){
                $out .= '<details class="faq-item"><summary class="faq-question">'.esc_html($item['question']).'</summary>';
                $out .= '<div class="faq-answer">'.wpautop($item['answer']).'</div></details>';

                if(get_post_meta($faq->ID,'_faq_jsonld',true)){
                    $schema[] = [
                        '@type'=>'Question',
                        'name'=>$item['question'],
                        'acceptedAnswer'=>['@type'=>'Answer','text'=>wp_strip_all_tags($item['answer'])]
                    ];
                }
            }
        }

        $out .= '</div>';

        if($schema){
            $json = ['@context'=>'https://schema.org','@type'=>'FAQPage','mainEntity'=>$schema];
            $out .= '<script type="application/ld+json">'.wp_json_encode($json).'</script>';
        }

        return $out;
    }

    public function enqueue_scripts(){
    	wp_enqueue_style('faq-express-style', plugin_dir_url(__FILE__) . '../assets/css/faq-style.css');
    }

    public function restrict_frontend(){
        if(is_singular('faq')){
            wp_redirect(home_url());
            exit;
        }
    }
}
