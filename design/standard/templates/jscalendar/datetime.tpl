{def $time_dropdown = ezini( 'Calendar', 'TimeDropDown', 'calendar.ini' )
     $time = ezini( 'Calendar', 'TimeDropDownStart', 'calendar.ini' )
     $time_stop = ezini( 'Calendar', 'TimeDropDownStop', 'calendar.ini' )
     $time_step = ezini( 'Calendar', 'TimeDropDownStep', 'calendar.ini' )}

{if is_set( $field_name )|not}
	{def $field_name = 'date'}
{/if}
{if is_set( $onclose )|not}
	{def $onclose = ""}
{/if}
{if is_set( $instance )|not}
	{def $instance = ''}
{/if}

{* date default values *}
{def $default_value_display = '- select -'}
{if or( is_set( $default_value )|not, $default_value|eq(0) )}
	{def $default_value = ''}
{else}
	{set $default_value_display = $default_value|datetime('short_date')}
{/if}

<script type="text/javascript">
{literal}
$(function()
{
	$('#datepicker').datepicker({
		inline     : false,
		altField   : '#datewriter',
		altFormat  : '@',
		onClose    : jquery_calendar_close,
		dateFormat : 'mm-dd-yy'
	});
});

function update_result_field()
{
	// jquery date details
	var date_milsecs = parseInt( document.getElementById( 'datewriter' ).value );

	if( ! date_milsecs )
	{
		var current_time = new Date();

		var diff = 0;
		diff += current_time.getHours() * 60 * 60 * 1000;
		diff += current_time.getMinutes() * 60 * 1000;
		diff += current_time.getSeconds() * 1000;
		diff += current_time.getMilliseconds();

		var new_time = current_time.getTime() - diff;
		
		new_date = new Date( new_time );

		date_milsecs = new_date.getTime();
	}

	var time_secs    = parseInt( document.getElementById( 'timepicker' ).value );

	var select_milsec = ( time_secs * 1000 ) + date_milsecs;

	var server_time = calcTime( select_milsec, 7 );

	document.getElementById( 'dateresult' ).value = server_time / 1000;
}

{/literal}

function jquery_calendar_close( mydate )
{ldelim}
	update_result_field();
	{$onclose}();
{rdelim}

</script>

<input id="datewriter" type="hidden"  value="" />
<input id="dateresult" type="hidden" name="{$field_name}" value="{$default_value}" />

<div>
	<script>
	{literal}
		$(document).ready(function()
		{
		  $("#toggledatepicker").click(function () {
		    $("#pickdatetime").toggle();
		    $("#picknow").toggle();
		    return false;
		  });
		});
	{/literal}
	</script>

	<div id="picknow">
		{$default_value|datetime('short_date_time')}
		<button id="toggledatepicker">Schedule</button>
	</div>
	
	<div id="pickdatetime" style="display: none">
		<input type="text" id="datepicker" value="{$default_value_display}" size="12" readonly="readonly" />
		
		<select id="timepicker" onchange="jquery_calendar_close();">
			{while lt($time, $time_stop)}
                <option value="{$time}">{time_format( $time )}</option>
                {set $time = sum($time, $time_step)}
			{/while}
		</select>
	</div>

</div>

<script type="text/javascript">
	{$onclose}();
</script>
