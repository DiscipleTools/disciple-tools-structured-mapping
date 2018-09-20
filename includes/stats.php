<?php

class DT_Saturation_Mapping_Stats {
    public static function get_location_tree() {
        $table_data = self::query_location_population_groups();

        $chart = [];
        foreach ( $table_data as $row ) {
            if ( (int) $row['groups_needed'] < 1 ) {
                $row['groups_needed'] = 0;
            }
            $chart[] = [
            [
            'v' => $row['location'],
            'f' => $row['location'] . '<br>pop: ' . $row['gn_population'] . '<br>need: ' . $row['groups_needed']
            ],
            $row['parent_name'],
            ''
            ];
        }

        return $chart;
    }

    public static function get_location_table() {
        $table_data = self::query_location_population_groups();

        $chart = [];
        foreach ( $table_data as $row ) {
            if ( (int) $row['groups_needed'] < 1 ) {
                $row['groups_needed'] = 0;
            }
            $chart[] = [ $row['location'], (int) $row['gn_population'], (int) $row['groups_needed'], (int) $row['groups'] ];
        }

        return $chart;
    }

    public static function get_location_map() {
        $table_data = self::query_location_latlng();

        $chart = [];
        $chart[] = [ 'Lat', 'Long', 'Name' ];
        foreach ( $table_data as $row ) {
            if ( ! empty( $row['latitude'] ) && ! empty( $row['longitude'] ) ) {
                $chart[] = [
                    (float) $row['latitude'],
                (float) $row['longitude'],
                $row['location']
                ];
            }
        }

        return $chart;
    }

    public static function get_location_side_tree() {
        $table_data = self::query_location_population_groups();

        $chart = [];
        $chart[] = [ 'id', 'childLabel', 'parent', 'size', [ 'role' => 'style' ] ];
        foreach ( $table_data as $row ) {
            if ( $row['parent_id'] == 0 ) {
                $row['parent_id'] = -1;
            }
            $chart[] = [ (int) $row['id'], $row['location'], (int) $row['parent_id'], 1, 'black' ];
        }

        return $chart;
    }

    public static function query_geoname_list() {
        global $wpdb;
        return $wpdb->get_col("SELECT CONCAT( name, ', ', country_code) FROM $wpdb->dt_geonames" );
    }

    public static function query_location_population_groups() {
        global $wpdb;

        $results = $wpdb->get_results("
            SELECT 
            t1.ID as id, 
            t1.post_parent as parent_id, 
            t1.post_title as location,
            (SELECT post_title FROM $wpdb->posts WHERE ID = t1.post_parent) as parent_name,
            t2.meta_value as gn_population, 
            ROUND(t2.meta_value / (SELECT option_value FROM $wpdb->options WHERE option_name = 'dt_saturation_mapping_pd'), 0 ) as groups_needed,
            (SELECT count(*) FROM $wpdb->p2p WHERE p2p_to = t1.ID) as groups
            FROM $wpdb->posts as t1
            LEFT JOIN $wpdb->postmeta as t2
            ON t1.ID=t2.post_id
            AND t2.meta_key = 'gn_population'
            WHERE post_type = 'locations' AND post_status = 'publish'
        ", ARRAY_A );

        return $results;
    }

    public static function query_location_latlng() {
        global $wpdb;

        $results = $wpdb->get_results("
            SELECT 
            t2.meta_value as latitude,
            t3.meta_value as longitude,
            t1.post_title as location
            FROM $wpdb->posts as t1
            LEFT JOIN $wpdb->postmeta as t2
            ON t1.ID=t2.post_id
            AND t2.meta_key = 'gn_latitude'
            LEFT JOIN $wpdb->postmeta as t3
            ON t1.ID=t3.post_id
            AND t3.meta_key = 'gn_longitude'
            WHERE post_type = 'locations' 
            AND post_status = 'publish'
            AND post_parent != '0'
        ", ARRAY_A );

        return $results;
    }

    public static function get_locations_level_tree() {
        global $wpdb;
        $query = $wpdb->get_results("
                    SELECT 
                    t1.ID as id, 
                    t1.post_parent as parent_id, 
                    t1.post_title as name,
                    t2.meta_value as gn_population, 
                    ROUND(t2.meta_value / (SELECT option_value FROM $wpdb->options WHERE option_name = 'dt_saturation_mapping_pd'), 0 ) as groups_needed,
                    (SELECT count(*) FROM $wpdb->p2p WHERE p2p_to = t1.ID) as groups
                    FROM $wpdb->posts as t1
                    LEFT JOIN $wpdb->postmeta as t2
                    ON t1.ID=t2.post_id
                    AND t2.meta_key = 'gn_population'
                    WHERE post_type = 'locations' AND post_status = 'publish'
                ", ARRAY_A );
        // prepare special array with parent-child relations
        $menu_data = array(
            'items' => array(),
            'parents' => array()
        );
        foreach ( $query as $menuItem )
        {
            $menu_data['items'][$menuItem['id']] = $menuItem;
            $menu_data['parents'][$menuItem['parent_id']][] = $menuItem['id'];
        }

        function build_menu( $parent_id, $menu_data, $gen) {
            $html = '';

            if (isset( $menu_data['parents'][$parent_id] ))
            {
                $html = '<ul class="gen-ul ul-gen-'.$gen.'">';
                $gen++;
                foreach ($menu_data['parents'][$parent_id] as $item_id)
                {
                    $html .= '<li class="gen-li li-gen-'.$gen.'">';
                    //            $html .= '(level: '.$gen.')<br> ';
                    $html .= '<strong>'. $menu_data['items'][$item_id]['name'] . '</strong><br>';
                    $html .= 'population: '. ( $menu_data['items'][$item_id]['gn_population'] ?: '0' ) . '<br>';
                    $html .= 'groups needed: '. ( $menu_data['items'][$item_id]['groups_needed'] ?: '0' ) . '<br>';
                    $html .= 'groups: '. $menu_data['items'][$item_id]['groups'];

                    $html .= '</li>';

                    // find childitems recursively
                    $html .= build_menu( $item_id, $menu_data, $gen );
                }
                $html .= '</ul>';
            }

            return $html;
        }

        $list = '<style>
                    .gen-ul {
                        list-style: none;
                        padding-left:30px;
                    }
                    .gen-li {
                        padding: 25px;
                        border: 1px solid grey;
                        margin-top: 10px;
                        width: 20%;
                        background: yellowgreen;
                        border-radius:10px;
                    }
                </style>';

        $list .= build_menu( 0, $menu_data, -1 );

        return $list;
    }

    public static function get_site_link_list() {
        global $wpdb;
        $list = $wpdb->get_results("
            SELECT post_title, ID as id
            FROM $wpdb->posts
            WHERE post_type = 'site_link_system' 
                AND post_status = 'publish'
        ", ARRAY_A );

        return $list;
    }
}