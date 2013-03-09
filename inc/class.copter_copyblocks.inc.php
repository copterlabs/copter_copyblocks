<?php

/**
 * Copter Copy Blocks — A WordPress Plugin
 * 
 * @author Jason Lengstorf <jason.lengstorf@copterlabs.com>
 */
class Copter_Copyblocks extends Copter_Plugin
{
    public $custom        = array(),
           $meta_fields   = array();

    /**
     * Initializes the plugin
     *
     * @return void
     */
    public function __construct(  )
    {
        date_default_timezone_set('America/Los_Angeles');

        $this->meta_fields = array(
            array(
                'slug'        => 'shortcode',
                'label'       => 'Shortcode',
                'type'        => 'text',
            ),
            array(
                'slug'        => 'copyblock',
                'label'       => 'Copy Block',
                'type'        => 'textarea',
            ),
            array(
                'slug'        => 'addtags',
                'label'       => 'Add HTML tags?',
                'type'        => 'checkbox',
            ),
        );

        add_action('init', array(&$this, 'register_custom_post_type'));
        add_action('init', array(&$this, 'register_shortcodes'));
        add_action('admin_init', array(&$this, 'copyblocks_init'));
        add_action('admin_enqueue_scripts', array(&$this, 'add_admin_styles'));
        add_action('save_post', array(&$this, 'save_custom_meta'));
    }

    /**
     * Initializes the admin interface for the copyblock post type
     *
     * @return void
     */
    public function copyblocks_init(  )
    {
        // Coaching Program Meta
        remove_meta_box('commentstatusdiv', 'copyblock', 'normal');
        remove_meta_box('commentsdiv', 'copyblock', 'normal');
        remove_meta_box('postexcerpt', 'copyblock', 'normal');
        remove_meta_box('postslug', 'copyblock', 'normal');
        add_meta_box(
            'copter_copyblocks_meta', 
            'Copy Block Information', 
            array(&$this, 'add_meta_box'), 
            'copyblock', 
            'normal', 
            'high'
        );
    }

    /**
     * Enqueues custom styles for the admin interface
     *
     * @return void
     */
    public function add_admin_styles(  )
    {
        $style  = plugins_url() 
                . '/copter_copyblocks/styles/copyblocks-dashboard.css';
        wp_enqueue_style('copter_copyblocks_styles', $style);
    }

    /**
     * Adds a meta box to the copyblocks editing page
     *
     * @param $post object  The post object
     * @return      string  Markup for the meta box
     */
    public function add_meta_box( $post )
    {
        $this->custom = get_post_custom($post->ID);
        ?>
    <div class="copter-metabox">
        <div class="copter-input-container" id="copter-general">
            <table class="copter-inputs">
        <?php

            foreach ($this->meta_fields as $meta) {
                if (isset($meta['placeholder'])) {
                    $placeholder = $meta['placeholder'];
                } else {
                    $placeholder = NULL;
                }

                $this->add_field(
                    $meta['slug'], 
                    $meta['label'], 
                    $meta['type'], 
                    $placeholder
                );
            }

        ?>
            </table><!-- .copter-inputs -->
        </div><!-- .copter-input-container#copter-general -->
    </div><!-- end .copter-metabox -->
        <?php
    }

    /**
     * Loads all copyblocks and loops through them to create shortcodes
     *
     * @return void
     */
    public function register_shortcodes(  ) {
        $args = array(
            'numberposts' => -1,
            'post_type'   => 'copyblock',
            'post_status' => 'publish',
        );
        $copyblocks = get_posts($args);

        foreach ($copyblocks as $cb) {

            // Loads the copy blocks meta fields
            $shortcode = get_post_meta(
                $cb->ID, 
                self::PFX.'shortcode',
                TRUE
            );
            $copyblock = get_post_meta(
                $cb->ID, 
                self::PFX.'copyblock',
                TRUE
            );
            $addtags = get_post_meta(
                $cb->ID, 
                self::PFX.'addtags',
                TRUE
            );

            // Applies the_content filter if the addtags box was checked
            if ($addtags==1) {
                $content = apply_filters('the_content', $copyblock);
            } else {
                $content = $copyblock;
            }

            // Builds a callback function for the shortcode
            $callback = create_function(
                NULL,
                "return stripslashes('" . addslashes($content) . "');"
            );

            // Registers the shortcode
            add_shortcode($shortcode,$callback);
        }
    }

    /**
     * Registers the copy blocks custom post type
     *
     * @return void
     */
    public function register_custom_post_type(  ) {
        $labels = array(
                'name'                  => __('Copy Blocks'),
                'singular_name'         => __('Copy Block'),
                'add_new'               => __('Add New'),
                'add_new_item'          => __('Add New Copy Block'),
                'edit_item'             => __('Edit Copy Block'),
                'new_item'              => __('New Copy Block'),
                'all_items'             => __('All Copy Blocks'),
                'view_item'             => __('View Copy Block'),
                'search_items'          => __('Search Programs'),
                'not_found'             => __('No copy blocks found'),
                'not_found_in_trash'    => __('No copy blocks in the trash'),
                'parent_item_colon'     => '',
                'menu_name'             => 'Copy Blocks',
            );
        $args = array(
                'labels'                => $labels,
                'public'                => FALSE,
                'publicly_queryable'    => FALSE,
                'show_ui'               => TRUE,
                'show_in_menu'          => TRUE,
                'query_var'             => TRUE,
                'rewrite'               => TRUE,
                'capability_type'       => 'post',
                'has_archive'           => FALSE,
                'hierarchical'          => FALSE,
                'menu_position'         => 10,
                'supports'              => array('title'),
                
            );
        register_post_type('copyblock', $args);
    }

    /**
     * Retrieves the value of a custom meta field
     *
     * @param $key      string  The field ID, unprefixed
     * @param $default  string  Optional default value for the field
     * @return          mixed   The field value or NULL if empty w/no default
     */
    protected function get_value( $key, $default=NULL ) {
        $id = $this->pfx($key);
        if (isset($this->custom[$id]) && !empty($this->custom[$id][0])) {
            return $this->custom[$id][0];
        } else {
            $default = get_option($id.$default);
            if (isset($default)) {
                return $default;
            }
        }

        return NULL;
    }

    /**
     * Saves the value of a field
     *
     * @param $post_id  int The post ID
     * @return          int The post ID
     */
    public function save_custom_meta( $post_id )
    {
        if (!$post_id) {
            global $post;
            $post_id = $post->ID;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }

        foreach ($this->meta_fields as $meta) {
            $fieldname = $this->pfx($meta['slug']);

            if ($meta['type']==='checkbox' && !isset($_POST[$fieldname])) {
                $_POST[$fieldname] = 0;
            }

            if (isset($_POST[$fieldname])) {
                update_post_meta($post_id, $fieldname, $_POST[$fieldname]);
            }
        }
    }

}
