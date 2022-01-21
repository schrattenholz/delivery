
              <div class="card">
                <div class="card-header">
                  <h3 class="accordion-heading">Versandoptionen<span class="accordion-indicator"></span></h3>
                </div>
                <div  id="shipping-estimates" data-parent="#order-options">
                  <div class="card-body">
					<div class="form-group">
                        <select id="deliveryType" class="form-control custom-select" required="required" name="DeliveryType">
							<% loop $DeliveryTypes %>
								<option value="$Type">$Title</option>
							<% end_loop %>
                        </select>
                        <div class="invalid-feedback">Bitte wählen Sie ein Lieferoption</div>
                      </div>

                      <div class="form-group delivery d-none">
					  <% loop $Top.Basket.DeliverySpecialProduct.Routes %>
					  <% loop $Up.DeliveryDays.Filter('RouteID',$ID) %>
						<p>$Route.getNextDeliveryDate($Top.CurrentOrderCustomerGroup.ID) - $Title</p>
					  <% end_loop %>
					 
					 <% end_loop %>
                        <select class="form-control custom-select" name="Delivery">
                   <% if $Top.Basket.DeliverySpecial %>
						
                          <option value="" data-city="" data-zip="">ds Wählen Sie Ihren Ort</option>
                          <% loop $Cities.Sort("Title") %>
						  <% if $Routes %>

							<option value="$ID" data-city="$Title" 
						  data-routes="<% loop $Routes %>$Title<% if $Last %><% else %>,<% end_if %><% end_loop %>" 
						  data-deliverydate="<% loop $Routes %>$getNextDeliveryDate($Top.CurrentOrderCustomerGroup.ID)<% if $Last %><% else %>,<% end_if %><% end_loop %>" 
						  data-deliveryroute="<% loop $Routes %>$ID<% if $Last %><% else %>,<% end_if %><% end_loop %>"
						  data-arrivaltime="<% loop $Routes %>$ArrivalTime.Format('HH:mm')<% if $Last %><% else %>,<% end_if %><% end_loop %>" 
						  data-zip="<% loop $ZIPCodes %>$Title<% if $Last %><% else %>,<% end_if %><% end_loop %>"
						  >
						  $Title
						  </option>

						  
						  <% end_if %>
                       
						<% end_loop %>
						<% else %>
						  <option value="" data-city="" data-zip="">Wählen Sie Ihren Ort</option>
                          <% loop $Cities.Sort("Title") %>
						  <% if $Routes %>
						  <option value="$ID" data-city="$Title" 
						  data-routes="<% loop $Routes %>$Title<% if $Last %><% else %>,<% end_if %><% end_loop %>" 
						  data-deliverydate="<% loop $Routes %>$getNextDeliveryDate($Top.CurrentOrderCustomerGroup.ID)<% if $Last %><% else %>,<% end_if %><% end_loop %>" 
						  data-deliveryroute="<% loop $Routes %>$ID<% if $Last %><% else %>,<% end_if %><% end_loop %>"
						  data-arrivaltime="<% loop $Routes %>$ArrivalTime.Format('HH:mm')<% if $Last %><% else %>,<% end_if %><% end_loop %>" 
						  data-zip="<% loop $ZIPCodes %>$Title<% if $Last %><% else %>,<% end_if %><% end_loop %>"
						  >
						  $Title
						  </option>
						  <% end_if %>
                         <% end_loop %>
						<% end_if %>
                        </select>
						<input type="hidden" id="deliveryDate" name="DeliveryDate" />
                        <input type="hidden" id="deliveryRoute" name="DeliveryRoute" />
                      </div> 
					 
						<div class="form-group collection">
						 Kundengruppe=$Top.CurrentOrderCustomerGroup.Title
                        <select class="form-control custom-select" name="CollectionDay" required="required">
                          <option value="" data-day="" data-timefrom="" data-timeto="">Wählen Sie Ihren Abholtag</option>
                          <% loop $CollectionDays.Sort('SortOrder') %>
						  <option value="$ID" data-day="$Day" data-date="$getNextCollectionDate($Top.CurrentOrderCustomerGroup.ID).Eng"
						  data-timefrom="$TimeFrom.Format('HH:mm')" 
						  data-timeto="$TimeTo.Format('HH:mm')"
						  >
						  $DayTranslated, $getNextCollectionDate($Top.CurrentOrderCustomerGroup.ID).Short
						  </option>
						  
                         <% end_loop %>
                        </select>
						<input type="hidden" id="collectionDate" name="CollectionDate" />
                      </div>	
					  <div class="deliverynotice d-none">Liefertermin</div>
                  </div>
                </div>
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