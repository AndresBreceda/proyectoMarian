jQuery(document).ready(function(){

    
  
    jQuery('.wc-enviaya-shipping-create-shipment').on('click',function(e){
        e.preventDefault();

        var data = {
            action: 'create_shipment',
            order_id: jQuery('#post_ID').val(),
            shipping_rate:jQuery('select[name=shipping_rate]').val(),
        };

        //jQuery('#order_status').val('wc-completed').trigger("change")
        //jQuery('#post').submit();
        
        jQuery('#ey-loading-metabox').show();

        jQuery.ajax( {
            method: 'POST',
            url: enviaya_ajax.ajaxurl,
            data: data,
        }).always(function() {
            window.location=document.location.href;
        });
        
       

        //jQuery('form#post').append('<input type="hidden" name="create_shipment" value="1">');
        //jQuery('form#post').submit();
    })

    jQuery('.wc-enviaya-shipping-tab-menu > li').on('click',function(){
        
        var tab = jQuery(this).data('tab');
        
        jQuery('.wc-enviaya-shipping-tab-menu > li').removeClass('active');
        jQuery(this).addClass('active')

        jQuery('.wc-enviaya-shipping-tab-content').hide();
        jQuery('#wc-enviaya-shipping-'+tab+'-tab-content').show();
     
    })

    jQuery('[name=woocommerce_wc-enviaya-shipping_production_api_key]').on('blur',function(e){
      
        var $parent = jQuery(this).closest('#wc-enviaya-shipping-access-tab-content').length > 0 ? jQuery(this).closest('#wc-enviaya-shipping-access-tab-content') : jQuery(this).closest('#TB_ajaxContent')
        getBillingAccounts(e,$parent)
    })
        
    
    //jQuery('[name=woocommerce_wc-enviaya-shipping_sender_address]').selectWoo('destroy');

    jQuery('[name=woocommerce_wc-enviaya-shipping_sender_address]').selectWoo({
          
        language: {
            noResults: function() {
                return i18n.select2_no_results;       
            },
            searching: function() { 
                return i18n.select2_searching;
            }
        },
        ajax: {
            
          url: enviaya_ajax.ajaxurl,
          dataType: 'json',
          delay: 250,
          type: "POST",
          data: function (params) {
            return {
              full_name: params.term,
              action: 'get_origin_address',
              api_key: (jQuery('#TB_window').length == 0) ? jQuery('.wc-enviaya-shipping-tab-content [name=woocommerce_wc-enviaya-shipping_production_api_key]').val() : jQuery('#TB_window [name=woocommerce_wc-enviaya-shipping_production_api_key]').val()
            };
          },
          processResults: function (data) {
            
            
            data.data.forEach(function(currentValue, index, arr){
               
                data.data[index]['id'] = JSON.stringify(currentValue)
            });
      
            return {
              results: data.data
            };
          },
        },
        escapeMarkup: function (markup) { return markup; },
        templateResult: function (direction){
       
        if(typeof direction.full_name == 'undefined'){
            var markup = direction.text;
        }else{
            var markup = [
                '<div class="select2-result-direction clearfix">',
                    '<div class="ey-select2-result-direction__meta">',
                    '<div class="ey-select2-result-full_name"><strong>'+direction.full_name+'</strong></div>',
                    '<div class="ey-select2-result-direction_1">'+direction.direction_1+'</div>',
                    '<div class="ey-select2-result-cp">CP. '+direction.postal_code+'</div>',
                    '<div class="ey-select2-result-city">'+direction.city+', '+direction.country_code+'</div>',
                    '</div>',
                '</div>'
        
            ].join('');
        }
         

         return markup;
       },

       templateSelection: function (direction){
        if(typeof direction.full_name == 'undefined'){
            return direction.text;
        }else{
            return direction.full_name+' ( '+direction.postal_code+' )';
        }
       
       },
       
    });

    jQuery('[name=woocommerce_wc-enviaya-shipping_sender_address]').one('select2:open', function (e) {
        jQuery(document).find('.select2-search__field[aria-owns=select2-woocommerce_wc-enviaya-shipping_sender_address-results]').prop('placeholder', i18n.search_for_direction);
    });

    jQuery('[name=woocommerce_wc-enviaya-shipping_sender_address]').on('select2:select', function (e) {
        var data = e.params.data;
        changeSelectedAddress(data)
    });
    
    var origin_direction = jQuery('[name=woocommerce_wc-enviaya-shipping_sender_address]').val();
   
    if(typeof origin_direction != 'undefined' && origin_direction != ""){
        changeSelectedAddress(JSON.parse(origin_direction))
    }

   
    jQuery('#get_billing_accounts').on('click',getBillingAccounts)
    
    //jQuery('#woocommerce_wc-enviaya-shipping_test_mode').on('change',getBillingAccounts)

    jQuery('#woocommerce_wc-enviaya-shipping_display_carrier_logo, #woocommerce_wc-enviaya-shipping_display_carrier_name, #woocommerce_wc-enviaya-shipping_display_service_name, #woocommerce_wc-enviaya-shipping_display_delivery_time ').on('select2:select', function (e) {
        buildLabelPreview();
    });

    jQuery('.wizard-setup-next').on('click',nextWizardStep);
    jQuery('.wizard-setup-back').on('click',backWizardStep);
    jQuery('.wizard-setup-finish').on('click',finishWizardStep);

    if(jQuery('.wc-enviaya-shipping-tab-content [name=woocommerce_wc-enviaya-shipping_production_api_key]').val() == ""){
  
        jQuery('#thickbox').click();
  
    }
   
    buildLabelPreview()



})

