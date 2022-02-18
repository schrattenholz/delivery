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
function loadZips(zipFragment){
	jQuery.ajax({
		url: pageLink+"/getZipsAjax?zipFragment="+zipFragment,
		success: function(data) {
		var zips=JSON.parse(data);
		var zipAr=Array();
		for(var c=0;c<zips.length;c++){
			var tmp=[];
			tmp['id']=zips[c]['id'];
			tmp['value']=zips[c]['title'];
			zipAr.push(tmp);
		}
		//console.log(zipAr.length);
		/*
		JSON
			$returnValues->Status=false;
			$returnValues->Message="Das Passwort muss mindestens 8 Zeiechen haben!";
			$returnValues->Value='object';
		*/
		if($('#OrderProfileFeature_RegistrationForm_useraccounttab_ZIP').autocomplete("instance")){
			$('#OrderProfileFeature_RegistrationForm_useraccounttab_ZIP').autocomplete("option","source",zipAr);
		}else{
			$('#OrderProfileFeature_RegistrationForm_useraccounttab_ZIP').autocomplete(
				{
				source:zipAr,
				minLength:3,
				select: function (event, ui) {
						//event.preventDefault();
						loadCitiesByZIPID(ui.item.id);
						//return false;
					},				
				close: function (event, ui) {
						//event.preventDefault();
						alert("close");
						loadCitiesByZIPID(ui.item.id);
						//return false;
					}
				}
			);
			jQuery.ui.autocomplete.prototype._resizeMenu = function () {
			  var ul = this.menu.element;
			  ul.outerWidth(this.element.outerWidth());
			}
		}
				//$('#OrderProfileFeature_RegistrationForm_useraccounttab_ZIP').autocomplete("option","source", zipAr);
}
	})
}
function loadCitiesByZIPID(zipID){
	jQuery.ajax({
		url: pageLink+"/getCitiesByZIPAjax?zipID="+zipID,
		success: function(data) {
			var zips=JSON.parse(data);
			var zipAr=Array();
			$('#OrderProfileFeature_RegistrationForm_useraccounttab_City').html("");
			if(zips.length>1){
				$('#OrderProfileFeature_RegistrationForm_useraccounttab_City')
					.append($("<option></option>")
						.text("Bitte wählen")); 
			}
			$.each(zips, function(key, value) {   
				$('#OrderProfileFeature_RegistrationForm_useraccounttab_City')
					.append($("<option></option>")
						.attr("value", value.title)
						.text(value.title)); 
				});

			}
	});
}
function loadCitiesByZIP(zip,ref){
	jQuery.ajax({
		url: pageLink+"/getCitiesByZIPAjax?zip="+zip,
		success: function(data) {
			var zips=JSON.parse(data);
			var zipAr=Array();
			
			var selectedCity=false;
			if(ref.val().length>2){
				selectedCity=ref.val();
			}
			if(zips.length>1){
				ref
					.append($("<option value=''></option>")
						.text("Bitte wählen")); 
			}
			ref.html("");
			if(zips.length>0){
				$.each(zips, function(key, value) { 
					if(selectedCity==value.title){
						ref
							.append($("<option selected='selected'></option>")
								.attr("value", value.title)
								.text(value.title)); 
					}else{
						ref
							.append($("<option></option>")
								.attr("value", value.title)
								.text(value.title)); 
					}
				});
			}else{
				ref
					.append($("<option value=''></option>")
						.text("Bitte PLZ eingeben")); 
			}
		}
	});
}
jQuery( document ).ready(function() {
	if(jQuery('#signup-tab').length>0){
		
		var loadZipFlag=true;
		var refCityFieldSignUp=$('#signup-tab #City');
		var refZIPFieldSignUp=$('#signup-tab #ZIP');
		if(refCityFieldSignUp.val()!=""){
			
			loadCitiesByZIP(refZIPFieldSignUp.val(),refCityFieldSignUp);
			
		}
		
		refZIPFieldSignUp.on('keyup focusout' ,function(){
			if($(this).val().length>2){
				
					//loadZips($(this).val());
				if($(this).val().length>=4){
					loadCitiesByZIP($(this).val(),refCityFieldSignUp);
				}
			}
		});
	}
	if(jQuery('#checkoutAddress').length>0){
		var loadZipFlag=true;
		var refCityField=$('#OrderProfileFeature_RegistrationForm_useraccounttab_City');
		var refZIPField=$('#OrderProfileFeature_RegistrationForm_useraccounttab_ZIP');
		if(refCityField.val()!=""){
			
			loadCitiesByZIP(refZIPField.val(),refCityField);
			
		}
		refZIPField.on('keyup focusout' ,function(){
			
			if($(this).val().length>2){
				
					//loadZips($(this).val());
				if($(this).val().length>=4){
					loadCitiesByZIP($(this).val(),refCityField);
				}
			}
		});
	}
	
	
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
				setDelivery();
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
				//setDelivery();
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
				setDelivery()
				return found = true;
			}
		}
	});
	return found;
}
function initDeliveryDateListener(){
	$('.deliveryDates input').each(function(){
		$(this).on("change", function(){
			alert($(this).val());
			setDeliveryDate($(this).val());
		});
	});
}
function setDeliveryDate(date){
	$('#deliveryDate').val(date);
}
function setDelivery(){
	var select=$('.delivery .custom-select');
	var option=$('.delivery .custom-select').children("option:selected");
	//console.log("ddate="+option.attr('data-deliverydate'));
	var deliveryDates=option.attr('data-deliverydate').split(';');
	if(select.val()){
		var deliverynotice;
		if(deliveryDates.length>1){
			deliverynotice="<h6 class='mt-2'>Mögliche Liefertermine:</h6>";			
		}else{
			deliverynotice="<h6 class='mt-2'>Liefertermin:</h6>";
		}
		deliveryDates.forEach(function(item){
		var date=item.split(':');
		 // eliverynotice+='<div class="custom-control custom-radio">'+date[0]+'<input type="radio" class="custom-control-input" name="deliveryDates" value="'+date[1]+'" required="required"><label for="'+date[1]+'" class="custom-control-label"></label></div>';	
		deliverynotice+='<div class="custom-control custom-radio deliveryDates">'
		deliverynotice+='<input class="custom-control-input" type="radio" id="'+date[1]+'" name="deliveryDates" value="'+date[1]+'">'
		deliverynotice+=' <label class="custom-control-label" for="'+date[1]+'">'+date[0]+'</label>'
		deliverynotice+='</div>'		
		});
		if(option.attr('data-arrivaltime')!="00:00 Uhr"){
			deliverynotice+="<p>Lieferung um ca."+option.attr('data-arrivaltime')+"</p>";	
		}
		$('.deliverynotice').html(deliverynotice);
		initDeliveryDateListener();
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
