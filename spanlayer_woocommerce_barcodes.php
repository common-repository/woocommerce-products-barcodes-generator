<?php
/**
 * Plugin Name: WooCommerce Products Barcodes
 * Plugin URI: http://spanlayer.com/woocommerce-barcodes
 * Description: Generate Barcodes for Printing on Labels or any purpose.
 * Version: 1.0.0
 * Author: SpanLayer
 * Author URI: http://spanlayer.com
 * Tested up to: 3.9
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	 add_action('admin_menu', 'sl_wc_barcodes_register_admin_menu');
	
}

function sl_wc_barcodes_register_admin_menu() {
	add_submenu_page('edit.php?post_type=product', 'Generate Barcodes', 'Generate Barcodes', 'manage_options', 'sl_wc_barcodes_select_products', 'sl_wc_barcodes_select_products');
}

function sl_wc_barcodes_select_products() {
	?>
	<h2>Barcodes Generator</h2>
	
	<?php
		if(isset($_POST['sl_wc_barcodes_op']) && $_POST['sl_wc_barcodes_op'] == 'generate') {
			$ids = implode(',', $_POST['products']);
			if(!preg_match('/^[,0-9]+$/', $ids))
				die('Fatal Error !');
			
			$query = new WP_Query(Array('query'=>'id=' . $ids, 'post_type'=>'product'));
			
			if($query->have_posts()) {
			?>
			<script type="text/javascript">
				function popup_for_print() {
					var w = window.open('', "Barcodes", "toolbar=no, location=no, directories=no, status=no, menubar=no, width=600, height=400, scrollbars=yes");
					w.document.body.innerHTML = jQuery("div#barcodes_imgs").html();
				}
			</script>
			
			<input type="button" value="Popup for Printing" onClick="popup_for_print();" />
			<h3>Generated Barcordes</h3>
			<div id="barcodes_imgs">
			<?php
				require_once 'barcode_img_generator.php';
				
				//foreach($_POST['products'] as $product) {
				while ($query->have_posts()) {
					$query->the_post();
					
					$sku = get_post_meta(get_the_ID(), '_sku', true);
					$value = strlen($sku) === 0 ? ($_POST['empty_sku'] == 'id' ? get_the_ID() : get_the_title()) : $sku;
					
					$img = sl_wc_barcodes_img_generate($value, get_the_title(), get_the_ID());
					ob_start();
						imagepng($img);
						imagedestroy($img);			
					$img_output = ob_get_clean();
					$img_base64 = 'data:image/png;base64,' . base64_encode($img_output);
					?><img src="<?php echo $img_base64; ?>" /><br /><?php
				}
			?></div><?php	
			} else {
				?><h3>No products found !</h3><p>Go to <a href="<?php echo admin_url('edit.php?post_type=product'); ?>">Products</a> to create Your first product !.</p><?php
			}
			
			
			
			exit;
		}
	
		$args = array( 'post_type' => 'product', 'posts_per_page' => -1 );
		$posts = get_posts( $args );
		if(is_array($posts) && count($posts) == 0) {
			?><h3>No products found !</h3><p>Go to <a href="<?php echo admin_url('edit.php?post_type=product'); ?>">Products</a> to create Your first product !.</p><?php
		}
	?>
	<h3>Select Products</h3>
	<form method="POST" action="">
		<input type="hidden" name="sl_wc_barcodes_op" value="generate" />
		<input id="select_all" type="checkbox" onChange="if(jQuery(this).is(':checked')) jQuery('input.product_checkbox').attr('checked', 'checked'); else jQuery('input.product_checkbox').removeAttr('checked');" /><label for="select_all">Select All</label>
		<ul style="border: 1px dashed;padding: 4px;height: 250px;overflow-y: scroll;"><?php
		foreach($posts as $post) {
			?>
			<li><input type="checkbox" id="product-<?php echo $post->ID; ?>" class="product_checkbox" name="products[]" value="<?php echo $post->ID; ?>" /><label for="product-<?php echo $post->ID; ?>"><?php echo $post->post_title; ?></label></li>
			<?php
		}
		?></ul>
		<p>For Empty SKU Products, Use:
			<select name="empty_sku">
				<option value="id">ID</option>
				<option value="title">Title</option>
			</select>
		</p>
		<input type="submit" value="Generate" />
	</form>
	<?php
	
	/*$sku = get_post_meta($post->ID, '_sku', true);
		if($sku === "" || strlen($sku) < 8 || strlen($sku) >= 8)
		{
			$sku = str_pad($post->ID, 8, '0', STR_PAD_LEFT);
			update_post_meta($post->ID, '_sku', $sku);
		}
		?>
		<img src="<?php echo site_url('barcode.php?text=' . urlencode($sku) . '&title='. urlencode($post->post_title) . '&price=100'); ?>" /><br />
		<?php */
}

?>