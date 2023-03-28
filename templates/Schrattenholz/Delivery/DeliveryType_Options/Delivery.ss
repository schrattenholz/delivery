<% loop $DeliverySetup %>
	<% if $Top.getActiveDeliveryTypes.Filter("Type","delivery").Count>0%>
     <div id="DeliveryContainer" class="form-group delivery">
		<% if $getCity($Top.CurrentOrderCustomerGroup.ID,$Top.CheckoutAddress.ZIP,$Top.CheckoutAddress.City) %>
          <select class="form-control custom-select" name="Delivery" id="Delivery" <% if $getActiveDeliveryTypes.First.Type=="delivery" %><% else %>disabled<% end_if %>>	   
			<% loop $getCities($Top.CurrentOrderCustomerGroup.ID).Sort('Title') %>
				<% loop $Top.DeliveryDatesForCity($Top.CurrentOrderCustomerGroup.ID, $Delivery_ZIPCodes.First.Title,$Title).Dates %>
				
					<% if $First %>

							<option <% loop $Up.ZIPs %><% if $Up.Title == $Top.CheckoutAddress.City %><% if $Title==$Top.CheckoutAddress.ZIP %> selected<% end_if %><% end_if %><% end_loop %>
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
	<% end_if %>
<% end_loop %>