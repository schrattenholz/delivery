<% if not $OpenPreSaleProductInBasket %>
	<% loop $DeliverySetup %>
	<div id="CollectionContainer" class="form-group collection">
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
<% end_if %>