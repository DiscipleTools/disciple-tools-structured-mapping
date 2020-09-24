<?php
/**
 * DT_Network_Dashboard_Menu class for the admin page
 *
 * @class       DT_Network_Dashboard_Menu
 * @version     0.1.0
 * @since       0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly


/**
 * Initialize menu class
 */
DT_Network_Dashboard_Menu::instance();

/**
 * Class DT_Network_Dashboard_Menu
 */
class DT_Network_Dashboard_Menu {

    public $token = 'dt_network_dashboard';

    /**
     * DT_Network_Dashboard_Menu Instance
     *
     * Ensures only one instance of DT_Network_Dashboard_Menu is loaded or can be loaded.
     *
     * @since 0.1.0
     * @static
     * @return DT_Network_Dashboard_Menu instance
     */
    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    /**
     * Constructor function.
     * @access  public
     * @since   0.1.0
     */
    public function __construct() {
        add_action( "admin_menu", array( $this, "register_menu" ) );
    } // End __construct()

    /**
     * Loads the subnav page
     * @since 0.1
     */
    public function register_menu() {
        add_menu_page( __( 'Extensions (DT)', 'disciple_tools' ),
            __( 'Extensions (DT)', 'disciple_tools' ),
            'manage_dt',
            'dt_extensions',
            [ $this, 'extensions_menu' ],
            'dashicons-admin-generic',
        59 );
        add_submenu_page( 'dt_extensions',
            __( 'Network Dashboard', 'dt_network_dashboard' ),
            __( 'Network Dashboard', 'dt_network_dashboard' ),
            'manage_dt',
            $this->token,
        [ $this, 'content' ] );
    }


    /**
     * Menu stub. Replaced when Disciple Tools Theme fully loads.
     */
    public function extensions_menu() {}

    /**
     * Builds page contents
     * @since 0.1
     */
    public function content() {

        if ( !current_user_can( 'manage_dt' ) ) { // manage dt is a permission that is specific to Disciple Tools and allows admins, strategists and dispatchers into the wp-admin
            wp_die( esc_attr__( 'You do not have sufficient permissions to access this page.' ) );
        }

        if ( dt_network_dashboard_multisite_is_approved() ) {
            $approved_multisite = true;
        } else {
            $approved_multisite = false;
        }

        if ( isset( $_GET["tab"] ) ) {
            $tab = sanitize_key( wp_unslash( $_GET["tab"] ) );
        }
        else if ( $approved_multisite ) {
            $tab = 'multisite-sites';
        }
        else {
            $tab = 'remote-sites';
        }

        $link = 'admin.php?page='.$this->token.'&tab=';

        ?>
        <div class="wrap">
            <h2><?php esc_attr_e( 'Network Dashboard', 'dt_network_dashboard' ) ?></h2>
            <h2 class="nav-tab-wrapper">

                <?php if ( $approved_multisite ) :  // check if approved multisite dashboard?>

                    <a href="<?php echo esc_attr( $link ) . 'multisite-sites' ?>" class="nav-tab
                        <?php echo ( $tab == 'multisite-sites' || ! isset( $tab ) ) ? 'nav-tab-active' : ''; ?>">
                        Multisite Sites
                    </a>
                    <a href="<?php echo esc_attr( $link ) . 'remote-sites' ?>" class="nav-tab
                    <?php echo ( $tab == 'remote-sites'  ) ? 'nav-tab-active' : ''; ?>">
                        Remote Sites
                    </a>

                <?php else: ?>
                    <a href="<?php echo esc_attr( $link ) . 'remote-sites' ?>" class="nav-tab
                    <?php echo ( $tab == 'remote-sites' || ! isset( $tab )  ) ? 'nav-tab-active' : ''; ?>">
                        Remote Sites
                    </a>
                <?php endif; ?>

                <a href="<?php echo esc_attr( $link ) . 'local-site' ?>" class="nav-tab
                <?php ( $tab == 'local-site' ) ? esc_attr_e( 'nav-tab-active', 'dt_network_dashboard' ) : print ''; ?>">
                    Local Site
                </a>

                <a href="<?php echo esc_attr( $link ) . 'activity' ?>" class="nav-tab
                <?php echo ( $tab == 'activity' ) ? 'nav-tab-active' : ''; ?>">
                    Send Activity
                </a>

                <a href="<?php echo esc_attr( $link ) . 'cron' ?>" class="nav-tab
                <?php echo ( $tab == 'cron' ) ? 'nav-tab-active' : ''; ?>">
                    Cron
                </a>

                <a href="<?php echo esc_attr( $link ) . 'tutorials' ?>" class="nav-tab
                <?php echo ( $tab == 'tutorials' ) ? 'nav-tab-active' : ''; ?>">
                    Tutorials
                </a>
            </h2>

            <?php
            switch ($tab) {
                case "remote-sites":
                    $object = new DT_Network_Dashboard_Tab_Remote_Snapshots();
                    $object->content();
                    break;
                case "multisite-sites":
                    $object = new DT_Network_Dashboard_Tab_Multisite_Snapshots();
                    $object->content();
                    break;
                case "local-site":
                    $object = new DT_Network_Dashboard_Tab_Local();
                    $object->content();
                    break;
                case "activity":
                    $object = new DT_Network_Dashboard_Tab_Activity();
                    $object->content();
                    break;
                case "tutorials":
                    $object = new DT_Network_Dashboard_Tab_Tutorial();
                    $object->content();
                    break;
                case "cron":
                    $object = new DT_Network_Dashboard_Tab_Cron();
                    $object->content();
                    break;
                default:
                    break;
            }
            ?>
        </div><!-- End wrap -->
        <?php
    }
}

