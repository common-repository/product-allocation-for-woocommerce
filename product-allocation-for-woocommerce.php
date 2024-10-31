<?php
/**
 * Plugin Name: Product Allocation for WooCommerce
 * Plugin URI: http://www.shop.hh-studio.com/product/product-allocation-for-woo-commerce/
 * Description: Set the maximum quantity of a WooCommerce product a customer is allowed to purchase.
 * Version: 1.0.0
 * Author: HH-Studio | Arslan
 * Author URI: http://hh-studio.com
 * Requires at least: 4.0.0
 * Tested up to: 4.5.2
 * 
 * Text Domain: product-allocation-for-woocommerce
 * Domain Path: /languages/
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

// Only if the woocommerce is there and active
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

    $wcallocation_total_sku = 6;
    $wcallocation_total_tier = 6;
    $wcallocation_max_allocation = 99;
    $wcallocation_min_allocation = 0;
    $wcallocation_values = get_option( 'wcallocation_values', false );
    $wcallocation_for_guest = 0; // available allocations for all products for guest
    $wcallocations_out_of_time_range = 0; // available allocations for all products in out of time range
    $wcallocation_unspecified_sku_for_loggedin_user = 0; // available allocations for logged in user for unspecified product sku
    $wcallocation_unspecified_user_tier_allocations = 0; //tier was not specified for this user, but sku was specified for product

    $keep_track = true; // if false, the plugin will limit the allocation for single order

    class WC_Settings_Tab_Allocation {

        /**
         * Bootstraps the class and hooks required actions & filters.
         *
         */
        public static function init() {
            add_filter( 'woocommerce_settings_tabs_array', __CLASS__ . '::add_settings_tab', 50 );
            add_action( 'woocommerce_settings_tabs_settings_tab_allocation', __CLASS__ . '::settings_tab' );
            add_action( 'woocommerce_update_options_settings_tab_allocation', __CLASS__ . '::update_settings' );
        }

        /**
         * Add the settings section to WooCommerce settings.
         * @param array $settings_tabs
         */
        public static function add_settings_tab( $settings_tabs ) {
            //make sure the tab is between product and tax tabs
            $temp_arrange = array();
            foreach($settings_tabs as $k => $v){
                if( 'tax' == $k ){
                    $temp_arrange['settings_tab_allocation'] = __( 'Allocations', 'product-allocation-for-woocommerce' );
                }
                $temp_arrange[$k] = $v;
            }
            return $temp_arrange;
        }
        /*
         * Generate HTML for Allocations table
         */
        public static function settings_tab() {
            global $wcallocation_total_sku;
            global $wcallocation_total_tier;
            global $wcallocation_max_allocation;
            global $wcallocation_min_allocation;
            global $wcallocation_values;

            $total_sku = $wcallocation_total_sku;
            $total_tier = $wcallocation_total_tier;
            $sku_html = WC_Settings_Tab_Allocation::get_sku_html();
            $max_allocation = $wcallocation_max_allocation;
            $min_allocation = $wcallocation_min_allocation;

            wp_enqueue_script(
                'wcallocation-datetime-picker-js',
                plugins_url( 'datetimepicker/build/jquery.datetimepicker.full.min.js', __FILE__ ),
                array('jquery')
            );
            wp_enqueue_style(
                'wcallocation-datetime-picker-css',
                plugins_url( 'datetimepicker/jquery.datetimepicker.css', __FILE__ )
            );
        ?>
            <p><?php _e( 'Fill in allocations for each sku for each tier.', 'product-allocation-for-woocommerce' ); ?></p>
            <table class="wcallocation-table">
                <thead>
                <tr>
                    <th></th>
                    <?php
                        for($i=1; $i<=$total_sku; $i++){
                    ?>
                        <th><?php printf( __( 'SKU %s', 'product-allocation-for-woocommerce' ), $i ); ?></th>
                    <?php } ?>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td></td>
                    <?php
                    for($i=1; $i<=$total_sku; $i++){
                        ?>
                        <td><select class="skus" name="sku_<?php echo esc_attr( $i ); ?>"><?php echo $sku_html; ?></select></td>
                    <?php } ?>

                </tr>
                <?php for ($j =1 ; $j <= $total_tier ; $j++) { ?>
                    <tr>
                        <th><?php printf( __( 'Tier %s', 'product-allocation-for-woocommerce' ), $j ); ?></th>
                        <?php for($i=1; $i<=$total_sku; $i++){ ?>
                            <td><input type="number" class="wcallocation_values" name="sku_<?php echo $i;?>|tier_<?php echo esc_attr( $j ); ?>" min="<?php echo esc_attr( $min_allocation ); ?>" max="<?php echo esc_attr( $max_allocation ); ?>" step="1" /></td>
                        <?php } ?>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
            <br>
            <table class="allocation-datetime">
                <tr>
                    <td><?php _e( 'Allocation starts:', 'product-allocation-for-woocommerce' ); ?></td>
                    <td><input type="text" id="date_timepicker_start" name="wcallocation_starts" required value="<?php echo isset($wcallocation_values['wcallocation_starts'])?$wcallocation_values['wcallocation_starts']:"";?>"/></td>
                </tr>
                <tr>
                    <td><?php _e( 'Allocation ends:', 'product-allocation-for-woocommerce' ); ?></td>
                    <td><input type="text" id="date_timepicker_end" name="wcallocation_ends" required value="<?php echo isset($wcallocation_values['wcallocation_ends'])?$wcallocation_values['wcallocation_ends']:"";?>"/></td>
                </tr>
                <tr>
                    <td><?php _e( 'Apply Tier to all customers:', 'product-allocation-for-woocommerce' ); ?></td>
                    <td>
                        <select name="apply_tier_to_all_customers" id="apply_tier_to_all_customers">
                            <option value="0"><?php _e( 'Do not change tier', 'product-allocation-for-woocommerce' ); ?></option>
                            <?php for($i=1 ; $i <= $wcallocation_total_tier; ++$i) { ?>
                                <option value="<?php echo $i;?>">Tier <?php echo $i;?></option>
                            <?php } ?>
                        </select>
                        <span id="change_tier_except"><?php _e( 'Except:', 'product-allocation-for-woocommerce' ); ?> <input type="text" name="apply_tier_to_all_customers_except" /> <small><?php _e( 'Please insert User IDs separated with comma, e.g. 1,5,13', 'product-allocation-for-woocommerce' ); ?></small></span>
                    </td>
                </tr>
            </table>
            <style type="text/css">
                /*
                Generic Styling, for Desktops/Laptops
                */
                .wcallocation-table table {
                    width: 100%;
                    border-collapse: collapse;
                }
                /* Zebra striping */
                .wcallocation-table tr:nth-of-type(odd) {
                    background: #eee;
                }
                .wcallocation-table th {
                    background: #333;
                    color: white;
                    font-weight: bold;
                }
                .wcallocation-table td, .wcallocation-table th {
                    padding: 6px;
                    border: 1px solid #ccc;
                    text-align: left;
                }
                #change_tier_except{
                    display: none;
                }
            </style>

            <script type="text/javascript">
                jQuery(document).ready(function($){
                    var wcallocation_values = <?php echo json_encode($wcallocation_values); ?>;

                    $(wcallocation_values.products_sku).each(function(i){
                        var column = i;
                        var num = i+1;
                        var sku = this.toString();
                        if(this.toString()){
                            $("select[name=sku_"+(num)+"] option[value='"+this.toString()+"']").attr("selected", "selected");
                            if(sku != 0) {
                                $(".wcallocation-table tbody tr").each(function (j) {
                                    if (j > 0) {
                                        var tier = j;
                                        var temp_value = wcallocation_values["sku_" + num + "|tier_" + tier + "|" + sku];
                                        if(temp_value){
                                            $(this).find("input.wcallocation_values").eq(column).val(parseInt(temp_value));
                                        }

                                     }
                                });
                            }
                        }


                    });
                    $("#date_timepicker_start").datetimepicker(
                        {
                            theme:'dark',
                            onShow:function( ct ){
                                this.setOptions({
                                    maxDate:jQuery('#date_timepicker_end').val()?jQuery('#date_timepicker_end').val():false
                                })
                            }
                        }
                    );
                    $("#date_timepicker_end").datetimepicker(
                        {
                            theme:'dark',
                            onShow:function( ct ){
                                this.setOptions({
                                    minDate:jQuery('#date_timepicker_start').val()?jQuery('#date_timepicker_start').val():false
                                })
                            }
                        }
                    );

                    $("#mainform").submit(function(){
                        var no_duplicate = true;
                        var skus = [];
                        $("select.skus").each(function(i){
                            if(skus.indexOf($(this).val()) !== -1 && $(this).val() != "0"){
                                no_duplicate = false;
                                alert("ERROR\nYou have selected same product for multiple SKUs, that is "+$(this).val()+".\nAll SKUs must be unique.\nPlease, try again!");
                                $(this).focus();
                                return false;
                            }
                            else{
                                skus.push($(this).val());
                            }

                        });
                        if(parseInt($("#apply_tier_to_all_customers").val()) != 0){
                            var excepts = $("input[name=apply_tier_to_all_customers_except]").val().split(",");
                            var c_ids = [];
                            $(excepts).each(function(){
                                if(this != "" && parseInt(this) != 0 && !isNaN(this)){
                                    c_ids.push(parseInt(this));
                                }
                            });
                            $("input[name=apply_tier_to_all_customers_except]").val(c_ids.toString());
                            return window.confirm("You have selected to apply Tier "+$("#apply_tier_to_all_customers").val()+
                            " to all customers.\n"+(c_ids.length? "Tier will not be changed for users with following IDs: \n"+c_ids.toString()+"\n" : "")+
                            "Are you sure?");
                        }
                        return no_duplicate;
                    });
                     $("#apply_tier_to_all_customers").on("change",function(){
                        if(parseInt($(this).val()) == 0){
                            $("#change_tier_except").hide();
                        }
                         else{
                            $("#change_tier_except").show();
                        }
                     });
                });
             </script>
        <?php
        }

        public static function get_sku_html(){
            $options = "<option value='0'></option>";
            $args = array(
                'post_type' => 'product',
                'posts_per_page' => -1
            );
            $loop = new WP_Query( $args );
            if ( $loop->have_posts() ){
                while ( $loop->have_posts() ) {
                    $loop->the_post();

                    global $product;
                    if($product->get_sku()) {
                        $options .= "<option value='" . esc_attr( $product->get_sku() ) . "'>" . esc_html( $product->get_sku() ) . "</option>";
                    }
                }
            }
            wp_reset_postdata();
            return $options;
        }

        public static function update_settings() {
            $store = array();
            global $wcallocation_total_sku;
            global $wcallocation_total_tier;
            global $wcallocation_values;

            for ($j = 1; $j <= $wcallocation_total_sku ; ++$j){
                for($i = 1; $i <= $wcallocation_total_tier ; ++$i){
                    if(isset($_POST["sku_".$j."|"."tier_".$i]) && $_POST["sku_".$j."|"."tier_".$i] !== "" && isset($_POST["sku_".$j]) && $_POST["sku_".$j] !== "") {
                        $store["sku_" . $j . "|" . "tier_" . $i."|".($_POST["sku_".$j])] = $_POST["sku_" . $j . "|" . "tier_" . $i];
                    }
                }
                $store["products_sku"][] = $_POST["sku_".$j];
            };
            $store["wcallocation_starts"] = $_POST["wcallocation_starts"];
            $store["wcallocation_ends"] = $_POST["wcallocation_ends"];

            $wcallocation_values = $store;
            update_option("wcallocation_values", $wcallocation_values);

            if(isset($_POST['apply_tier_to_all_customers']) && $_POST['apply_tier_to_all_customers'] != "0"){
                if(isset($_POST['apply_tier_to_all_customers_except'])){
                    $exclude = explode(",", $_POST['apply_tier_to_all_customers_except']);
                }
                $tier = intval($_POST['apply_tier_to_all_customers']);
                $user_query = new WP_User_Query( array( 'exclude' => $exclude ) );
                $customers = $user_query->get_results();
                foreach ($customers as $customer)
                {
                    update_usermeta( $customer->ID, 'allocation_tier', intval( $tier ) );
                }
            }

        }

    }
    WC_Settings_Tab_Allocation::init();



    /* User field addition to profile for max quantity */

    add_action( 'show_user_profile', 'wcallocation_show_extra_profile_fields' );
    add_action( 'edit_user_profile', 'wcallocation_show_extra_profile_fields' );

    function wcallocation_show_extra_profile_fields( $user ) {
        global $wcallocation_total_tier;
        ?>

        <h3><?php _e( 'Allocation Tier', 'product-allocation-for-woocommerce' ); ?></h3>

        <table class="form-table">

            <tr>
                <th><label for="wcallocation_tier"><?php _e( 'Allocation Tier', 'product-allocation-for-woocommerce' ); ?></label></th>

                <td>
                    <select name="allocation_tier">
                        <option value="0"><?php _e( 'Please select', 'product-allocation-for-woocommerce' ); ?></option>
                        <?php
                        $tier = get_usermeta( $user->id, 'allocation_tier', "" );
                        for($i = 1; $i <= $wcallocation_total_tier; ++$i){

                            if($tier && $tier == $i){
                                $selected = "selected";
                            }
                            else{
                                $selected = "";
                            }
                            ?>
                            <option value="<?php echo esc_attr( $i ); ?>" <?php echo $selected;?>><?php printf( __( 'Tier %s', 'product-allocation-for-woocommerce' ), intval( $i ) ); ?></option>
                        <?php } ?>
                    </select>
                </td>
            </tr>

        </table>
    <?php }


    add_action( 'personal_options_update', 'wcallocation_save_extra_profile_fields' );
    add_action( 'edit_user_profile_update', 'wcallocation_save_extra_profile_fields' );

    function wcallocation_save_extra_profile_fields( $user_id ) {

        if ( !current_user_can( 'edit_user', $user_id ) )
            return false;

        if(isset($_POST['allocation_tier'])){
            update_usermeta( $user_id, 'allocation_tier', intval( $_POST['allocation_tier'] ) );
        }

    }

    /* Add field to add new user form */

    add_action( 'user_new_form', 'wcallocation_user_new_form' );

    function wcallocation_user_new_form()
    {
        global $wcallocation_total_tier;
        ?>

        <table class="form-table">
            <tbody>
            <tr class="form-field">
                <th scope="row"><label for="wcallocation_tier"><?php _e( 'Allocation Tier', 'product-allocation-for-woocommerce' ); ?></label></th>
                <td>
                    <select name="allocation_tier">
                        <option value="0"><?php _e( 'Please select', 'product-allocation-for-woocommerce' ); ?></option>
                        <?php for($i = 1; $i <= $wcallocation_total_tier; ++$i){ ?>
                            <option value="<?php echo esc_attr( $i ); ?>"><?php printf( __( 'Tier %s', 'product-allocation-for-woocommerce' ), intval( $i ) ); ?></option>
                        <?php } ?>

                    </select>
                </td>
            </tr>
            </tbody>
        </table>


        <?php
    }

    //  process the extra fields for new user form
    add_action( 'user_register', 'wcallocation_user_register', 10, 1 );

    function wcallocation_user_register( $user_id ){

        if ( isset( $_POST['allocation_tier'] ))
        {
            update_user_meta($user_id, 'allocation_tier', intval( $_POST['allocation_tier'] ) );
        }

    }

    /*
     * show allocation on product page
     */
    add_action ( 'woocommerce_single_product_summary', 'wcallocation_show_product_allocations', 40 );
    function wcallocation_show_product_allocations() {
        global $product;
        global $wcallocation_for_guest;
        echo __( 'Allocation: ', 'product-allocation-for-woocommerce' ).wcallocations_get_allocations($product, get_current_user_id());
    }

    /*
     * add allocation in cart
     */
    add_filter( 'woocommerce_cart_item_quantity', 'wcallocation_woocommerce_cart_item_quantity', 1, 3 );
    function wcallocation_woocommerce_cart_item_quantity($product_quantity, $cart_item_key, $cart_item){
        if(is_cart()){
            $product = $cart_item['data'];
            return $product_quantity.'<br><small>' . __( 'Allocation: ', 'product-allocation-for-woocommerce' ) . wcallocations_get_allocations( $product, get_current_user_id() ) . '</small>';
        }
        return $product_quantity;
    }

    /*
     * real implication of allocation for add to cart
     */

    function wcallocation_get_qty_already_in_cart( $the_id ) {
        global $woocommerce;

        $new_keys = array();
        $new_values = array();

        foreach($woocommerce->cart->get_cart() as $cart_item_key => $values ) {

            $product_id_key = isset ( $values['product_id'] ) ? $values['product_id'] : '';
            if( $product_id_key )
                $new_keys[] = $product_id_key;

            $qty_key = isset ( $values['quantity'] ) ? $values['quantity'] : '';
            if( $qty_key )
                $new_values[] = $qty_key;

        }

        if ( $new_keys && $new_values ) {
            $current_cart_quantities = array_combine($new_keys, $new_values);
        }

        $qty_exists = isset($current_cart_quantities[$the_id]) ? $current_cart_quantities[$the_id] : '';

        return $qty_exists;
    }

    add_action('woocommerce_add_to_cart_validation', 'wcallocation_add_to_cart_validation', 1, 3);
    function wcallocation_add_to_cart_validation($passed, $product_id, $quantity ) {
        global $woocommerce;
        global $wcallocations_for_guest;
        if(!is_user_logged_in() && $wcallocations_for_guest == 0){
            wc_add_notice(
                sprintf(__( 'You must be logged in and have an allocation. Please %1$s or %2$s.', 'product-allocation-for-woocommerce' ),
                    "<a href='".get_permalink(get_option( 'woocommerce_myaccount_page_id' ))."'>" . __( 'login', 'product-allocation-for-woocommerce' ) . "</a>",
                    "<a href='".wp_registration_url()."'>" . __( 'join', 'product-allocation-for-woocommerce' ) . "</a>"
                ), 'error');
            return false;
        }

        $product = get_product( $product_id );
        $alread_in_cart = wcallocation_get_qty_already_in_cart( $product_id );
        $allocations = wcallocations_get_allocations($product, get_current_user_id());

        $product_title = $product->post->post_title;

        if ( ! empty( $alread_in_cart ) ) {
            $new_qty = $alread_in_cart + $quantity;
            if ( $new_qty > $allocations ) {
                wc_add_notice( sprintf( __( 'Your allocation of %1$s is %2$s. You already have %3$s in cart.', 'product-allocation-for-woocommerce' ),
                    $product_title,
                    $allocations,
                    $alread_in_cart), 'error' );
                return false;
            }
        }
        else {
            if ( $quantity > $allocations ) {

                wc_add_notice( sprintf( __( 'Your allocation of %1$s is %2$s.', 'product-allocation-for-woocommerce' ),
                    $product_title,
                    $allocations), 'error' );
                return false;
            }

        }
        return true;

    }
    /* Update cart */
    function wcallocation_qty_update_cart_validation( $passed, $cart_item_key, $values, $quantity ) {

        global $woocommerce;
        $product_id = $values['product_id'];
        $product = get_product( $product_id );
        $allocation = wcallocations_get_allocations($product, get_current_user_id());
        $product_title = $product->post->post_title;

        if ( ! empty( $allocation ) ) {
            if ( $quantity > $allocation ) {
                wc_add_notice( sprintf( __( 'Your allocation of %1$s is %2$s.', 'product-allocation-for-woocommerce' ),
                    $product_title,
                    $allocation), 'error' );
                return false;
            }
        }

        return true;
    }
    add_action( 'woocommerce_update_cart_validation', 'wcallocation_qty_update_cart_validation', 1, 4 );

    function wcallocations_get_allocations($product, $user_id){
        if($user_id == 0){
            global  $wcallocation_for_guest;
            return $wcallocation_for_guest;
        }
        global $wcallocation_values;
        global $keep_track;
        $used_allocations = 0;
        if($keep_track){
            $args = array(
                'post_type' => 'shop_order',
                'post_status'   => 'publish',
                'meta_key'    => '_customer_user',
                'meta_value'  => get_current_user_id(),
                'date_query'    => array(
                    'column'  => 'post_date',
                    'after'   => date("Y-m-d H:i:s", strtotime($wcallocation_values['wcallocation_starts']))
                ),
            );
            $query = new WP_Query( $args );
            // Check that we have query results.
            if ( $query->have_posts() ) {
                // Start looping over the query results.
                $order_ids = wp_list_pluck($query->posts ,"ID");
                foreach($order_ids as $order_id){
                    $order = new WC_Order( $order_id );
                    $items = $order->get_items();
                    foreach ( $items as $item ) {
                        if($item['product_id'] == $product->id){
                            $qty = intval($item['item_meta']['_qty'][0]);
                            $used_allocations += $qty;
                            break;
                        }
                    }

                }

            }
        }

        global $wcallocation_unspecified_sku_for_loggedin_user;
        global $wcallocation_unspecified_user_tier_allocations;
        $tier = get_usermeta( $user_id, 'allocation_tier', "");
        $sku = $product->get_sku();
        $products_sku = $wcallocation_values["products_sku"];
        $sku_index = array_search($sku, $products_sku);
        if($sku_index === false){
            $return = $wcallocation_unspecified_sku_for_loggedin_user - $used_allocations;
            return $return > 0 ? $return : 0;
        }
        $sku_index = 1 + intval($sku_index);
        $allocation_starts = strtotime($wcallocation_values['wcallocation_starts']);
        $allocation_ends = strtotime($wcallocation_values['wcallocation_ends']);
        $now = strtotime(current_time('mysql'));
        if($now >= $allocation_starts && $now <= $allocation_ends){
            $key = 'sku_'.$sku_index."|tier_".$tier."|".$sku;
            if(isset($wcallocation_values[$key])){
                $return = intval($wcallocation_values[$key]) - $used_allocations;
                return $return > 0 ? $return : 0;
            }
            $return = $wcallocation_unspecified_user_tier_allocations - $used_allocations;
            return $return > 0 ? $return : 0;
        }
        else{
            global $wcallocations_out_of_time_range;
            $return = $wcallocations_out_of_time_range - $used_allocations;
            return $return > 0 ? $return : 0;
        }
    }
}
