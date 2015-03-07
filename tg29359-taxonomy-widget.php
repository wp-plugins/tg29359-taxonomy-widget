<?php
/**
 * @package tg29359 taxonomy widget
 */
/*
Plugin Name: tg29359 taxonomy widget
Plugin URI: http://tg29359.rdy.jp/app/tg29359-taxonomy-widget/
Description: Displays a list or dropdown of taxonomies in a sidebar widget.
Version: 0.0.1
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
        $pad = str_repeat('&nbsp;', $depth * 3);
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
        $title = apply_filters('widget_title', empty($instance['title']) ? __('Taxonomies', 'tg29359-taxonomy-widget') : $instance['title'], $instance, 'tg29359_taxonomies');

        $c = !empty($instance['count']) ? '1' : '0';
        $h = !empty($instance['hierarchical']) ? '1' : '0';
        $hide_empty = !empty($instance['hide_empty']) ? '1' : '0';
        $d = !empty($instance['dropdown']) ? '1' : '0';
        if (!empty($instance['taxonomy'])) {
            $this->taxonomy = $instance['taxonomy'];
        }

        echo $args['before_widget'];
        if ($title) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        $cat_args = array('orderby' => 'name', 'show_count' => $c, 'hierarchical' => $h, 'hide_empty' => $hide_empty);

        if ($d) {
            if (!empty($instance['select_xxx_for_dropdown'])) {
                $cat_args['show_option_none'] = $instance['select_xxx_for_dropdown'];
            } else {
                $cat_args['show_option_none'] = __('Select Taxonomy', 'tg29359-taxonomy-widget');
            }
            $cat_args['taxonomy'] = $this->taxonomy;
            $cat_args['id'  ] = 'tg29359_taxonomies_tax';
            $cat_args['name'] = 'tg29359_taxonomies_tax';
            $cat_args['walker'] = $this->walker;

            wp_dropdown_categories(apply_filters('widget_categories_dropdown_args', $cat_args));
?>

<script type='text/javascript'>
/* <![CDATA[ */
    var tg29359_taxonomies_dropdown = document.getElementById('tg29359_taxonomies_tax');
    function tg29359_taxonomies_onTaxonomyChange()
    {
        if (tg29359_taxonomies_dropdown.options[tg29359_taxonomies_dropdown.selectedIndex].value != 'null') {
            location.href = tg29359_taxonomies_dropdown.options[tg29359_taxonomies_dropdown.selectedIndex].value;
        }
    }
    tg29359_taxonomies_dropdown.onchange = tg29359_taxonomies_onTaxonomyChange;
/* ]]> */
</script>

<?php
        } else {
?>
        <ul>
<?php
        $cat_args['title_li'] = '';
        $cat_args['taxonomy'] = $this->taxonomy;

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
        $instance['count'] = !empty($new_instance['count']) ? 1 : 0;
        $instance['hierarchical'] = !empty($new_instance['hierarchical']) ? 1 : 0;
        $instance['hide_empty'] = !empty($new_instance['hide_empty']) ? 1 : 0;
        $instance['dropdown'] = !empty($new_instance['dropdown']) ? 1 : 0;
        $taxonomies = $this->get_taxonomies();
        $instance['taxonomy'] = 'category';
        if (in_array($new_instance['taxonomy'], $taxonomies)) {
            $instance['taxonomy'] = $new_instance['taxonomy'];
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
        $count = isset($instance['count']) ? (bool)$instance['count'] : false;
        $hierarchical = isset($instance['hierarchical']) ? (bool)$instance['hierarchical'] : false;
        $hide_empty = isset($instance['hide_empty']) ? (bool)$instance['hide_empty'] : false;
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
            <label for="<?php echo $this->get_field_id('select_xxx_for_dropdown'); ?>"><?php _e( 'Custom Select Taxonomy:', 'tg29359-taxonomy-widget'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('select_xxx_for_dropdown'); ?>" name="<?php echo $this->get_field_name('select_xxx_for_dropdown'); ?>" type="text" value="<?php echo $select_xxx_for_dropdown; ?>" />
        </p>
        <p>
            <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('dropdown'); ?>" name="<?php echo $this->get_field_name('dropdown'); ?>"<?php checked( $dropdown ); ?> />
            <label for="<?php echo $this->get_field_id('dropdown'); ?>"><?php _e( 'Display as dropdown' ); ?></label><br />

            <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('count'); ?>" name="<?php echo $this->get_field_name('count'); ?>"<?php checked( $count ); ?> />
            <label for="<?php echo $this->get_field_id('count'); ?>"><?php _e( 'Show post counts' ); ?></label><br />

            <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('hierarchical'); ?>" name="<?php echo $this->get_field_name('hierarchical'); ?>"<?php checked( $hierarchical ); ?> />
            <label for="<?php echo $this->get_field_id('hierarchical'); ?>"><?php _e( 'Show hierarchy' ); ?></label><br />

            <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('hide_empty'); ?>" name="<?php echo $this->get_field_name('hide_empty'); ?>"<?php checked( $hide_empty ); ?> />
            <label for="<?php echo $this->get_field_id('hide_empty'); ?>"><?php _e( 'Hide Empty', 'tg29359-taxonomy-widget'); ?></label>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'taxonomy' ); ?>"><?php _e( 'Taxonomy:' ); ?></label><br />
            <select id="<?php echo $this->get_field_id( 'taxonomy' ); ?>" name="<?php echo $this->get_field_name( 'taxonomy' ); ?>">
                <?php foreach ( $taxonomies as $value ) : ?>
                <option value="<?php echo esc_attr( $value ); ?>"<?php selected( $taxonomy, $value ); ?>><?php echo esc_attr( $value ); ?></option>
                <?php endforeach; ?>
            </select>
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