/**
 * Class DT_Network_Dashboard_Tab_Multisite_Snapshots
 */
class DT_Network_Dashboard_Tab_Multisite_Snapshots
{
    public function content() {
        ?>
        <div class="wrap">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-1">
                    <div id="post-body-content">
                        <!-- Main Column -->
                        <?php

                        if ( dt_is_current_multisite_dashboard_approved() ) {
                            $this->process_full_list();
                            $this->main_column();
                            $this->logging_viewer();
                        } else {
                            $this->not_approved_content();
                        }

                        ?>

                        <!-- End Main Column -->
                    </div><!-- end post-body-content -->

                </div><!-- post-body meta box container -->
            </div><!--poststuff end -->
        </div><!-- wrap end -->
        <?php
    }

    public function not_approved_content() {
        ?>
        <table class="widefat striped">
            <thead>
            <th>Not Yet Approved to Collect Reports from the Local Network</th>
            </thead>
            <tbody>
            <tr>
                <td>
                    <p>You are not yet approved to collect reports from the local network. Your network administrator must enable your dashboard to collect reports from this network.</p>
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <table class="widefat striped">
            <thead>
            <th>How does this work?</th>
            </thead>
            <tbody>
            <tr>
                <td>
                    <ol>
                        <li>
                            The network dashboard can collect reports from both "Remote Sites" which you setup through the Site Links system, or in a multisite installation, where many sites are hosted on one server
                            you can enable the network dashboard to collect reports from the "local" or multisite server installations of Disciple Tools.
                        </li>
                        <li>
                            Both remote and local sites can be collected from and are aggregated into dashboard totals.
                        </li>
                    </ol>
                </td>
            </tr>
            </tbody>
        </table>
        <?php
    }

    public function process_full_list() {
        ?>
        <p><strong>Snapshot Collection</strong></strong></p>
        <table class="widefat striped">
            <tbody>
            <tr>
                <td>
                    <button class="button" id="rerun-collection" type="button">Refresh All Snapshots</button>
                    &nbsp;<span id="rerun-collection-spinner" style="display: none;"><img src="<?php echo plugin_dir_url( __DIR__ ) ?>spinner.svg" width="25px" /></span>
                    &nbsp;<span id="result-message"></span>

                    <span style="float:right;"><a href="#logs">View logs</a></span>
                </td>
            </tr>
            </tbody>
        </table>
        <script>
            jQuery(document).ready(function() {
                jQuery('#rerun-collection').click( function() {
                    let spinner = jQuery('#rerun-collection-spinner')
                    spinner.show()
                    jQuery.ajax({
                        type: "POST",
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        url: '<?php echo esc_url( rest_url() ) ?>dt/v1/network/admin/trigger_multisite_snapshot_collection',
                        beforeSend: function (xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', '<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ); ?>');
                        },
                    })
                        .done(function (data) {
                            console.log(data)
                            if ( data ) {
                                spinner.hide()
                                jQuery('#result-message').html('Snapshot refresh began successfully. Check logs below for updates.')
                            }
                        })
                        .fail(function (err) {
                            console.log(err)
                            jQuery('#result-message').html('Snapshot refresh unsuccessful. View console for errors.')
                        })
                })
            })
        </script>
        <?php
    }

    public function main_column() {
        $message = false;
        if ( isset( $_POST['network_dashboard_nonce'] )
            && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['network_dashboard_nonce'] ) ), 'network_dashboard_' . get_current_user_id() )
            && $_POST['new-snapshot'] ) {

            dt_save_log( 'multisite', '', false );
            dt_save_log( 'multisite', 'REFRESH SNAPSHOT', false );
            $result = dt_get_multisite_snapshot( intval( sanitize_key( wp_unslash( $_POST['new-snapshot'] ) ) ) );
            if ( $result ) {
                $message = [ 'notice-success','Successful collection of new snapshot' ];
            }
            else {
                $message = [ 'notice-error', 'Failed collection' ];
            }
        }
        // Get list of sites


        $snapshots = dt_multisite_dashboard_snapshots();
        $number_of_snapshots = count( $snapshots );

        /** Message */
        if ( $message ) {
            echo '<div class="notice '.esc_attr( $message[0] ).'">'.esc_html( $message[1] ).'</div>';
        }

        /** Box */
        ?>
        <form method="post">
            <?php wp_nonce_field( 'network_dashboard_' . get_current_user_id(), 'network_dashboard_nonce' ) ?>
            <p><strong>Network Dashboard Snapshots of Connected Sites (<?php echo $number_of_snapshots; ?>)</strong></p>
            <table class="widefat striped">
                <thead>
                <th>ID</th>
                <th>Site Name</th>
                <th>Domain</th>
                <th>Snapshot</th>
                <th>Last Snapshot</th>
                <th></th>
                </thead>
                <tbody>
                <?php
                if ( ! empty( $snapshots ) ) {
                    foreach ( $snapshots as $blog_id => $snapshot ) {

                        ?>
                        <tr>
                            <td>
                                <?php echo esc_html( $blog_id ) ?>
                            </td>
                            <td>
                                <?php echo '<strong>' . esc_html( $snapshot['profile']['partner_name'] ?? '' ) . '</strong>' ?>
                            </td>
                            <td>
                                <?php echo '<a href="'. esc_url( get_site_url( $blog_id ) ) .'" target="_blank">' . get_site_url( $blog_id ) . '</a>' ?>
                            </td>
                            <td>
                                <?php echo ( ! empty( $snapshot ) ) ? '&#9989;' : '&#x2718;' ?>
                            </td>
                            <td>
                                <?php echo ( ! empty( $snapshot['date'] ) ) ? date( 'Y-m-d H:i:s', $snapshot['date'] ) : '&#x2718;' ?>
                            </td>
                            <td>
                                <button value="<?php echo esc_attr( $blog_id ) ?>" name="new-snapshot" type="submit" class="button" >Refresh Snapshot</button>
                            </td>
                        </tr>
                        <?php
                    } // end foreach

                }
                else {
                    ?>
                    <tr>
                        <td colspan="6">
                            No dashboard sites found.
                        </td>
                    </tr>
                    <?php
                }
                ?>

                </tbody>
            </table>
            <div style="display:none;padding:2em;" id="fail-error"></div>
        </form>
        <br>
        <!-- End Box -->
        <?php

    }

    public function logging_viewer() {
        if ( ! file_exists( dt_get_log_location( 'multisite' ) ) ) {
            dt_save_log( 'multisite', 'New log file' );
            dt_reset_log( 'multisite' );
        }
        ?>
        <a id="logs"></a>
        <div style="padding: 1.2em;"><strong>Recent Cron Log</strong> <span style="float:right;"> <a href="javascript:void(0)" onclick="document.getElementById('log_viewer').contentWindow.location.reload();">reload</a></span></div>
        <table class="widefat striped">
            <tr>
                <td>
                    <iframe id="log_viewer" src="<?php echo dt_get_log_location( 'multisite', 'url' ) ?>" width="100%" height="800px" scrolling="yes"></iframe>
                </td>
            </tr>
        </table>
        <?php
    }
}