var finishWizardStep = function(){

    jQuery.each( jQuery('#TB_ajaxContent').find('input'), function( i, elm ) {
        

            inputName = jQuery(elm).attr('name');
            inputVal = jQuery(elm).val();
            jQuery('form#mainform').find('input[name='+inputName+']').val(inputVal)
        
     });

     jQuery.each( jQuery('#TB_ajaxContent').find('select'), function( i, elm ) {
        

            selectName = jQuery(elm).attr('name');
            selectVal = JSON.parse(jQuery(elm).val());

      
            
            if(selectName == 'woocommerce_wc-enviaya-shipping_account'){
                var newOption = new Option(`${selectVal.alias} ( ${selectVal.account} )`, JSON.stringify(selectVal) , false, false);
                jQuery('.wc-enviaya-shipping-tab-content [name=woocommerce_wc-enviaya-shipping_account]').append(newOption).trigger('change')
            }
            
            if(selectName == 'woocommerce_wc-enviaya-shipping_sender_address'){
                var newOption = new Option(selectVal.full_name, JSON.stringify(selectVal) , false, false);
                jQuery('.wc-enviaya-shipping-tab-content [name=woocommerce_wc-enviaya-shipping_sender_address]').append(newOption).trigger('change')
            }
            
            jQuery('form#mainform button[name=save]').trigger('click');

     });


}

var backWizardStep = function(){

    var currentStepId = jQuery('.wizard-setup-step:visible').attr('id');
    var currentStep = parseInt(currentStepId.split('wizard-setup-step-')[1])
    var prevStep = currentStep - 1;

    jQuery('#'+currentStepId).hide();
    jQuery('#wizard-setup-step-'+prevStep).show();

}

var nextWizardStep = function(){

    var currentStepId = jQuery('.wizard-setup-step:visible').attr('id');
    var currentStep = parseInt(currentStepId.split('wizard-setup-step-')[1])
    var nextStep = currentStep + 1;

    valid = true;
    
    jQuery.each( jQuery('#'+currentStepId).find('input.required'), function( i, elm ) {
       if( !jQuery(elm).val() ){
            valid = false;
       }
    });

    if(valid){

        jQuery('#'+currentStepId).hide();
        jQuery('#wizard-setup-step-'+nextStep).show();

    }


}

var changeSelectedAddress = function(address) {
  
    if(address != "" &&  address != null){

        var conctactBlock = document.getElementById('woocommerce_wc-enviaya-shipping_contact_information');
        var contactKeys = ['title', 'phone', 'email'];
        var contactInfo = '';
        
        if(address['company'] != null){
            address['title'] = address['full_name']+ " ( "+address['company']+" )"
            
        }else{
            address['title'] =  address['full_name']
        }
        
        if (address) {
            contactKeys.forEach(function (key) {
                if (address[key]) {
                    
                    contactInfo += address[key]+"\n";
                }
            });
        }
        
        conctactBlock.innerHTML = contactInfo;
    
        var shippingBlock = document.getElementById('woocommerce_wc-enviaya-shipping_shipping_address');
        var shippingKeys = [ 'direction_1', 'direction_2', 'district', 'postal_code', 'city', 'state_code', 'country_code']
        var shippingInfo = '';

        if (address) {
            shippingKeys.forEach(function (key) {
                if (address[key]) {
                    
                    if(key != 'postal_code'){
                        shippingInfo += address[key];
                    }
                    if (key == 'postal_code') {
                        shippingInfo += 'CP. '+address[key]+"\n";
                    } else if (key == 'city') {
                        shippingInfo += ', ';
                    } else if (key == 'state_code') {
                        shippingInfo += ', ';
                    } else {
                        shippingInfo += "\n";;
                    }
                }
            });
        }

        shippingBlock.innerHTML = shippingInfo;
        
        jQuery('#woocommerce_wc-enviaya-shipping_origin_direction').val(JSON.stringify(address))
        
        
    }
    

}

