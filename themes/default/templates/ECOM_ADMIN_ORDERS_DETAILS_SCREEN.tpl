{TITLE}

<p>{TEXT*}</p>

<div class="wide_table_wrap">
	<table summary="{!MAP_TABLE}" class="solidborder wide_table">
		{+START,IF,{$NOT,{$MOBILE}}}
			<colgroup>
				<col style="width: 167px" />
				<col style="width: 100%" />
			</colgroup>
		{+END}

		<tbody>
			<tr>
				<th>{!ORDER_NUMBER}</th>
				<td>
					#{ORDER_NUMBER*}
				</td>
			</tr>

			<tr>
				<th>{!ORDERED_BY}</th>
				<td>
					{+START,IF_NON_EMPTY,{$USERNAME,{ORDERED_BY_MEMBER_ID}}}
						<a href="{$MEMBER_PROFILE_LINK*,{ORDERED_BY_MEMBER_ID}}">{ORDERED_BY_USERNAME*}</a>
					{+END}
					{+START,IF_EMPTY,{$USERNAME,{ORDERED_BY_MEMBER_ID}}}
						{ORDERED_BY_USERNAME*}
					{+END}
				</td>
			</tr>

			<tr>
				<th >{!ORDER_PLACED_ON}</th>
				<td>
					{ADD_DATE*}
				</td>
			</tr>

		
			<tr>
				<th>{!TOTAL_PRICE}</th>
				<td>
					{$CURRENCY_SYMBOL}{TOTAL_PRICE*}
				</td>
			</tr>
			<tr>
				<th>{!NOTES}</th>
				<td>
					{NOTES*}
				</td>
			</tr>
			<tr>
				<th>{!ORDER_STATUS}</th>
				<td>
					{ORDER_STATUS}
				</td>
			</tr>
			<tr>
				<th>{!RECEIVER_DETAILS}</th>
				<td>
					{SHIPPING_ADDRESS}
				</td>
			</tr>		
			<tr>
				<th>{!ORDER_ACTIONS}</th>
				<td>
					{ORDER_ACTIONS}
				</td>
			</tr>
		</tbody>
	</table>

	<br />
	{RESULT_TABLE}

	{+START,IF_NON_EMPTY,{RESULTS_BROWSER}}
		<div class="float_surrounder results_browser_spacing">
			{RESULTS_BROWSER}
		</div>
	{+END}
</div>