/**
 * Class DT_Network_Dashboard_Tab_Remote_Snapshots
 */
class DT_Network_Dashboard_Tab_Remote_Snapshots
{
    public function content() {
        ?>
        <div class="wrap">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-1">
                    <div id="post-body-content">

                        <!-- Main Column -->
                        <?php $this->process_full_list() ?>
                        <?php $this->main_column() ?>
                        <?php $this->logging_viewer() ?>

                        <!-- End Main Column -->
                    </div><!-- end post-body-content -->

                </div><!-- post-body meta box container -->
            </div><!--poststuff end -->
        </div><!-- wrap end -->
        <?php
    }

    public function process_full_list() {
        ?>
        <p><strong>Snapshot Collection</strong></strong></p>
        <table class="widefat striped">
            <tbody>
                <tr>
                    <td>
                        <button class="button" id="rerun-collection" type="button">Refresh All Snapshots</button>
                        &nbsp;<span id="rerun-collection-spinner" style="display: none;"><img src="<?php echo plugin_dir_url( __DIR__ ) ?>spinner.svg" width="25px" /></span>
                        &nbsp;<span id="result-message"></span>

                        <span style="float:right;"><a href="#logs">View logs</a></span>
                    </td>
                </tr>
            </tbody>
        </table>
        <script>
        jQuery(document).ready(function() {
            jQuery('#rerun-collection').click( function() {
                let spinner = jQuery('#rerun-collection-spinner')
                spinner.show()
                jQuery.ajax({
                    type: "POST",
                    contentType: "application/json; charset=utf-8",
                    dataType: "json",
                    url: '<?php echo esc_url( rest_url() ) ?>dt/v1/network/admin/trigger_snapshot_collection',
                    beforeSend: function (xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', '<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ); ?>');
                    },
                })
                    .done(function (data) {
                        console.log(data)
                        if ( data ) {
                            spinner.hide()
                            jQuery('#result-message').html('Snapshot refresh began successfully. Check logs below for updates.')
                        }
                    })
                    .fail(function (err) {
                        console.log(err)
                        jQuery('#result-message').html('Snapshot refresh unsuccessful. View console for errors.')
                    })
            })
        })
        </script>
        <?php
    }

    public function main_column() {
        $message = false;
        if ( isset( $_POST['network_dashboard_nonce'] )
            && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['network_dashboard_nonce'] ) ), 'network_dashboard_' . get_current_user_id() )
            && isset( $_POST['new-snapshot'] ) ) {

            dt_save_log( 'remote', '', false );
            dt_save_log( 'remote', 'REFRESH SNAPSHOT', false );
            $result = dt_get_site_snapshot( intval( sanitize_key( wp_unslash( $_POST['new-snapshot'] ) ) ) );
            if ( $result ) {
                $message = [ 'notice-success','Successful collection of new snapshot.' ];
            }
            else {
                $message = [ 'notice-error', 'Failed collection' ];
            }
        }
        // Get list of sites
        $sites = DT_Network_Dashboard_Queries::site_link_list();

        /** Message */
        if ( $message ) {
            echo '<div class="notice '.esc_attr( $message[0] ).'">'.esc_html( $message[1] ).'</div>';
        }

        /** Box */
        ?>
        <form method="post">
            <?php wp_nonce_field( 'network_dashboard_' . get_current_user_id(), 'network_dashboard_nonce' ) ?>
            <p><strong>Network Dashboard Snapshots of Connected Sites</strong></p>
            <table class="widefat striped">
                <thead>
                    <th></th>
                    <th>Site Name</th>
                    <th>ID</th>
                    <th>Snapshot</th>
                    <th>Last Snapshot</th>
                    <th>Success</th>
                    <th></th>
                </thead>
                <tbody>
                <?php
                if ( ! empty( $sites ) ) {
                    $i = 1;
                    foreach ( $sites as $site ) {
                        $site_meta = get_post_meta( $site['id'] );
                        if ( isset( $site_meta['snapshot_fail'][0] ) && ! empty( $site_meta['snapshot_fail'][0] ) ) {
                            $fail = maybe_serialize( $site_meta['snapshot_fail'][0] );
                        } else {
                            $fail = '';
                        }
                        ?>
                        <tr>
                            <td style="width:10px;">
                                <?php echo $i;
                                $i++; ?>
                            </td>
                            <td>
                               <?php echo $site['name'] ?>
                            </td>
                            <td>
                               <?php echo $site['id'] ?>
                            </td>
                            <td>
                               <?php echo ( ! empty( $site_meta['snapshot'][0] ) ) ? '&#x2714;' : '&#x2718;' ?>
                            </td>
                            <td>
                               <?php echo ( ! empty( $site_meta['snapshot_date'][0] ) ) ? date( 'Y-m-d H:i:s', $site_meta['snapshot_date'][0] ) : '&#x2718;' ?>
                            </td>
                            <td>
                                <?php echo ( empty( $site_meta['snapshot_fail'][0] )
                                        && ! empty( $site_meta['snapshot'][0] ) ) ? '&#x2714;' :
                                    '<span style="color:red;" onclick="jQuery(\'#fail-error\').show().append(jQuery(\'#fail-'.$site['id'].'\').html())">&#x2718; view error below</span>
                                     <span style="display:none;" id="fail-'.$site['id'].'">'. $fail .'</span>'
                                ?>
                            </td>
                            <td>
                                <button value="<?php echo esc_attr( $site['id'] ) ?>" name="new-snapshot" type="submit" class="button" >Refresh Snapshot</button>
                            </td>
                        </tr>
                        <?php
                    } // end foreach
                }
                else {
                    ?>
                    <tr>
                        <td>
                            No dashboard sites found.
                        </td>
                    </tr>
                    <?php
                }
                ?>

                </tbody>
            </table>
            <div style="display:none;padding:2em;" id="fail-error"></div>
        </form>
        <br>
        <!-- End Box -->
        <?php
    }

    public function logging_viewer() {
        if ( ! file_exists( dt_get_log_location( 'remote' ) ) ) {
            dt_save_log( 'remote', 'New log file' );
            dt_reset_log( 'remote' );
        }
        ?>
        <a id="logs"></a>
        <div style="padding: 1.2em;"><strong>Recent Cron Log</strong> <span style="float:right;"> <a href="javascript:void(0)" onclick="document.getElementById('log_viewer').contentWindow.location.reload();">reload</a></span></div>
        <table class="widefat striped">
            <tr>
                <td>
                    <iframe id="log_viewer" src="<?php echo dt_get_log_location( 'remote', 'url' ) ?>" width="100%" height="800px" scrolling="1"></iframe>
                </td>
            </tr>
        </table>

        <?php
    }
}