var getBillingAccounts = function(e,$parent){
    
  
    var live_api_key = jQuery($parent).find('[name=woocommerce_wc-enviaya-shipping_production_api_key]').val();
    var test_api_key = jQuery($parent).find('[name=woocommerce_wc-enviaya-shipping_test_api_key').val();
    var test_mode = jQuery($parent).find('[name=woocommerce_wc-enviaya-shipping_test_mode]').is(':checked');
    
    if( test_mode  ){
        var api_key = test_api_key
    }else{
        var api_key = live_api_key
    }
    
    if (api_key != "" ) {

        var data = {
            action: 'get_billing_accounts',
            api_key: api_key,
        };
        
        //Before ajax
        jQuery($parent).find('[name=woocommerce_wc-enviaya-shipping_account]').siblings('.notice-error').remove();

        jQuery($parent).find('#get_billing_accounts').find('.dashicons-update').addClass('spin')

        jQuery($parent).find('#enviaya_admin_notices_container .notice').find('.notice-body').text('')
        jQuery($parent).find('#enviaya_admin_notices_container .notice').hide();

        jQuery.ajax( {
            method: 'POST',
            url: enviaya_ajax.ajaxurl,
            data: data,
            success: function(res){
                
                jQuery($parent).find('[name=woocommerce_wc-enviaya-shipping_account]').find('option').remove();
                jQuery($parent).find('[name=woocommerce_wc-enviaya-shipping_account]').val(null).trigger('change');

                if(typeof res.data != 'undefined'){

                    res.data.forEach(function(value, index, arr){
                        
                        var selected =  (enviaya_ajax.settings.account_id == value.id) ? true : false;
                        var newOption = new Option(`${value.alias} ( ${value.account} )`, JSON.stringify(value) , selected, selected);
                        jQuery($parent).find('[name=woocommerce_wc-enviaya-shipping_account]').append(newOption);
    
    
                    })

                   
                }
                
            },
            error: function(res){
                
                jQuery($parent).find('[name=woocommerce_wc-enviaya-shipping_account] > option').remove();
                jQuery($parent).find('[name=woocommerce_wc-enviaya-shipping_account]').val(null).trigger('change');
                jQuery($parent).find('[name=woocommerce_wc-enviaya-shipping_account]').siblings('p.description').after('<div class="notice notice-error inline input-error" ><span class="notice-body">'+res.responseJSON.data+'</span></div>')
       
                
            },

        }).always(function() {
            jQuery($parent).find('#get_billing_accounts').find('.dashicons-update').removeClass('spin')
        });

    }

    

}

var buildLabelPreview = function(){
    
    var display_carrier_logo = jQuery('#woocommerce_wc-enviaya-shipping_display_carrier_logo').val()
    var display_carrier_name = jQuery('#woocommerce_wc-enviaya-shipping_display_carrier_name').val()
    var display_service_name = jQuery('#woocommerce_wc-enviaya-shipping_display_service_name').val()
    var display_delivery_time = jQuery('#woocommerce_wc-enviaya-shipping_display_delivery_time').val()
   
    var key_carrier =  Math.floor(Math.floor(Math.random() * (2 - 0 + 1)) + 0)
    var key_option = Math.floor(Math.floor(Math.random() * (1 - 0 + 1)) + 0)

    var label = ''

    if( display_service_name != 'no_service_name'  && typeof(display_service_name) != 'undefined'){

        label = enviaya_ajax.previewData[key_carrier][display_service_name][key_option]

    }

    var label_preffix = ''

    if( display_carrier_logo == 'yes' ){
        label_preffix= '<img src="'+enviaya_ajax.previewData[key_carrier].carrier_logo_url+'" style="height:15px; margin-right:5px" />'
    }


    if( display_carrier_name == 'yes' ){
        label_preffix+= enviaya_ajax.previewData[key_carrier].carrier_name
    }

    if ( label_preffix != '' ) {
        label= label_preffix+' '+label
    }

    var label_suffix = ''
    
    if(  display_delivery_time == 'delivery_date' ){
        label_suffix = enviaya_ajax.previewData[key_carrier]['delivery_date'][key_option]
    }

    if(  display_delivery_time == 'delivery_days' ){
        label_suffix = enviaya_ajax.previewData[key_carrier]['delivery_days'][key_option]
    }

    if ( label_suffix != '' ) {
        label+= ' ( ' + label_suffix + ' )'
    }
    
    jQuery('#woocommerce_wc-enviaya-shipping_display_preview + .description').html(label)

}

