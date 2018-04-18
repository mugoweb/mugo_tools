{*
   INPUT:
   
   image_class:            image alias for thumnail
   lightbox_image_class:   image alias for the popupimage
   start_js:               default is true - send false if you need control in the calling template
*}

{if not( is_set( $image_class ) )}
	{def $image_class = 'medium'}
{/if}

{if not( is_set( $lightbox_image_class ) )}
	{def $lightbox_image_class = 'original'}
{/if}

{if not( is_set( $start_js ) )}
	{def $start_js = true()}
{/if}

<a class="lightbox" href={$attribute.content[ $lightbox_image_class ].full_path|ezroot()} title="{* where is the alt text? *}">
	{attribute_view_gui attribute=$attribute image_class=$image_class}
</a>

{if $start_js}
	<script type="text/javascript">
	{literal}
		$(function() {
			$('a.lightbox').lightBox();
		});
	
		$("#lightboximages div:first-child").css( 'display', 'block' );
	{/literal}
	</script>
{/if}