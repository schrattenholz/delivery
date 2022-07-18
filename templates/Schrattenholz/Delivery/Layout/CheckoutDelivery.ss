<% include PageTitleOverlap %>
    <!-- Page Content-->
	<div class="container pb-5 mb-2 mb-md-4">
	<form id="checkoutDelivery" class="needs-validation" novalidate>
		<div class="row">
			<section id="content" class="col-lg-8">
				<% include Schrattenholz/Order/Includes/CheckoutSteps %>

						<% if $Basket.ProductContainers %>
	<% if $OpenPreSaleProductInBasket %>
		<div class="row">
			<div class="col-12 font-size-sm">
				<% if not $v %>
					$BasketDeliverySetup($ID,$GroupPreise.Sort('SortID','ASC').First.ID).DeliverySetup.ContentProductShippingInfo
				<% else %>
					$BasketDeliverySetup($ID,$v).DeliverySetup.ContentProductShippingInfo
				<% end_if %>
			</div>
		</div>
	<% else %>
						
	<div class="row">
              <div class="col mb-4 card">
                <div class="card-header">
                  <h3 class="accordion-heading">Versandoptionen<span class="accordion-indicator"></span></h3>
                </div>
                <div  id="shipping-estimates" data-parent="#order-options">
                  <div class="card-body">
					<div class="form-group">
						<select id="deliveryType" class="form-control custom-select" required="required" name="DeliveryType"  onload="changeDeliveryType();" onchange="changeDeliveryType();">
							<% loop $getActiveDeliveryTypes %>
								<option value="$Type" data-deliveryTypeID="$ID" <% if $Top.Basket.DeliveryType.Type == $Type %>selected<% end_if %>>$Title</option>
							<% end_loop %>
                        </select>
                        <div class="invalid-feedback">Bitte wählen Sie eine Lieferoption</div>
                      </div>
					  
					  
					  
					  

						
						
						
						
					  <% loop $DeliverySetup %>

                      <div id="DeliveryContainer" class="form-group delivery <% if $Top.Basket.DeliveryType.Type=="delivery" %><% else %>d-none<% end_if %>">
					<% if $getCity($Top.CurrentOrderCustomerGroup.ID,$Top.CheckoutAddress.ZIP,$Top.CheckoutAddress.City) %>
                        <select class="form-control custom-select" name="Delivery" id="Delivery" <% if $getActiveDeliveryTypes.First.Type=="delivery" %><% else %>disabled<% end_if %>>	   

					<option value="" data-city="" data-zip="">Wählen Sie Ihren Ort</option>
						<% loop $getCities($Top.CurrentOrderCustomerGroup.ID).Sort('Title') %>
							<% loop $Top.DeliveryDatesForCity($Top.CurrentOrderCustomerGroup.ID, $Delivery_ZIPCodes.First.Title,$Title).Dates %>
							<% if $First %>
							<% loop $Up.ZIPs %>$Title - $Top.CheckoutAddress.ZIP<% if $Title==$Top.CheckoutAddress.ZIP %> selected<% end_if %><% end_loop %>
							<option <% loop $Up.ZIPs %><% if $Title==$Top.CheckoutAddress.ZIP %> selected<% end_if %><% end_loop %>
							value="$Up.ID"
							data-city="$Up.Title"
							data-zip="<% loop $Up.ZIPs %>$Title<% if $Last %><% else %>,<% end_if %><% end_loop %>" 
							data-deliverydata="<% end_if %><% if $First %><% else %>;<% end_if %>$DayShort, $Short|$Eng|$RouteID|$ArrivalTime<% if $Last %>">
							$Up.Title			
							</option>
							<% end_if %>
							<% end_loop %>
							
						
						<% end_loop %>
							
						
						
                        </select>
						<% else %>
							<p>Leider findet nach $Top.CheckoutAddress.City zur Zeit keine Lieferung statt.</p>
							<p>Sie möchten, dass Ihr Ort in eine unsere Lieferrouten aufgenommen wird?
							Sprechen Sie uns an. Vielleicht k&ouml;nnen wir es einrichten. </p>
						<% end_if %>
						<input type="hidden" id="deliveryDate" name="DeliveryDate" <% if $Top.Basket.ShippingDate %>value="$Top.Basket.ShippingDate"<% end_if %> />
                        <input type="hidden" id="deliveryRoute" name="DeliveryRoute" <% if $Top.Basket.RouteID %>value="$Top.Basket.RouteID"<% end_if %> />
                      </div> 
					 
						<div id="CollectionContainer" class="form-group collection <% if $Top.Basket.DeliveryType.Type="collection" %><% else %>d-none<% end_if %>">
                        <select class="form-control custom-select" name="CollectionDay" <% if $Top.Basket.DeliveryType.Type =="delivery" %> <% else %>required="required" <% end_if %>>
                          <option value="" data-day="" data-timefrom="" data-timeto="">Wählen Sie Ihren Abholtag</option>
                          <% loop $getNextCollectionDays($Top.CurrentOrderCustomerGroup.ID,$ID) %>
						  
						  <option value="$ID" data-day="$Day" data-date="$Date.Eng"
						  data-timefrom="$Time.From" 
						  data-timeto="$Time.To"
						   <% if $Top.Basket.CollectionDayID == $ID %>selected="selected"<% end_if %>>
						  $DayTranslated, $Date.Short
						  </option>
                         <% end_loop %>
                        </select>
						<input type="hidden" id="collectionDate" name="CollectionDate" <% if $Top.CheckoutAddress.CollectionDate %>value="$Top.CheckoutAddress.CollectionDate"<% end_if %> />
                      </div>
