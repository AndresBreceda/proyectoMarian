<?php
/**
 * Shipping Methods Display
 *
 * In 2.1 we show methods per package. This allows for multiple methods per order if so desired.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart-shipping.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.3.0
 */

defined( 'ABSPATH' ) || exit;

$formatted_destination    = isset( $formatted_destination ) ? $formatted_destination : WC()->countries->get_formatted_address( $package['destination'], ', ' );
$has_calculated_shipping  = ! empty( $has_calculated_shipping );
$show_shipping_calculator = ! empty( $show_shipping_calculator );
$calculator_text          = '';
$seguro_envio = number_format(WC()->cart->get_subtotal()*0.05,2,'.',',');

?>
<tr class="woocommerce-shipping-totals shipping">
	<th colspan="2" style="font-weight:600"><?php echo wp_kses_post( $package_name ); ?></th>
</tr>
<tr>
    <td colspan="2" data-title="<?php echo esc_attr( $package_name ); ?>">
        <table>
            <thead>
                <td style="padding-bottom:5px;padding-top:5px;width:15px"></td>
                <td style="padding-bottom:5px;padding-top:5px;text-align:left">Paqueteria</td>
                <td style="padding-bottom:5px;padding-top:5px;text-align:center">Entrega estimada</td>
                <td style="padding-bottom:5px;padding-top:5px;text-align:right">Precio</td>
            </thead>
            <tbody>
                <?php if ( $available_methods ) : ?>
                    <?php  setlocale(LC_MONETARY, 'en_US.UTF-8'); ?>
                    <?php foreach ( $available_methods as $method ) : ?>
                        <tr>
                            <td style="padding-bottom:5px;padding-top:5px">
                             <?php printf( '<input type="radio" name="shipping_method[%1$d]" data-index="%1$d" id="shipping_method_%1$d_%2$s" value="%3$s" class="shipping_method" %4$s />', $index, esc_attr( sanitize_title( $method->id ) ), esc_attr( $method->id ), checked( $method->id, $chosen_method, false ) ); ?> 
                            </td>
                            <?php if($method->get_method_id() == 'wc-enviaya-shipping') :?>
                                <?php 
                                   
                                    $meta_data = $method->get_meta_data();
                                    $delivery_date = new \DateTime($meta_data['estimated_delivery']); 
   
                                ?>
                                <td class="enviaya-carrier-name" style="padding-bottom:5px;padding-top:5px"><?php echo $meta_data['carrier_name']?></td>
                                <td class="enviaya-delivery-date" style="padding-bottom:5px;padding-top:5px"><?php echo strftime('%d-%h-%y',$delivery_date->getTimestamp()) ?></td>
                                <td class="enviaya-shipping-cost" style="padding-bottom:5px;padding-top:5px">$<?php echo number_format($method->get_cost(),2,'.',',')  ?></td>
                            <?php else :?>
                                <td class="enviaya-carrier-name" colspan="2" style="padding-bottom:5px;padding-top:5px"> <?php echo $method->get_label()?></td>
                                <td class="enviaya-shipping-cost" style="padding-bottom:5px;padding-top:5px;padding-left:20px"><?php echo "$".number_format($method->get_cost(),2,'.',',')  ?></td>
                            <?php endif;?>
                            
                        </tr>
                    <?php endforeach; ?>
                <?php endif;?>
            </tbody>
        </table>
    </td>
</tr>
<script>
    jQuery(function() {

        if(jQuery('[name=enviaya_shipping_insurance]').length > 0){
            var insurance_control = jQuery('[name=custom_enviaya_shipping_insurance]:checked').val();

            jQuery('[name=enviaya_shipping_insurance][value='+insurance_control+']').prop('checked',true);

            jQuery('body').trigger('update_checkout');

            jQuery('[name=enviaya_shipping_insurance]').on('change',function(){
                
                var insurance_control_2 =  jQuery('[name=enviaya_shipping_insurance]:checked').val();

                jQuery('[name=custom_enviaya_shipping_insurance][value='+insurance_control_2+']').prop('checked',true);

                jQuery('body').trigger('update_checkout');
            })
        }else{

            jQuery('[name=enviaya_shipping_insurance][value=no]').prop('checked',true);

            jQuery('body').trigger('update_checkout');
        }
        
        
    });
</script>