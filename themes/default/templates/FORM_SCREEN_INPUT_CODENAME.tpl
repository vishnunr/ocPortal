<div class="constrain_field">
	<input onkeydown="if (!key_pressed(event,[null,'0','1','2','3','4','5','6','7','8','9',190,'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','-','.','_'{+START,LOOP,EXTRA_CHARS},'{_loop_var;}'{+END}])) return false; return null;" size="16" {+START,IF_PASSED,MAXLENGTH}maxlength="{MAXLENGTH*}" {+END}{+START,IF_NON_PASSED,MAXLENGTH}maxlength="80" {+END}tabindex="{TABINDEX*}" class="input_codename{REQUIRED*}" type="text" id="{NAME*}" name="{NAME*}" value="{DEFAULT*}" />
</div>
