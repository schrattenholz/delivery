/*vendor\schrattenholz\delivery\javascript\delivery.js*/

function loadShippingOptions(priceBlockElementID,productID){
	jQuery.ajax({
		url: pageLink+"/getShippingOptions?priceBlockElementID="+priceBlockElementID+"&productID="+productID,
		success: function(data) {
		
		/*
		JSON
			$returnValues->Status=false;
			$returnValues->Message="Das Passwort muss mindestens 8 Zeiechen haben!";
			$returnValues->Value='object';
		*/
					$('#shippingOptions').html(data);

		}
	});
}
jQuery( document ).ready(function() {
	if(jQuery('#checkoutDelivery').length>0){
		jQuery('#checkoutDelivery').submit(function (event) {
				event.preventDefault();
			if (jQuery('#checkoutDelivery')[0].checkValidity() === false) {
				event.stopPropagation();
			} else {
			checkoutDelivery()
			}
			jQuery('#checkoutDelivery').addClass('was-validated');
		});
	}
	if($('#deliveryType').length>0){
		$('#OrderProfileFeature_RegistrationForm_useraccounttab_City').on('focusout',function(){
			console.log("suche City"+searchCity($('#OrderProfileFeature_RegistrationForm_useraccounttab_City').val()));
			if(searchCity($('#OrderProfileFeature_RegistrationForm_useraccounttab_City').val())){
				console.log("City gefunden");
				$('#delivery-toast').toast('show');
			}
		});
		$('#deliveryType').on("change",function(){
			console.log("deliverytype="+$(this).val());
			if($(this).val()=="shipping"){
				$('.shipping').removeClass('d-none').addClass('d-block');
				$('.delivery').addClass('d-none').removeClass('d-block');
				$('.collection').addClass('d-none').removeClass('d-block');
				$('.delivery .custom-select').removeAttr('required');
				$('.collection  .custom-select').removeAttr('required','required');
			}else if($(this).val()=="delivery"){
				$('.collection  .custom-select').removeAttr('required','required');
				$('#OrderProfileFeature_RegistrationForm_useraccounttab_City').on('focusout',function(){
					searchCity($('#OrderProfileFeature_RegistrationForm_useraccounttab_City').val());
				});
				$('#OrderProfileFeature_RegistrationForm_useraccounttab_ZIP').on('focusout',function(){
					if($('#OrderProfileFeature_RegistrationForm_useraccounttab_City').val()){
						if(searchCity($('#OrderProfileFeature_RegistrationForm_useraccounttab_City').val())){
							$('#delivery-toast').toast('show');
						}
					}else{
						if(searchZIP($('#OrderProfileFeature_RegistrationForm_useraccounttab_ZIP').val())){
							$('#delivery-toast').toast('show');
						}
					}
				});
				searchZIP($('#OrderProfileFeature_RegistrationForm_useraccounttab_ZIP').val());
				searchCity($('#OrderProfileFeature_RegistrationForm_useraccounttab_City').val());
				$('.delivery').removeClass('d-none').addClass('d-block');
				$('.delivery  .custom-select').attr('required','required');
				$('.shipping').addClass('d-none').removeClass('d-block');
				$('.collection').addClass('d-none').removeClass('d-block');
			}else{
				setCollectionDate();
				$('.delivery  .custom-select').removeAttr('required');
				$('.collection').removeClass('d-none').addClass('d-block');
				$('.collection  .custom-select').attr('required','required');
				$('.shipping').addClass('d-none').removeClass('d-block');
				$('.delivery').addClass('d-none').removeClass('d-block');
			}
		})
		if($('.delivery .custom-select').length>0){
			$('.delivery .custom-select').on('change',function(){
				console.log("deliveryday");
				setDeliveryDate();
			});
		}
		if($('.collection .custom-select').length>0){
			$('.collection .custom-select').on('change',function(){
				console.log("collectionday");
				setCollectionDate();
			});
		}
	}
});
function searchCity(city){
	var found=false;
	$('.delivery .custom-select option').each(function(){
		
		if($(this).attr('data-city').localeCompare(city)){
			//$('.delivery .custom-select').val($(this).val());
			if($("#deliveryType").val()=="delivery"){
				//setDeliveryDate();
			}
			return found = true;
		}
	});
	return found;
}
function searchZIP(zip){
	var found=false;
	$('.delivery .custom-select option').each(function(){
		var zips=$(this).attr('data-zip');
		var zipsAr=zips.split(',');
		//console.log("zip = "+zipsAr.length);
		for (var c=0;c<zipsAr.length;c++){
			if(zipsAr[c]==zip && $(this).attr('data-city')==$('#OrderProfileFeature_RegistrationForm_useraccounttab_City').val()){
				console.log(zip +" = "+zipsAr[c]+" val="+$(this).val());
				$('.delivery .custom-select').val($(this).val());
				setDeliveryDate()
				return found = true;
			}
		}
	});
	return found;
}
function setDeliveryDate(){
	var select=$('.delivery .custom-select');
	var option=$('.delivery .custom-select').children("option:selected");
	//console.log("ddate="+option.attr('data-deliverydate'));
	if(select.val()){
		var deliverynotice="<h6 class='mt-2'>Liefertermin:</h6>"+option.attr('data-deliverydate');
		if(option.attr('data-arrivaltime')!="00:00 Uhr"){
			deliverynotice+=" um ca."+option.attr('data-arrivaltime');	
		}
		$('.deliverynotice').html(deliverynotice);
		$('#deliveryDate').val(option.attr('data-deliverydate'));
		$('#deliveryRoute').val(option.attr('data-deliveryroute'));
		$('.deliverynotice').removeClass("d-none");
	}else{
		$('.deliverynotice').addClass("d-none");
	}
}
function setCollectionDate(){
	var select=$('.collection .custom-select');
	var option=$('.collection .custom-select').children("option:selected");
	if(select.val()){
		$('.deliverynotice').html("<h6 class='mt-2'>Abholzeit:</h6>"+option.attr('data-timefrom')+" Uhr bis "+option.attr('data-timeto')+" Uhr");
		$('.deliverynotice').removeClass("d-none");
		$('#collectionDate').val(option.attr('data-date'));
	}else{
		$('.deliverynotice').addClass("d-none");
	}
}
$.fn.serializeObject = function()
{
    var o = {};
    var a = this.serializeArray();
    $.each(a, function() {
	console.log("="+this.value);
        if (o[this.name] !== undefined) {
            if (!o[this.name].push) {
                o[this.name] = [o[this.name]];
            }
            o[this.name].push(this.value || '');
        } else {
            o[this.name] = this.value || '';
        }
    });
    return o;
};
