<a{+START,IF,{$IN_STR,{CAPTION},<img}} class="link_exempt"{+END} href="{$STRIP_TAGS,{URL*}}"{+START,IF,{$EQ,{TARGET,_blank}}} rel="external"{+END}{+START,IF,{$NEQ,{TARGET,_blank}}} rel="{REL*}"{+END} target="{TARGET*}"{+START,IF_NON_EMPTY,{TITLE}} title="{+START,IF_NON_EMPTY,{$TRIM,{$STRIP_TAGS,{CAPTION}}}}{$STRIP_TAGS,{CAPTION}}: {+END}{TITLE*}"{+END}>{$TRIM,{CAPTION}}</a>
