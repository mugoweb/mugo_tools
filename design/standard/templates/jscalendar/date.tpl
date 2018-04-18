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
	$('#datepicker{/literal}{$instance}{literal}').datepicker
	({
		inline     : false,
		altField   : '#datewriter{/literal}{$instance}{literal}',
		altFormat  : '@',
		dateFormat : 'mm-dd-yy',
		onClose    : jquery_calendar_close{/literal}{$instance}{literal},
	});
});


function update_result_field( instance )
{
	var datewriter_field = document.getElementById( 'datewriter' + instance );
	var date_milsecs = parseInt( datewriter_field.value );

	if( ! date_milsecs )
	{
		var current_time = new Date();
		var diff = 0;
		diff += current_time.getHours() * 60 * 60;
		diff += current_time.getMinutes() * 60;
		diff += current_time.getSeconds();
	
		var new_time = current_time.getTime() - ( diff * 1000 );
		
		new_date = new Date( new_time );
		date_milsecs = new_date.getTime();
	}
	
	var server_time = calcTime( date_milsecs, 7 );

	datewriter_field.value = server_time / 1000;
}
{/literal}

function jquery_calendar_close{$instance}( mydate )
{ldelim}
	update_result_field( {$instance} );
	{$onclose}
{rdelim}
</script>

<input type="text" id="datepicker{$instance}" value="{$default_value_display}" size="12" />
<input type="hidden" id="datewriter{$instance}" value="{$default_value}" name="{$field_name}" />
