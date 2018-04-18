{*
   INPUT:

   group:                  group images together into a gallery
*}

{if not( is_set( $group ) )}
	{def $rel='lightbox'}
{else}
	{def $rel=concat('lightbox',"[",$group,"]")}
{/if}
<a href={$object.data_map.image.content[original].full_path|ezroot()} rel="{$rel}">{attribute_view_gui attribute=$object.data_map.name}</a>