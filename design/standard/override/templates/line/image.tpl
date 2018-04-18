{*
   INPUT:

   group:                  group images together into a gallery
*}

{if not( is_set( $group ) )}
	{def $rel='prettyPhoto'}
{else}
	{def $rel=concat('prettyPhoto',"[",$group,"]")}
{/if}
{$node.data_map.image.content|attribute( 'show', 1 ) }
<a href={$node.data_map.image.content[ $lightbox_image_class ].full_path|ezroot()} title="{$node.data_map.caption.data_text}" rel="{$rel}">
	{attribute_view_gui attribute=$node.data_map.name}
</a>