class DT_Network_Dashboard_Tab_Local
{
    public function content() {
        ?>
        <div class="wrap">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <!-- Main Column -->

                        <?php $this->box_partner_profile() ?>
                        <?php $this->box_mapbox_status() ?>
                        <?php $this->box_ipstack_api_key() ?>
                        <?php $this->box_site_link() ?>

                        <!-- End Main Column -->
                    </div><!-- end post-body-content -->
                    <div id="postbox-container-1" class="postbox-container">
                        <!-- Right Column -->

                        <?php $this->overview_message() ?>
                        <?php $this->box_send_text() ?>
                        <?php $this->box_top_nav_item() ?>

                        <!-- End Right Column -->
                    </div><!-- postbox-container 1 -->
                    <div id="postbox-container-2" class="postbox-container">
                    </div><!-- postbox-container 2 -->
                </div><!-- post-body meta box container -->
            </div><!--poststuff end -->
        </div><!-- wrap end -->
        <?php
    }

    public function overview_message() {
        ?>
        <style>dt { font-weight:bold;}</style>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
            <th>Overview of Plugin</th>
            </thead>
            <tbody>
            <tr>
                <td>
                    <dl>
                        <dt>Plugin Purpose</dt>
                        <dd>Collecting reports across many systems is difficult and doing it automatically, even more so. Making sure
                            counts for certain location are counted only once you need a shared database of locations to post counts to.
                            This network mapping plugin attempts to set up a globally consistent mapping schema.</dd>

                        <dt>Local vs Network Functions</dt>
                        <dd>This plugin has two functions.
                            <ol>
                                <li> First to extend Disciple Tools with structured mapping data
                                    and to make it easy to install those locations for a team to use as they reach out to a certain area.
                                </li>
                                <li>This plugin also has the ability to add a network (global) dashboard to Disciple Tools for
                                    multiple Disciple Tools teams to connect their systems and share reporting (i.e. celebration) of the
                                    work between them.
                                </li>
                            </ol>
                        </dd>

                    </dl>

                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php
    }