<% end_loop %>					  
					  <div class="deliverynotice d-none">Liefertermin</div>
                  </div>
                </div>
              </div>
			  </div>
	 <% end_if %>
			
				<div class="row" id="paymenMethods_Holder">
				<% if not $Top.Basket.DeliveryType %>
					<% include Schrattenholz/Payment/Payment DeliveryTypeID=1 %>
				<% else %>
					<% include Schrattenholz/Payment/Payment DeliveryTypeID=$Top.Basket.DeliveryTypeID %>
				<% end_if %>
		</div>
    <!-- Toast: Delivery-->
    <div class="toast-container toast-bottom-center">
      <div class="toast mb-3" id="delivery-toast" data-delay="10000" role="info" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-success text-white"><i class="czi-check-circle mr-2"></i>
          <h6 class="font-size-sm text-white mb-0 mr-auto">Lieferung m&ouml;glich!</h6>
          <button class="close text-white ml-2 mb-1" type="button" data-dismiss="toast" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
        <div class="toast-body">Gerne bringen wir Ihnen Ihre Bestellung nach Hause. <br/>Wählen Sie hierzu unter den <strong><i>Versandoptionen>Lieferung</i></strong> aus.</div>
      </div>
    </div>
						<% else %>
						  <div class="card">
                <div class="card-header">
                  <h3 class="accordion-heading">Versandoptionen<span class="accordion-indicator"></span></h3>
                </div>
							<p>Es befinden sich momentan keine Produkte in Deinem Warenkorb!</p>
							<a href="$LinkProductRoot">Zur Produkt-&Uuml;bersicht</a>
							</div>
						<% end_if %>

				
					<div class="col-12 messageBox">
								<div class="col-12 pl-3 pr-3 alert  fade" style="display:none;" role="alert">

								</div>
							</div>
				
				  <!-- Navigation (desktop)-->
					<div class="d-none d-lg-flex pt-4 mt-3">
						<div class="w-50 pr-3">
							<a href="$LinkCheckoutAddress" name="action_back" value="Zurück zu den Benutzerdaten" class="action action btn btn-secondary btn-shadow mb-2 mr-1 col-12" id="OrderProfileFeature_RegistrationForm_useraccounttab_action_back">
							<i class="czi-arrow-left mt-sm-0 mr-1"></i><span class="d-none d-sm-inline">Zurück zu den Benutzerdaten</span><span class="d-inline d-sm-none">Zurück</span>
							</a>
						</div>
						<div class="w-50 pl-2">
							<button type="submit" name="action_continue" value="Weiter zur Bestellübersicht" class="action action btn btn-primary btn-shadow mb-2 mr-1 col-12" id="OrderProfileFeature_RegistrationForm_useraccounttab_action_continue">
								<span class="d-none d-sm-inline">Weiter zur Bestellübersicht</span><span class="d-inline d-sm-none">Weiter</span><i class="czi-arrow-right mt-sm-0 ml-1"></i></a>
							</button>
						</div>
					</div>
				
			</section>
