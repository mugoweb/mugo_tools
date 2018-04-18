{*
   INPUT:
   
   image_class:            image alias for thumnail
   lightbox_image_class:   image alias for the popupimage
   group:                  group images together into a gallery
*}

{if not( is_set( $image_class ) )}
	{def $image_class = 'medium'}
{/if}

{if not( is_set( $lightbox_image_class ) )}
	{def $lightbox_image_class = 'original'}
{/if}

{if not( is_set( $group ) )}
	{def $rel='prettyPhoto'}
{else}
	{def $rel=concat('prettyPhoto',"[",$group,"]")}
{/if}

<a href={$node.data_map.image.content[ $lightbox_image_class ].full_path|ezroot()} title="{$node.data_map.caption.data_text}" rel="{$rel}">
	{attribute_view_gui attribute=$node.data_map.image image_class=$image_class}
</a>
{if $node.data_map.caption.has_content}
	<p>
		{$node.data_map.caption.content}
	</p>
{/if}
