<?php

if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class DT_Network_Dashboard_Metrics_Home extends DT_Network_Dashboard_Metrics_Base {

    public $url_path;
    public $base_slug = 'network'; //lowercase
    public $base_title = "Home";
    public $title = '';
    public $slug = '';
    public $js_object_name = ''; // This object will be loaded into the metrics.js file by the wp_localize_script.
    public $js_file_name = ''; // should be full file name plus extension
    public $permissions = ['view_any_contacts', 'view_project_metrics'];

    private static $_instance = null;
    public static function instance() {
        if (is_null( self::$_instance )) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    public function __construct() {
        parent::__construct();
        add_filter( 'dt_templates_for_urls', [ $this, 'add_url' ], 199 ); // add custom URL
        add_action( "template_redirect", [ $this, 'url_redirect' ], 10 );
        add_filter( 'dt_metrics_menu', [ $this, 'menu' ], 199 );
        add_action( 'wp_enqueue_scripts', [ $this, 'scripts' ], 99 );
        add_filter( 'dt_mapping_module_data', [ $this, 'filter_mapping_module_data' ], 50, 1 );

    }

    /**
     *  This hook add a page for the metric charts
     *
     * @param $template_for_url
     *
     * @return mixed
     */
    public function base_add_url( $template_for_url ) {
        $template_for_url["network/$this->base_slug/$this->slug"] = 'template-metrics.php';
        return $template_for_url;
    }

    public function base_scripts() {
        wp_localize_script(
            'dt_'.$this->base_slug.'_script', 'apiNetworkDashboardBase', [
                'slug' => $this->base_slug,
                'root' => esc_url_raw( rest_url() ),
                'plugin_uri' => plugin_dir_url( __DIR__ ),
                'nonce' => wp_create_nonce( 'wp_rest' ),
                'current_user_login' => wp_get_current_user()->user_login,
                'current_user_id' => get_current_user_id()
            ]
        );
    }

    public function has_permission(){
        $permissions = $this->permissions;
        $pass = count( $permissions ) === 0;
        foreach ( $this->permissions as $permission ){
            if ( current_user_can( $permission ) ){
                $pass = true;
            }
        }
        return $pass;
    }

    public function add_url( $template_for_url) {
        $template_for_url['network'] = 'template-metrics.php';
        return $template_for_url;
    }

    public function url_redirect() {
        $url = dt_get_url_path();
        $plugin_dir = get_stylesheet_directory();
        if ( strpos( $url, "network" ) !== false ){
            $path = $plugin_dir . '/template-metrics.php';
            include( $path );
            die();
        }
    }

    public function menu( $content) {
        // home
        $content .= '<li><a href="' . esc_url( site_url( '/network/' ) ) . '" onclick="show_network_home()">' . esc_html__( 'Home' ) . '</a></li>';
        return $content;
    }

    public function scripts() {

        if ( DT_Mapbox_API::get_key() ){
            DT_Mapbox_API::load_mapbox_header_scripts();
        }

        DT_Mapping_Module::instance()->scripts();

        // UI script
        wp_enqueue_script('dt_network_dashboard_script',
            trailingslashit( plugin_dir_url( __FILE__ ) ) . 'metrics.js',
            [
                'jquery',
                'amcharts-core',
                'amcharts-charts',
                'amcharts-animated',
                'amcharts-maps',
                'datatable',
            ],
            filemtime( plugin_dir_path( __DIR__ ) . 'metrics/metrics.js' ),
            true);
        wp_localize_script(
            'dt_network_dashboard_script',
            'wpApiNetworkDashboard',
            [
                'root' => esc_url_raw( rest_url() ),
                'plugin_uri' => plugin_dir_url( __DIR__ ),
                'nonce' => wp_create_nonce( 'wp_rest' ),
                'current_user_login' => wp_get_current_user()->user_login,
                'current_user_id' => get_current_user_id(),
                'spinner' => ' <img src="' . plugin_dir_url( __DIR__ ) . 'spinner.svg" width="12px" />',
                'spinner_large' => ' <img src="' . plugin_dir_url( __DIR__ ) . 'spinner.svg" width="24px" />',
                'global' => self::get_global(), // @todo make these ajax loaded
                'locations_list' => self::get_locations_list(), // @todo make these ajax loaded
                'translations' => [
                ]
            ]
        );

    }

    public static function get_sites() {

//        if (wp_cache_get( 'get_sites' )) {
//            return wp_cache_get( 'get_sites' );
//        }

        $new = [];

        $sites = DT_Network_Dashboard_Site_Post_Type::all_sites();
        if ( !empty( $sites )) {
            foreach ($sites as $site) {
                if ( 'multisite' === $site['type'] ){
                    continue;
                }
                $snapshot = maybe_unserialize( $site['snapshot'] );
                if ( !empty( $snapshot['partner_id'] )) {
                    $new[$snapshot['partner_id']] = $snapshot;
                    $new[$snapshot['partner_id']]['partner_name'] = $site['name'];
                }
            }
        }

        if (dt_is_current_multisite_dashboard_approved()) {
            foreach ($sites as $key => $site) {
                if ( 'remote' === $site['type'] ){
                    continue;
                }
                $snapshot = maybe_unserialize( $site['snapshot'] );
                if ( !empty( $snapshot['partner_id'] )) {
                    $new[$snapshot['partner_id']] = $snapshot;
                }
            }
        }

//        wp_cache_set( 'get_sites', $new );

        return $new;
    }

    public static function get_site_list() {
        $sites = DT_Network_Dashboard_Site_Post_Type::all_sites();

        $new = [];
        if ( !empty( $sites )) {
            foreach ($sites as $key => $site) {
                if ( 'multisite' === $site['type'] ){
                    continue;
                }
                $snapshot = maybe_unserialize( $site['snapshot'] );
                if ( !empty( $snapshot['partner_id'] )) {
                    $new[] = [
                        'id' => $snapshot['partner_id'],
                        'name' => ucwords( $site['name'] ),
                        'contacts' => $snapshot['contacts']['current_state']['status']['active'],
                        'groups' => $snapshot['groups']['current_state']['total_active'],
                        'users' => $snapshot['users']['current_state']['total_users'],
                        'date' => date( 'Y-m-d H:i:s', $snapshot['date'] ),
                    ];
                }
            }
        }

        if (dt_is_current_multisite_dashboard_approved()) {
            foreach ($sites as $key => $site) {
                if ( 'remote' === $site['type'] ){
                    continue;
                }
                $snapshot = maybe_unserialize( $site['snapshot'] );
                if ( !empty( $snapshot['partner_id'] )) {
                    $new[] = [
                        'id' => $snapshot['partner_id'],
                        'name' => ucwords( $snapshot['profile']['partner_name'] ),
                        'contacts' => $snapshot['contacts']['current_state']['status']['active'],
                        'groups' => $snapshot['groups']['current_state']['total_active'],
                        'users' => $snapshot['users']['current_state']['total_users'],
                        'date' => date( 'Y-m-d H:i:s', $snapshot['date'] ),
                    ];
                }
            }
        }

        return $new;
    }

    public static function get_global() {
        $totals = self::compile_totals();
        $data = [
            'contacts' => [
                'total' => $totals['total_contacts'] ?? 0,
                'added' => [
                    'sixty_days' => self::compile_by_days( 'contacts' ),
                    'twenty_four_months' => self::compile_by_months( 'contacts' ),
                ],
            ],
            'groups' => [
                'total' => $totals['total_groups'] ?? 0,
                'added' => [
                    'sixty_days' => self::compile_by_days( 'groups' ),
                    'twenty_four_months' => self::compile_by_months( 'groups' ),
                ],
            ],
            'users' => [
                'total' => $totals['total_users'] ?? 0,
            ],
            'sites' => [
                'total' => $totals['total_sites'] ?? 0,
            ],
            'locations' => [
                'total_countries' => $totals['total_countries'] ?? 0,
            ],
            'prayer_events' => [
                'total' => $totals['total_prayer_events'] ?? 0,
            ],
        ];

        return $data;
    }

    public static function get_locations_list() {
        $data_types = self::location_data_types();
        $data = [
            'custom_column_labels' => $data_types,
            'current_state' => [
                'active_countries' => 0,
                'active_admin0_grid_ids' => [],
                'active_admin1' => 0,
                'active_admin1_grid_ids' => [],
                'active_admin2' => 0,
                'active_admin2_grid_ids' => [],
            ],
            'list' => [],
        ];
        $sites = self::get_sites();

        if (empty( $sites )) {
            return [];
        }

        $custom_column_data = [];
        foreach ($sites as $id => $site) {
            foreach ($site['locations']['list'] as $grid_id => $stats) {
                if ( !isset( $custom_column_data[$grid_id] ) ) {
                    $custom_column_data[$grid_id] = [];
                    $i = 0;
                    $label_counts = count( $data_types );
                    while ($i <= $label_counts -1 ) {
                        $custom_column_data[$grid_id][$i] = 0;
                        $i++;
                    }
                }
                $custom_column_data[$grid_id][0] = (int) $custom_column_data[$grid_id][0] + (int) $stats['contacts'] ?? 0;
                $custom_column_data[$grid_id][1] = (int) $custom_column_data[$grid_id][1] + (int) $stats['groups'] ?? 0;
                $custom_column_data[$grid_id][2] = (int) $custom_column_data[$grid_id][2] + (int) $stats['churches'] ?? 0;
                $custom_column_data[$grid_id][3] = (int) $custom_column_data[$grid_id][3] + (int) $stats['users'] ?? 0;
            }
        }

        $data["custom_column_data"] = $custom_column_data;

        foreach ($sites as $id => $site) {

            // list
            foreach ($site['locations']['list'] as $grid_id => $stats) {
                if ( !isset( $data['list'][$grid_id] )) {
                    $data['list'][ $grid_id ] = [
                        "contacts" => 0,
                        "groups" => 0,
                        "churches" => 0,
                        "users" => 0
                    ];
                    $data['list'][$grid_id]['sites'] = $sites[$id]['profile']['partner_name'];
                } else {
                    $data['list'][$grid_id]['sites'] .= ', ' . $sites[$id]['profile']['partner_name'];
                }
                $data['list'][$grid_id]['contacts'] = (int) $data['list'][$grid_id]['contacts'] + (int) $stats['contacts'] ?? 0;
                $data['list'][$grid_id]['groups'] = (int) $data['list'][$grid_id]['groups'] + (int) $stats['groups'] ?? 0;
                $data['list'][$grid_id]['churches'] = (int) $data['list'][$grid_id]['churches'] + (int) $stats['churches'] ?? 0;
                $data['list'][$grid_id]['users'] = (int) $data['list'][$grid_id]['users'] + (int) $stats['users'] ?? 0;
                $data['list'][$grid_id][$id] = $sites[$id]['profile']['partner_name'];

            }

            // complete list
            $list_location_grids = array_keys( $data['list'] );
            $location_grid_properties = self::format_location_grid_types( Disciple_Tools_Mapping_Queries::get_by_grid_id_list( $list_location_grids, true ) );
            if ( !empty( $location_grid_properties )) {
                foreach ($location_grid_properties as $value) {
                    foreach ($value as $k => $v) {
                        $data['list'][$value['grid_id']][$k] = $v;
                    }
                }
            }
        }

        return $data;
    }

    public static function get_activity_log(){
        global $wpdb;
        $data = $wpdb->get_results( "SELECT * FROM $wpdb->dt_movement_log ORDER BY timestamp DESC LIMIT 5000;");
        if ( empty( $data ) ) {
            return [];
        }
        return $data;
    }

    public static function format_location_grid_types( $query) {
        if ( !empty( $query ) || !is_array( $query )) {
            foreach ($query as $index => $value) {
                if (isset( $value['grid_id'] )) {
                    $query[$index]['grid_id'] = (int) $value['grid_id'];
                }
                if (isset( $value['population'] )) {
                    $query[$index]['population'] = (int) $value['population'];
                    $query[$index]['population_formatted'] = number_format( (int) $value['population'] );
                }
                if (isset( $value['latitude'] )) {
                    $query[$index]['latitude'] = (float) $value['latitude'];
                }
                if (isset( $value['longitude'] )) {
                    $query[$index]['longitude'] = (float) $value['longitude'];
                }
                if (isset( $value['parent_id'] )) {
                    $query[$index]['parent_id'] = (float) $value['parent_id'];
                }
                if (isset( $value['admin0_grid_id'] )) {
                    $query[$index]['admin0_grid_id'] = (float) $value['admin0_grid_id'];
                }
                if (isset( $value['admin1_grid_id'] )) {
                    $query[$index]['admin1_grid_id'] = (float) $value['admin1_grid_id'];
                }
                if (isset( $value['admin2_grid_id'] )) {
                    $query[$index]['admin2_grid_id'] = (float) $value['admin2_grid_id'];
                }
                if (isset( $value['admin3_grid_id'] )) {
                    $query[$index]['admin3_grid_id'] = (float) $value['admin3_grid_id'];
                }
            }
        }
        return $query;
    }

    public static function location_data_types() {
        return [
            [
                "key" => "contacts",
                "label" => "Contacts"
            ],
            [
                "key" => "groups",
                "label" => "Groups"
            ],
            [
                "key" => "churches",
                "label" => "Churches"
            ],
            [
                "key" => "users",
                "label" => "Users"
            ]
        ];
    }

    /**
     * Gets an array of the last number of days.
     *
     * @param int $number_of_days
     *
     * @return array
     */
    public static function get_day_list( $number_of_days = 60) {
        $d = [];
        for ($i = 0; $i < $number_of_days; $i++) {
            $d[date( "Y-m-d", strtotime( '-' . $i . ' days' ) )] = [
                'date' => date( "Y-m-d", strtotime( '-' . $i . ' days' ) ),
                'value' => 0,
            ];
        }
        return $d;
    }

    /**
     * Gets an array of last 25 months.
     *
     * @note 25 months allows you to get 3 years to compare of this month.
     *
     * @param int $number_of_months
     *
     * @return array
     */
    public static function get_month_list( $number_of_months = 25) {
        $d = [];
        for ($i = 0; $i < $number_of_months; $i++) {
            $d[date( "Y-m", strtotime( '-' . $i . ' months' ) ) . '-01'] = [
                'date' => date( "Y-m", strtotime( '-' . $i . ' months' ) ) . '-01',
                'value' => 0,
            ];
        }
        return $d;
    }

    public static function compile_by_days( $type) {
        $dates1 = self::get_day_list( 60 );
        $dates2 = [];

        $sites = self::get_sites();
        if (empty( $sites )) {
            return [];
        }

        // extract days
        foreach ($sites as $key => $site) {
            foreach ($site[$type]['added']['sixty_days'] as $day) {
                if (isset( $dates1[$day['date']]['value'] ) && $day['value']) {
                    $dates1[$day['date']]['value'] = $dates1[$day['date']]['value'] + $day['value'];
                }
            }
        }

        arsort( $dates1 );

        foreach ($dates1 as $d) {
            $dates2[] = $d;
        }

        return $dates2;
    }

    public static function compile_by_months( $type) {
        $dates1 = self::get_month_list( 25 );
        $dates2 = [];

        $sites = self::get_sites();
        if (empty( $sites )) {
            return [];
        }

        // extract months
        foreach ($sites as $key => $site) {
            foreach ($site[$type]['added']['twenty_four_months'] as $day) {
                if (isset( $dates1[$day['date']]['value'] ) && $day['value']) {
                    $dates1[$day['date']]['value'] = $dates1[$day['date']]['value'] + $day['value'];
                }
            }
        }

        arsort( $dates1 );

        foreach ($dates1 as $d) {
            $dates2[] = $d;
        }

        return $dates2;
    }

    public static function compile_totals() {
        $sites = self::get_sites();
        $data = [
            'total_contacts' => 0,
            'total_groups' => 0,
            'total_users' => 0,
            'total_countries' => 0,
            'total_sites' => 0,
            'total_prayer_events' => 0,
        ];
        if (empty( $sites )) {
            return [];
        }

        foreach ($sites as $key => $site) {
            $data['total_contacts'] = $data['total_contacts'] + $site['contacts']['current_state']['status']['active'];
            $data['total_groups'] = $data['total_groups'] + $site['groups']['current_state']['total_active'];
            $data['total_users'] = $data['total_users'] + $site['users']['current_state']['total_users'];

            if ( !empty( $site['locations']['current_state']['active_admin0_grid_ids'] )) {
                foreach ($site['locations']['current_state']['active_admin0_grid_ids'] as $grid_id) {
                    $data['countries'][$grid_id] = true;
                }
            }
        }
        if ( !empty( $data['countries'] )) {
            $data['total_countries'] = count( $data['countries'] );
        }

        $data['total_sites'] = count($sites);

        $logs = self::get_activity_log();
        $data['total_prayer_events'] = count($logs);

        return $data;
    }
}
DT_Network_Dashboard_Metrics_Base::instance();