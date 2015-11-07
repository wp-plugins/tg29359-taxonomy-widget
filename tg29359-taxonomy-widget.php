<?php
/**
 * @package tg29359 taxonomy widget
 */
/*
Plugin Name: tg29359 taxonomy widget
Plugin URI: http://tg29359.rdy.jp/app/tg29359-taxonomy-widget/
Description: Displays a list or dropdown of taxonomies in a sidebar widget.
Version: 0.0.6
Author: tg29359
Author URI: http://tg29359.rdy.jp/
DomainPath: /languages
Text Domain: tg29359-taxonomy-widget
License: GPL2
*/
/*  Copyright 2015 tg29359 (email : tg29359@tg29359.rdy.jp)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
// タクソノミーの一覧を表示するウィジェットでドロップダウン処理をする時のためのWalker
class tg29359_Walker_TaxonomyDropdown extends Walker
{
    public $tree_type = 'category';
    public $db_fields = array('parent' => 'parent', 'id' => 'term_id');

    public function start_el(&$output, $category, $depth = 0, $args = array(), $id = 0)
    {
        $walker_indention = $args['walker_indention'];
        if(empty($walker_indention)){
            $walker_indention = '&#045;&#045;&#045;';
        }
        $pad = str_repeat($walker_indention, $depth * 1);
        $cat_name = apply_filters('list_cats', $category->name, $category);
        $link = get_term_link($category->slug, $category->taxonomy);
        $output .= "\t<option class=\"level-$depth\" value=\"".$link."\"";
        if ($category->term_id == $args['selected']) {
            $output .= ' selected="selected"';
        }
        $output .= '>';
        $output .= $pad.$cat_name;
        if ($args['show_count']) {
            $output .= '&nbsp;&nbsp;('. number_format_i18n( $category->count ) .')';
        }
        $output .= "</option>\n";
    }
}

// タクソノミーの一覧を表示するウィジェット
class tg29359_Widget_Taxonomies extends WP_Widget
{
    private $taxonomy = 'category';
    public  $walker = null;

    public function __construct()
    {
        if (!is_textdomain_loaded('tg29359-taxonomy-widget')) {
            load_plugin_textdomain('tg29359-taxonomy-widget', false, basename( dirname( __FILE__ ) ) . '/languages');
        }
        $widget_ops = array('classname' => 'tg29359_taxonomies', 'description' => __('A list or dropdown of Taxonomies.', 'tg29359-taxonomy-widget'));
        parent::__construct('tg29359_taxonomies', __('tg29359 taxonomy widget', 'tg29359-taxonomy-widget'), $widget_ops);
        if ($this->walker == null) {
            $this->walker = new tg29359_Walker_TaxonomyDropdown;
        }
    }

    public function widget($args, $instance)
    {
        static $first_dropdown = true;

        $title = apply_filters('widget_title', empty($instance['title']) ? __('Taxonomies', 'tg29359-taxonomy-widget') : $instance['title'], $instance, $this->id_base);

        $c = !empty($instance['count']) ? '1' : '0';
        $pad_counts = !empty($instance['pad_counts']) ? '1' : '0';
        $h = !empty($instance['hierarchical']) ? '1' : '0';
        $hide_empty = !empty($instance['hide_empty']) ? '1' : '0';
        $hide_if_empty = !empty($instance['hide_if_empty']) ? '1' : '0';
        $d = !empty($instance['dropdown']) ? '1' : '0';
        if (!empty($instance['taxonomy'])) {
            $this->taxonomy = $instance['taxonomy'];
        }
        if (!empty($instance['orderby'])) {
            $orderby = $instance['orderby'];
        } else {
            $orderby = '';
        }
        if (!empty($instance['order'])) {
            $order = $instance['order'];
        } else {
            $order = '';
        }

        echo $args['before_widget'];
        if ($title) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        $cat_args = array(
            'taxonomy'      => $this->taxonomy,
            'show_count'    => $c,
            'pad_counts'    => $pad_counts,
            'hierarchical'  => $h,
            'hide_empty'    => $hide_empty,
            'orderby'       => $orderby,
            'order'         => $order
        );

        if ($d) {
            $dropdown_id = ($first_dropdown) ? 'tg29359_taxonomies_tax' : "{$this->id_base}-dropdown-{$this->number}";
            $first_dropdown = false;

            echo '<label class="screen-reader-text" for="' . esc_attr( $dropdown_id ) . '">' . $title . '</label>';
 
            if (!empty($instance['select_xxx_for_dropdown'])) {
                $cat_args['show_option_none'] = $instance['select_xxx_for_dropdown'];
            } else {
                $cat_args['show_option_none'] = __('Select Taxonomy', 'tg29359-taxonomy-widget');
            }
            $cat_args['id'  ] = $dropdown_id;
            $cat_args['name'] = 'tg29359_taxonomies_tax';
            $cat_args['walker'] = $this->walker;
            if (!empty($instance['walker_indention'])) {
                $cat_args['walker_indention'] = $instance['walker_indention'];
            } else {
                $cat_args['walker_indention'] = '';
            }
	        $cat_args['hide_if_empty'] = $hide_if_empty;

            wp_dropdown_categories(apply_filters('widget_categories_dropdown_args', $cat_args));
?>

<script type='text/javascript'>
/* <![CDATA[ */
(function() {
    var dropdown = document.getElementById("<?php echo esc_js( $dropdown_id ); ?>");
    function onTaxChange() {
        if (dropdown.options[dropdown.selectedIndex].value != 'null') {
            location.href = dropdown.options[dropdown.selectedIndex].value;
        }
    }
    dropdown.onchange = onTaxChange;
})();
/* ]]> */
</script>