<!-- Sidebar-->
        <aside class="col-lg-4 pt-4 pt-lg-0">
          <div class="cz-sidebar-static rounded-lg box-shadow-lg ml-lg-auto">
            <div class="widget mb-3">
                <h2 class="h6 mb-4 text-center"><i class="h6 text-body czi-home"></i>&nbsp;Addresse</h4>
                <ul class="list-unstyled font-size-sm">
                  <li><span class="text-muted">Kontakt:&nbsp;</span>$CheckoutAddress.FirstName $CheckoutAddress.Surname</li>
                  <li><span class="text-muted">Addresse:&nbsp;</span>$CheckoutAddress.Street, $CheckoutAddress.ZIP $CheckoutAddress.City</li>
                  <li><span class="text-muted">Telefon:&nbsp;</span>$CheckoutAddress.PhoneNumber</li>
				  <li><span class="text-muted">E-Mail:&nbsp;</span>$CheckoutAddress.Email</li>
				  <input type="hidden" id="OrderProfileFeature_RegistrationForm_useraccounttab_ZIP" value="$CheckoutAddress.ZIP" />
				  <input type="hidden" id="OrderProfileFeature_RegistrationForm_useraccounttab_City" value="$CheckoutAddress.City" />
                </ul>
            </div>
          </div>
        </aside>

			<!-- Navigation (mobile)-->
				<div class="col-12 d-lg-none">
				  <div class="d-flex pt-4 mt-3">
					<div class="w-50 pr-3"><a class="btn btn-secondary btn-block" href="$LinkCheckoutAddress"><i class="czi-arrow-left mt-sm-0 mr-1"></i><span class="d-none d-sm-inline">Zurück zum Warenkorb</span><span class="d-inline d-sm-none">Zurück</span></a></div>
					<div class="w-50 pl-2"><button type="submit" name="action_continue" value="Weiter zur Bestellübersicht" class="action action btn btn-primary btn-shadow mb-2 mr-1 col-12" id="OrderProfileFeature_RegistrationForm_useraccounttab_action_continue">
								<span class="d-none d-sm-inline">Weiter zur Bestellübersicht</span><span class="d-inline d-sm-none">Weiter</span><i class="czi-arrow-right mt-sm-0 ml-1"></i></a>
							</button></div>
				  </div>
				</div>

		</div>
		</form>
	</div>
	
<script>

function changeDeliveryType(){
	var option=$("#deliveryType").children("option:selected");
	if(option.val()=="delivery"){
		$('#DeliveryContainer').removeClass("d-none");
		$('#CollectionContainer').addClass("d-none");
		$("#delivery_sepa").attr("required","required");
	}else{
		$(".deliverynotice").html("");
		$('#CollectionContainer').removeClass("d-none");
		$('#DeliveryContainer').addClass("d-none");
		$("#delivery_sepa").removeAttr("required");
	}
	var selectedDeliveryType=jQuery('#deliveryType option[value=' + jQuery('#deliveryType').val() + ']').attr("data-deliveryTypeID");
	loadPaymentMethods(selectedDeliveryType,0);
}

function loginMember(){
	console.log("loginMember");
	var pageLink='$Link';
	jQuery.ajax({
	
		url: pageLink+"/loginMember?person="+encodeURIComponent(JSON.stringify(jQuery('#checkoutAddress').serializeObject())),
		success: function(data) {
		var response=JSON.parse(data);
		var status=response.Status;
		var message=response.Message;
		var object=response.Value;
		/*
		JSON
			$returnValues->Status=false;
			$returnValues->Message="Das Passwort muss mindestens 8 Zeiechen haben!";
			$returnValues->Value='object';
		*/
		 
		console.log("loginMember="+status);
			if(status=='error'){
				alert(message);
			}else{
				if(status=='info' || status=='warning'){
					alert(message);
				}	
				window.location=pageLink;
			}
		}
	});

}
function checkoutDelivery(nextLink,pageLink){
	var nextLink='$LinkCheckoutSummary';
	var pageLink='$Link';
	setCollectionDate();
	jQuery('input').each(function(){
		$(this).removeAttr('disabled');
	});
	jQuery.ajax({
	
		url: pageLink+"/setCheckoutDelivery?delivery="+encodeURIComponent(JSON.stringify(jQuery('#checkoutDelivery').serializeObject())),
		success: function(data) {
		var response=JSON.parse(data);
		var status=response.Status;
		var message=response.Message;
		var object=response.Value;
		/*
		JSON
			$returnValues->Status=error/info/warning/good;
			$returnValues->Message="Das Passwort muss mindestens 8 Zeiechen haben!";
			$returnValues->Value='object';
		*/
		 
		console.log("checkoutAddress="+data);
			if(status=='error'){
					$('.messageBox .alert').html(message);
					$('.messageBox .alert').addClass('alert-danger').css('display','block').fadeTo(100,1).delay(3000).fadeTo(100,0,function(){$(this).removeClass('alert-danger');$(this).css('display','none');});
	
			}else{
				if(status=='info' || status=='warning'){
					//alert(message);
				}
				window.location=nextLink;
			}
		}
	});
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
</script>