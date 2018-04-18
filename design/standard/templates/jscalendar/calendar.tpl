{if is_set( $field_name )|not}
	{def $field_name = 'date'}
{/if}
{if is_set( $onclose )|not}
	{def $onclose = "null"}
{/if}
{if is_set( $instance )|not}
	{def $instance = ''}
{/if}

{* date default values *}
{def $default_value_display = '- select -'}
{if or( is_set( $default_value )|not, $default_value|eq(0) )}
	{def $default_value = ''}
{else}
	{set $default_value_display = $default_value|datetime('jscalendar_long')}
{/if}

<input type="hidden" name="{$field_name}" id="f_date_e{$instance}" value="{$default_value}" />

<p>
	<span id="show_e{$instance}">{$default_value_display}</span>

	<img src={'calendar.gif'|ezimage()} id="f_trigger_e{$instance}" title="Date selector"
	     style="cursor: pointer; border: 1px solid red;"
	     onmouseover="this.style.background='red';"
	     onmouseout="this.style.background=''"
	/>
</p>

<script type="text/javascript">
    Calendar.setup(
    {ldelim}
        inputField     :    "f_date_e{$instance}",    // id of the input field
        ifFormat       :    "%s",                     // format of the input field
        displayArea    :    "show_e{$instance}",      // ID of the span where the date is to be shown
        daFormat       :    "%A, %B %d, %Y %H:%M",    // format of the displayed date
        button         :    "f_trigger_e{$instance}", // trigger button (well, IMG in our case)
        align          :    "Tl",                     // alignment (defaults to "Bl")
        singleClick    :    true,
        showsTime      :    true,
        onClose	   	   :	{$onclose}
    {rdelim}
    );
</script>
