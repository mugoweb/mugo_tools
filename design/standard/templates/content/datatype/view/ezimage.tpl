{*
Input:
 image_class - Which image alias to show, default is large
 css_class     - Optional css class to wrap around the <img> tag, the
                 class will be placed in a <div> tag.
 alignment     - How to align the image, use 'left', 'right' or false().
 link_to_image - boolean, if true the url_alias will be fetched and
                 used as link.
 href          - Optional string, if set it will create a <a> tag
                 around the image with href as the link.
 border_size   - Size of border around image, default is 0
*}
{default image_class=large
         css_class=false()
         alignment=false()
         link_to_image=false()
         href=false()
         target=false()
         hspace=false()
         border_size=0}

{let image_content=$attribute.content}

{section show=$image_content.is_valid}
    {let image=$image_content[$image_class]}

    {section show=$link_to_image}
        {let image_original=$image_content['original']}
        {set href=$image_original.url|ezroot}
        {/let}
    {/section}
    {switch match=$alignment}
    {case match='left'}
        <div class="imageleft">
    {/case}
    {case match='right'}
        <div class="imageright">
    {/case}
    {case/}
    {/switch}

    {if and( is_set( $image ), $image )}
        {section show=$href}<a href={$href}{section show=and( is_set( $link_class ), $link_class )} class="{$link_class}"{/section}{section show=and( is_set( $link_id ), $link_id )} id="{$link_id}"{/section}{section show=$target} target="{$target}"{/section}>{/section}
        <img {if $css_class}class="{$css_class}"{/if} src={$image.url|ezroot} width="{$image.width}" height="{$image.height}" {section show=$hspace}hspace="{$hspace}"{/section} style="border: {$border_size}px;" alt="{$image.text|wash(xhtml)}" title="{$image.text|wash(xhtml)}" />
        {section show=$href}</a>{/section}
    {else}
    	{def $filters = ezini( $image_class, 'Filters', 'image.ini' )
    	     $height = ''
    	     $width = ''
    	     $image_info = ''}
    	
    	{if is_array( $filters )}
    		{set $image_info = $filters.0}
    		{set $image_info = $image_info|explode( '=' )}
    		{set $image_info = $image_info.1}
    		{set $image_info = $image_info|explode( ';' )}
    	{/if}
    	
    	<img {if $css_class}class="{$css_class}"{/if} src={'placeholder.jpg'|ezimage()} width="{$image_info.0}" height="{$image_info.1}" />
    {/if}

    {switch match=$alignment}
    {case match='left'}
        </div>
    {/case}
    {case match='right'}
        </div>
    {/case}
    {case/}
    {/switch}

    {/let}

{/section}

{/let}

{/default}