    public function box_top_nav_item() {
        if ( isset( $_POST['network_dashboard_nonce'] )
            && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['network_dashboard_nonce'] ) ), 'network_dashboard_' . get_current_user_id() )
            && isset( $_POST['hide_top_nav'] ) ) {

            $selection = sanitize_text_field( wp_unslash( $_POST['hide_top_nav'] ) );
            if ( $selection === 'hide' ) {
                update_option( 'dt_hide_top_menu', true, true );
            }
            if ( $selection === 'show' ) {
                delete_option( 'dt_hide_top_menu' );
            }
        }
        $state = get_option( 'dt_hide_top_menu' );
        ?>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
            <tr>
                <th>Hide Top Nav Except "Network"</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    For appearance, the default top nav of Disciple Tools (i.e. Contacts, Groups, Metrics)
                    can be hidden for appearance, if you are only using this site as a dashboard and not managing
                    contacts or groups here locally.
                </td>
            </tr>
            <tr>
                <td>
                    <form method="post">
                        <?php wp_nonce_field( 'network_dashboard_' . get_current_user_id(), 'network_dashboard_nonce' ) ?>
                        <select name="hide_top_nav">
                            <option value="show" <?php echo empty($state) ? '' : ' selected'; ?>>Show</option>
                            <option value="hide" <?php echo ($state) ? ' selected' : ''; ?>>Hide</option>
                        </select>
                        <button type="submit" class="button">Update</button>
                    </form>
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php
    }

    public function box_mapbox_status() {
        ?>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
            <th>Mapbox Upgrade Status</th>
            </thead>
            <tbody>
            <tr>
                <td>
                    The presence of a mapbox key upgrades the mapping features of the network dashboard automatically.
                </td>
            </tr>
            <tr>
                <td>
                    Mapbox Key Installed : <?php echo ( class_exists( 'DT_Mapbox_API' ) && empty( DT_Mapbox_API::get_key() ) ) ? '<span style="color:red;">&#x2718;</span>' : '&#9989;' ; ?>
                </td>
            </tr>
            <tr>
                <td>
                    <a href="<?php echo esc_url( admin_url() ) ?>admin.php?page=dt_mapping_module&tab=geocoding">Disciple Tools Geocoding Tab</a>
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php
    }

    public function box_ipstack_api_key(){
        DT_Ipstack_API::metabox_for_admin();
    }

    public function box_partner_profile()
    {
        // process post action
        if (isset($_POST['partner_profile_form'])
            && isset($_POST['_wpnonce'])
            && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'partner_profile' . get_current_user_id())
            && isset($_POST['partner_name'])
            && isset($_POST['partner_description'])
            && isset($_POST['partner_id'])
        ) {
            $partner_profile = [
                'partner_name' => sanitize_text_field(wp_unslash($_POST['partner_name'])) ?: get_option('blogname'),
                'partner_description' => sanitize_text_field(wp_unslash($_POST['partner_description'])) ?: get_option('blogdescription'),
                'partner_id' => sanitize_text_field(wp_unslash($_POST['partner_id'])) ?: Site_Link_System::generate_token(40),
            ];

            update_option('dt_site_partner_profile', $partner_profile, true);
        }
        $partner_profile = get_option('dt_site_partner_profile');

        ?>
        <!-- Box -->
        <form method="post">
            <?php wp_nonce_field('partner_profile' . get_current_user_id()); ?>
            <table class="widefat striped">
                <thead>
                <tr><th>Network Profile</th></tr>
                </thead>
                <tbody>
                <tr>
                    <td>
                        <table class="widefat">
                            <tbody>
                            <tr>
                                <td><label for="partner_name">Your Group Name</label></td>
                                <td><input type="text" class="regular-text" name="partner_name"
                                           id="partner_name"
                                           value="<?php echo esc_html($partner_profile['partner_name']) ?>"/></td>
                            </tr>
                            <tr>
                                <td><label for="partner_description">Your Group Description</label></td>
                                <td><input type="text" class="regular-text" name="partner_description"
                                           id="partner_description"
                                           value="<?php echo esc_html($partner_profile['partner_description']) ?>"/>
                                </td>
                            </tr>
                            <tr>
                                <td><label for="partner_id">Site ID</label></td>
                                <td><?php echo esc_attr($partner_profile['partner_id']) ?>
                                    <input type="hidden" class="regular-text" name="partner_id"
                                           id="partner_id"
                                           value="<?php echo esc_attr($partner_profile['partner_id']) ?>"/></td>
                            </tr>
                            </tbody>
                        </table>

                        <p><br>
                            <button type="submit" id="partner_profile_form" name="partner_profile_form"
                                    class="button">Update
                            </button>
                        </p>
                    </td>
                </tr>
                </tbody>
            </table>
        </form>
        <br>
        <!-- End Box -->
        <?php
    }

    public function box_send_text()
    {
        $report = false;
        if (isset($_POST['test_send_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['test_send_nonce'])), 'test_send_' . get_current_user_id())) {
            $report = DT_Network_Dashboard_Snapshot_Report::snapshot_report();

        }
        ?>
        <table class="widefat striped">
            <thead>
            <tr>
                <th>Site Links</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <form method="post">
                        <?php wp_nonce_field('test_send_' . get_current_user_id(), 'test_send_nonce', false, true) ?>
                        <button type="submit" name="send_test" class="button"><?php esc_html_e('Send Test') ?></button>
                    </form>
                    <?php
                    if ($report) {
                        echo esc_html(maybe_serialize($report));
                    }
                    ?></td>
            </tr>
            </tbody>
        </table>
        <br>
        <?php
    }

    public function box_site_link()
    {
        global $wpdb;

        $site_links = $wpdb->get_results("
        SELECT p.ID, p.post_title, pm.meta_value as type
            FROM $wpdb->posts as p
              LEFT JOIN $wpdb->postmeta as pm
              ON p.ID=pm.post_id
              AND pm.meta_key = 'type'
            WHERE p.post_type = 'site_link_system'
              AND p.post_status = 'publish'
        ", ARRAY_A);

        ?>
        <table class="widefat striped">
            <thead>
            <tr><th>Site Links</th></tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <?php
                    if (!is_array($site_links)) {
                        ?>
                        No site links found. Go to <a href="<?php echo esc_url(admin_url()) ?>edit.php?post_type=site_link_system">Site Links</a> and create a site link, and then select "Network Report" as the type.
                        <?php
                    }
                    ?>
                    <h2>Can Send Reports to these Sites</h2>
                    <?php
                    foreach ($site_links as $site) {
                        if ('network_dashboard_sending' === $site['type'] || 'network_dashboard_both' === $site['type'] ) {
                            ?>
                            <dd><a href="<?php echo esc_url(admin_url()) ?>post.php?post=<?php echo esc_attr($site['ID']) ?>&action=edit"><?php echo esc_html($site['post_title']) ?></a></dd>
                            <?php
                        }
                    }
                    ?>

                    <h2>Can Receive Reports from these Sites</h2>
                    <?php
                    foreach ($site_links as $site) {
                        if ('network_dashboard_receiving' === $site['type'] || 'network_dashboard_both' === $site['type'] ) {
                            ?>
                            <dd><a href="<?php echo esc_url(admin_url()) ?>post.php?post=<?php echo esc_attr($site['ID']) ?>&action=edit"><?php echo esc_html($site['post_title']) ?></a></dd>
                            <?php
                        }
                    }
                    ?>
                    <h2><a onclick="jQuery('#other-links').toggle()" href="javascript:void(0)">Non-Dashboard Site Links</a></h2>
                    <div id="other-links" style="display:none;">
                        <?php
                        foreach ($site_links as $site) {
                            if (!('network_dashboard_sending' === $site['type'] || 'network_dashboard_receiving' === $site['type'] || 'network_dashboard_both' === $site['type'] ) ) {
                                ?>
                                <dd><a href="<?php echo esc_url(admin_url()) ?>post.php?post=<?php echo esc_attr($site['ID']) ?>&action=edit"><?php echo esc_html($site['post_title']) ?></a></dd>
                                <?php
                            }
                        }
                        ?>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <?php
    }
}


/**
 * Class DT_Network_Dashboard_Tab_Activity
 */
class DT_Network_Dashboard_Tab_Activity
{
    public function content() {
        ?>
        <form method="post">
            <?php wp_nonce_field( 'activity'. get_current_user_id(), 'activity-nonce') ?>
            <div class="wrap">
                <div id="poststuff">
                    <div id="post-body" class="metabox-holder columns-2">
                        <div id="post-body-content">
                            <!-- Main Column -->

                            <?php $this->box_site_link()?>

                            <!-- End Main Column -->
                        </div><!-- end post-body-content -->
                        <div id="postbox-container-1" class="postbox-container">
                            <!-- Right Column -->

                            <?php $this->sidebar_update_settings() ?>
                            <?php $this->sidebar() ?>

                            <!-- End Right Column -->
                        </div><!-- postbox-container 1 -->
                        <div id="postbox-container-2" class="postbox-container">
                        </div><!-- postbox-container 2 -->
                    </div><!-- post-body meta box container -->
                </div><!--poststuff end -->
            </div><!-- wrap end -->
        </form>
        <?php
    }

    public function box_site_link()
    {
        if ( isset( $_POST['activity-nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['activity-nonce'] ) ), 'activity'. get_current_user_id() ) ) {
//            dt_write_log($_POST);
            if ( isset( $_POST['activity_log'] ) && ! empty( $_POST['activity_log'] ) && is_array( $_POST['activity_log'] ) ) {
                foreach($_POST['activity_log'] as $i => $v ) {
                    update_post_meta( sanitize_text_field( wp_unslash( $i ) ), 'send_activity_log',  sanitize_text_field( wp_unslash( $v ) ) );
                }
            }
        }
        global $wpdb;

        $site_links = $wpdb->get_results("
        SELECT p.ID, p.post_title, pm.meta_value as type, pa.meta_value as send_activity_log
            FROM $wpdb->posts as p
              LEFT JOIN $wpdb->postmeta as pm ON p.ID=pm.post_id AND pm.meta_key = 'type'
              LEFT JOIN $wpdb->postmeta as pa ON p.ID=pa.post_id AND pa.meta_key = 'send_activity_log'
            WHERE p.post_type = 'site_link_system'
              AND p.post_status = 'publish'
              AND ( pm.meta_value = 'network_dashboard_both' OR pm.meta_value = 'network_dashboard_sending' )
        ", ARRAY_A);
//        dt_write_log($site_links);

        // @todo Add strategy for sending to multisite and saving to multisite

        ?>
        <?php
        if (!is_array($site_links)) :
            ?>
            No site links found. Go to <a href="<?php echo esc_url(admin_url()) ?>edit.php?post_type=site_link_system">Site Links</a> and create a site link, and then select "Network Report" as the type.
        <?php
        else :
            ?>
            <table class="widefat striped">
                <thead>
                <tr>
                    <td style="width:300px;">Available Sites to Send Activity Logs</td>
                    <td>Send Activity</td>
                </tr>
                </thead>
                <tbody>
                <?php
                foreach ($site_links as $site) {
                    if ('network_dashboard_sending' === $site['type'] || 'network_dashboard_both' === $site['type'] ) {
                        ?>
                        <tr><td><a href="<?php echo esc_url(admin_url()) ?>post.php?post=<?php echo esc_attr($site['ID']) ?>&action=edit"><?php echo esc_html($site['post_title']) ?></a></td>
                            <td><input type="radio" name="activity_log[<?php echo esc_attr($site['ID']) ?>]" value="yes" <?php echo ($site['send_activity_log'] === 'yes' ) ? 'checked' : '' ?>/> Yes | <input type="radio" name="activity_log[<?php echo esc_attr($site['ID']) ?>]" value="no" <?php echo ($site['send_activity_log'] === 'yes' ) ? '' : 'checked' ?>/> No</td></tr>
                        <?php
                    }
                }
                ?>
                </tbody>
            </table>
        <?php
        endif;
        ?>
        <br>
        <?php
    }

    public function main() {
        ?>
        <style>dt { font-weight:bold;}</style>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
            <th>Activity</th>
            </thead>
            <tbody>
            <tr>
                <td>
                    <dl>
                        <dt>Title</dt>
                        <dd>Content</dd>

                        <dt>Title</dt>
                        <dd>Content</dd>
                    </dl>
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php
    }

    public function sidebar() {
        ?>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
            <th>Notes</th>
            </thead>
            <tbody>
            <tr>
                <td>
                    Enable these site connections for
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php
    }

    public function sidebar_update_settings() {
        ?>
        <!-- Box -->
        <table class="widefat">
            <thead>
            <tr><td>Update Configuration</td></tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <form method="post">
                        <button type="submit" class="button">Update</button><br>
                    </form>
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php
    }
}

/**
 * Class DT_Network_Dashboard_Tab_Tutorial
 */
class DT_Network_Dashboard_Tab_Cron
{
    public function content() {
        DT_Network_Dashboard_Snapshot_Report::snapshot_report();
        ?>
        <div class="wrap">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <!-- Main Column -->


                        <?php $this->metabox_cron_list() ?>

                        <!-- End Main Column -->
                    </div><!-- end post-body-content -->
                    <div id="postbox-container-1" class="postbox-container">
                        <!-- Right Column -->

                        <?php $this->box_instructions() ?>

                        <!-- End Right Column -->
                    </div><!-- postbox-container 1 -->
                    <div id="postbox-container-2" class="postbox-container">
                    </div><!-- postbox-container 2 -->
                </div><!-- post-body meta box container -->
            </div><!--poststuff end -->
        </div><!-- wrap end -->
        <?php
    }

    public function metabox_cron_list() {

        if ( isset( $_POST['cron_run_nonce'] )
            && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['cron_run_nonce'] ) ), 'cron_run_' . get_current_user_id() )
            && isset( $_POST['run_now'] ) ) {

            dt_write_log($_POST);

            $hook = sanitize_text_field( wp_unslash( $_POST['run_now'] ) );
            $timestamp = wp_next_scheduled( $hook );
            wp_unschedule_event( $timestamp, $hook );

            // @todo push a run

        }
        $cron_list = _get_cron_array();
        ?>
        <!-- Box -->

        <table class="widefat striped">
            <thead>
            <tr>
                <th>External Cron Schedule</th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php
            foreach( $cron_list as $time => $time_array ){
                foreach( $time_array as $token => $token_array ){
                    if ( 'dt_' === substr( $token, 0, 3 ) ){
                        foreach( $token_array as $key => $items ) {
                            ?>
                            <tr>
                                <td>
                                    <?php echo 'Next event in ' . round( ( $time - time() ) / 60 / 60 , 1) . ' hours' ?><br>
                                    <?php echo date( 'Y-m-d H:i:s', $time  )?><br>
                                </td>
                                <td>
                                    <?php echo $token ?>
                                </td>
                                <td>
                                    <?php echo $key ?>
                                </td>
                                <td>
                                    <?php echo $items['schedule'] ?? '' ?><br>
                                    Every <?php echo isset($items['interval']) ? $items['interval'] / 60 . ' minutes' : '' ?><br>
                                    <?php echo ! empty($items['args']) ? serialize( $items['args'] ) : '' ?><br>
                                </td>
                                <td>
                                    <form method="post">
                                        <?php wp_nonce_field( 'cron_run_' . get_current_user_id(), 'cron_run_nonce' ) ?>
                                        <button type="submit" name="run_now" value="<?php echo $token ?>" class="button">Delete and Respawn</button>
                                    </form>
                                </td>
                            </tr>
                            <?php
                        }
                    }
                }
            }
            ?>
            </tbody>
        </table>

        <br>
        <!-- End Box -->
        <?php
    }

    public function box_instructions() {
        ?>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
            <th>What is CRON?</th>
            </thead>
            <tbody>
            <tr>
                <td>
                    Cron is a time based task often scheduled to occur at regular times. You can have some tasks in a software run instantly, while others might build up and be run at a certain time.
                    The Network Dashboard collects snapshots from remote sites in this time based way. Because these are processor intensive tasks/queries we do not run them instantly, so as to protect the performance of the
                    Disciple.Tools system.
                </td>
            </tr>
            <tr>
                <td>
                    Although it is not required, if there are concerns about the timely distribution of posts/messages/emails or collection of snapshots, then it is very simple
                    to add an external cron service.
                </td>
            </tr>
            <tr>
                <td>
                    <p><strong>EXPLANATION</strong><br></p>
                    Wordpress/Disciple.Tools Cron System depends on visits to trigger background processes. If the site is not visited regularly
                    like a normal website would be, it is possible to use an external cron service to call the site regularly and trigger these
                    background tasks. If the Network Dashboard is configured for frequent collections in the section above and you notice
                    these services not running when expected, you can schedule an external cron service to connect to the site on a regular basis.
                </td>
            </tr>
            <tr>
                <td>
                    <p><strong>SERVICES</strong></p>
                    <ul>
                        <li><a href="https://cron-job.org/en/">Cron-Job.org</a></li>
                        <li><a href="https://www.easycron.com/">EasyCron</a></li>
                        <li><a href="https://cronless.com/">Cronless</a></li>
                        <li>Or Google "free cron services"</li>
                    </ul>
                </td>
            </tr>
            <tr>
                <td>
                    <p><strong>CRON URL</strong><br></p>
                    <code><?php echo esc_url( site_url() ) . '/wp-cron.php' ?></code>
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php
    }
}

/**
 * Class DT_Network_Dashboard_Tab_Tutorial
 */
class DT_Network_Dashboard_Tab_Tutorial
{
    public function content() {
        ?>
        <div class="wrap">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <!-- Main Column -->

                        <?php $this->main() ?>

                        <!-- End Main Column -->
                    </div><!-- end post-body-content -->
                    <div id="postbox-container-1" class="postbox-container">
                        <!-- Right Column -->

                        <?php $this->sidebar() ?>

                        <!-- End Right Column -->
                    </div><!-- postbox-container 1 -->
                    <div id="postbox-container-2" class="postbox-container">
                    </div><!-- postbox-container 2 -->
                </div><!-- post-body meta box container -->
            </div><!--poststuff end -->
        </div><!-- wrap end -->
        <?php
    }

    public function main() {
        ?>
        <style>dt { font-weight:bold;}</style>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
            <th>Tutorials</th>
            </thead>
            <tbody>
            <tr>
                <td>
                    <dl>
                        <dt>Title</dt>
                        <dd>Content</dd>

                        <dt>Title</dt>
                        <dd>Content</dd>
                    </dl>
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php
    }

    public function sidebar() {
        ?>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
            <th>Outline</th>
            </thead>
            <tbody>
            <tr>
                <td>

                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php
    }
}



