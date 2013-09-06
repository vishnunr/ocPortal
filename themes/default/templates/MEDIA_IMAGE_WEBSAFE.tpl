{+START,IF_NON_PASSED_OR_FALSE,WYSIWYG_SAFE}
	{+START,IF_EMPTY,{$META_DATA,image}}
		{$META_DATA,image,{THUMB_URL}}
	{+END}
{+END}

{+START,IF_PASSED_AND_TRUE,FRAMED}
	<figure class="attachment">
		<figcaption>{!IMAGE}</figcaption>
		<div>
			{DESCRIPTION}
			<div class="attachment_details">
				<a {+START,IF,{$NOT,{$INLINE_STATS}}}onclick="return ga_track(this,'{!IMAGE;*}','{ORIGINAL_FILENAME;*}');" {+END}rel="lightbox" target="_blank" title="{+START,IF_NON_EMPTY,{DESCRIPTION}}{DESCRIPTION*}: {+END}{!LINK_NEW_WINDOW}" href="{URL*}"><img {+START,IF,{$NEQ,{WIDTH}x{HEIGHT},240x216}}width="{WIDTH*}" height="{HEIGHT*}" {+END}src="{THUMB_URL*}"{+START,IF_NON_PASSED_OR_FALSE,WYSIWYG_SAFE}{+START,IF,{EXPANDABLE}}{+START,IF_PASSED,NUM_DOWNLOADS} alt="{!IMAGE_ATTACHMENT,{NUM_DOWNLOADS*},{CLEAN_SIZE*}}"{+END}{+END}{+START,IF,{$NOT,{EXPANDABLE}}} title="{DESCRIPTION*}" alt="{DESCRIPTION*}"{+END}{+END}{+START,IF_PASSED_AND_TRUE,WYSIWYG_SAFE}{+START,IF,{EXPANDABLE}}{+END}{+START,IF,{$NOT,{EXPANDABLE}}} title="{DESCRIPTION*}"{+END} alt="{DESCRIPTION*}"{+END} /></a>

				<ul class="actions_list" role="navigation"><li class="actions_list_strong"><a target="_blank" title="{!_DOWNLOAD,{ORIGINAL_FILENAME*}}: {!LINK_NEW_WINDOW}" href="{URL*}">{!_DOWNLOAD,{ORIGINAL_FILENAME*}}</a> ({CLEAN_SIZE*}{+START,IF_NON_PASSED_OR_FALSE,WYSIWYG_SAFE}{+START,IF,{$INLINE_STATS}}{+START,IF_PASSED,NUM_DOWNLOADS}, {!DOWNLOADS_SO_FAR,{NUM_DOWNLOADS*}}{+END}{+END}{+END})</li></ul>
			</div>
		</div>
	</figure>
{+END}
{+START,IF_NON_PASSED_OR_TRUE,FRAMED}
	{+START,IF,{EXPANDABLE}}<a rel="lightbox" target="_blank" title="{DESCRIPTION*} {!LINK_NEW_WINDOW}" href="{URL*}">{+END}<img class="attachment_img"{+START,IF_NON_EMPTY,{WIDTH}} width="{WIDTH*}"{+END}{+START,IF_NON_EMPTY,{HEIGHT}} height="{HEIGHT*}"{+END} src="{THUMB_URL*}"{+START,IF_NON_PASSED_OR_FALSE,WYSIWYG_SAFE}{+START,IF,{EXPANDABLE}}{+START,IF_PASSED,NUM_DOWNLOADS} alt="{!IMAGE_ATTACHMENT,{NUM_DOWNLOADS*},{CLEAN_SIZE*}}"{+END}{+END}{+START,IF,{$NOT,{EXPANDABLE}}} title="{DESCRIPTION*}" alt="{DESCRIPTION*}"{+END}{+END}{+START,IF_PASSED_AND_TRUE,WYSIWYG_SAFE}{+START,IF,{EXPANDABLE}}{+END}{+START,IF,{$NOT,{EXPANDABLE}}} title="{DESCRIPTION*}"{+END} alt="{DESCRIPTION*}"{+END} />{+START,IF,{EXPANDABLE}}</a>{+END}
{+END}