<?php
        } else {
?>
            <ul>
<?php
            $cat_args['title_li'] = '';
            if (!empty($instance['no_taxonomies_for_list'])) {
                $cat_args['show_option_none'] = $instance['no_taxonomies_for_list'];
            } else {
                $cat_args['show_option_none'] = __('No taxonomies', 'tg29359-taxonomy-widget');
            }

            wp_list_categories(apply_filters('widget_categories_args', $cat_args));
?>
            </ul>
<?php
        }
        echo $args['after_widget'];
    }

    public function update($new_instance, $old_instance)
    {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['select_xxx_for_dropdown'] = strip_tags($new_instance['select_xxx_for_dropdown']);
        $instance['walker_indention'] = strip_tags($new_instance['walker_indention']);
        $instance['no_taxonomies_for_list'] = strip_tags($new_instance['no_taxonomies_for_list']);
        $instance['count'] = !empty($new_instance['count']) ? 1 : 0;
        $instance['pad_counts'] = !empty($new_instance['pad_counts']) ? 1 : 0;
        $instance['hierarchical'] = !empty($new_instance['hierarchical']) ? 1 : 0;
        $instance['hide_empty'] = !empty($new_instance['hide_empty']) ? 1 : 0;
        $instance['hide_if_empty'] = !empty($new_instance['hide_if_empty']) ? 1 : 0;
        $instance['dropdown'] = !empty($new_instance['dropdown']) ? 1 : 0;

        $taxonomies = $this->get_taxonomies();
        $instance['taxonomy'] = 'category';
        if (in_array($new_instance['taxonomy'], $taxonomies)) {
            $instance['taxonomy'] = $new_instance['taxonomy'];
        }

        $orderby = array('name', 'ID', 'slug', 'count', 'term_group', 'menu_order');
        if (in_array($new_instance['orderby'], $orderby)) {
            $instance['orderby'] = $new_instance['orderby'];
        }
        
        $order = array('ASC', 'DESC');
        if (in_array($new_instance['order'], $order)) {
            $instance['order'] = $new_instance['order'];
        }
        return $instance;
    }

    public function form($instance)
    {
        //Defaults
        $instance = wp_parse_args((array)$instance, array( 'title' => ''));
        $title = esc_attr($instance['title']);
        $instance = wp_parse_args((array)$instance, array( 'select_xxx_for_dropdown' => ''));
        $select_xxx_for_dropdown = esc_attr($instance['select_xxx_for_dropdown']);
        $instance = wp_parse_args((array)$instance, array( 'walker_indention' => ''));
        $walker_indention = esc_attr($instance['walker_indention']);
        $instance = wp_parse_args((array)$instance, array( 'no_taxonomies_for_list' => ''));
        $no_taxonomies_for_list = esc_attr($instance['no_taxonomies_for_list']);
        $count = isset($instance['count']) ? (bool)$instance['count'] : false;
        $pad_counts = isset($instance['pad_counts']) ? (bool)$instance['pad_counts'] : false;
        $hierarchical = isset($instance['hierarchical']) ? (bool)$instance['hierarchical'] : false;
        $hide_empty = isset($instance['hide_empty']) ? (bool)$instance['hide_empty'] : false;
        $hide_if_empty = isset($instance['hide_if_empty']) ? (bool)$instance['hide_if_empty'] : false;
        $dropdown = isset($instance['dropdown']) ? (bool) $instance['dropdown'] : false;
        $taxonomy = 'category';
        if (!empty( $instance['taxonomy'])) {
            $taxonomy = $instance['taxonomy'];
        }
        $taxonomies = $this->get_taxonomies();
?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e( 'Title:' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
            <br />
            <label for="<?php echo $this->get_field_id('select_xxx_for_dropdown'); ?>"><?php _e( 'Replace "Select Taxonomy" of dropdown to:', 'tg29359-taxonomy-widget'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('select_xxx_for_dropdown'); ?>" name="<?php echo $this->get_field_name('select_xxx_for_dropdown'); ?>" type="text" value="<?php echo $select_xxx_for_dropdown; ?>" />
            <br />
            <label for="<?php echo $this->get_field_id('walker_indention'); ?>"><?php _e( 'Replace "&#045;&#045;&#045;" indention of dropdown to:', 'tg29359-taxonomy-widget'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('walker_indention'); ?>" name="<?php echo $this->get_field_name('walker_indention'); ?>" type="text" value="<?php echo $walker_indention; ?>" />
            <br />
            <span><?php _e('*if you want to use a space, input &amp;nbsp&#059;. note that iOS ignore a space.', 'tg29359-taxonomy-widget'); ?></span>
            <br />
            <label for="<?php echo $this->get_field_id('no_taxonomies_for_list'); ?>"><?php _e( 'Replace "No taxonomies" of list to:', 'tg29359-taxonomy-widget'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('no_taxonomies_for_list'); ?>" name="<?php echo $this->get_field_name('no_taxonomies_for_list'); ?>" type="text" value="<?php echo $no_taxonomies_for_list; ?>" />
        </p>
        <p>
            <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('dropdown'); ?>" name="<?php echo $this->get_field_name('dropdown'); ?>"<?php checked( $dropdown ); ?> />
            <label for="<?php echo $this->get_field_id('dropdown'); ?>"><?php _e( 'Display as dropdown' ); ?></label>
            <br />
            <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('hierarchical'); ?>" name="<?php echo $this->get_field_name('hierarchical'); ?>"<?php checked( $hierarchical ); ?> />
            <label for="<?php echo $this->get_field_id('hierarchical'); ?>"><?php _e( 'Show hierarchy' ); ?></label>
            <br />
            <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('count'); ?>" name="<?php echo $this->get_field_name('count'); ?>"<?php checked( $count ); ?> />
            <label for="<?php echo $this->get_field_id('count'); ?>"><?php _e( 'Show post counts' ); ?></label>
            <br />
            <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('pad_counts'); ?>" name="<?php echo $this->get_field_name('pad_counts'); ?>"<?php checked( $pad_counts ); ?> />
            <label for="<?php echo $this->get_field_id('pad_counts'); ?>"><?php _e('Include post counts of child taxonomies', 'tg29359-taxonomy-widget'); ?></label>
            <br />
            <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('hide_empty'); ?>" name="<?php echo $this->get_field_name('hide_empty'); ?>"<?php checked( $hide_empty ); ?> />
            <label for="<?php echo $this->get_field_id('hide_empty'); ?>"><?php _e( 'Hide empty taxonomy', 'tg29359-taxonomy-widget'); ?></label>
            <br />
            <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('hide_if_empty'); ?>" name="<?php echo $this->get_field_name('hide_if_empty'); ?>"<?php checked( $hide_if_empty ); ?> />
            <label for="<?php echo $this->get_field_id('hide_if_empty'); ?>"><?php _e( 'Hide empty dropdown', 'tg29359-taxonomy-widget'); ?></label>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'taxonomy' ); ?>"><?php _e( 'Taxonomy:' ); ?></label><br />
            <select id="<?php echo $this->get_field_id( 'taxonomy' ); ?>" name="<?php echo $this->get_field_name( 'taxonomy' ); ?>">
                <?php foreach ( $taxonomies as $value ) : ?>
                <option value="<?php echo esc_attr( $value ); ?>"<?php selected( $taxonomy, $value ); ?>><?php echo esc_attr( $value ); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="<?php echo $this->get_field_name('orderby'); ?>"><?php _e('Sort By:', 'tg29359-taxonomy-widget'); ?></label>
            <select name="<?php echo $this->get_field_name('orderby'); ?>" class="widefat">
                <option value="name"       <?php selected('name'      , $instance['orderby']); ?>><?php _e('Name'                                 ); ?></option>
                <option value="ID"         <?php selected('ID'        , $instance['orderby']); ?>><?php _e('ID'        , 'tg29359-taxonomy-widget'); ?></option>
                <option value="slug"       <?php selected('slug'      , $instance['orderby']); ?>><?php _e('Slug'                                 ); ?></option>
                <option value="count"      <?php selected('count'     , $instance['orderby']); ?>><?php _e('Count'     , 'tg29359-taxonomy-widget'); ?></option>
                <option value="term_group" <?php selected('term_group', $instance['orderby']); ?>><?php _e('Term group', 'tg29359-taxonomy-widget'); ?></option>
                <option value="menu_order" <?php selected('menu_order', $instance['orderby']); ?>><?php _e('Menu order'); ?></option>
            </select>
            <span><?php _e('*Sorry, this feature does not work yet, at least with the plugin "Category Order and Taxonomy Terms Order".', 'tg29359-taxonomy-widget'); ?></span>
        </p>
        <p>
            <label for="<?php echo $this->get_field_name('order'); ?>"><?php _e('Sort Order:'); ?></label>
            <select name="<?php echo $this->get_field_name( 'order' ); ?>" class="widefat">
                <option value="ASC"  <?php selected('ASC' , $instance['order']); ?>><?php _e('Ascending' ); ?></option>
                <option value="DESC" <?php selected('DESC', $instance['order']); ?>><?php _e('Descending'); ?></option>
            </select>
            <span><?php _e('*Sorry, this feature does not work yet, at least with the plugin "Category Order and Taxonomy Terms Order".', 'tg29359-taxonomy-widget'); ?></span>
        </p>
<?php
    }

    private function get_taxonomies()
    {
        $taxonomies = get_taxonomies(array('public' => true,));
        return $taxonomies;
    }
}

function register_tg29359_Widget_Taxonomies()
{
    register_widget('tg29359_Widget_Taxonomies');
}

add_action('widgets_init', 'register_tg29359_Widget_Taxonomies');
?>
