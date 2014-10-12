<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		testing_platform
 */

/**
 * ocPortal test case class (unit testing).
 */
class xhtml_substr_test_set extends ocp_test_case
{
	function setUp()
	{
		require_code('xhtml');
	}

	function testMisc1()
	{
		$this->assertTrue(xhtml_substr('test',0,NULL)=='test');
	}

	function testMisc2()
	{
		$this->assertTrue(xhtml_substr('test',0,4)=='test');
	}

	function testMisc3()
	{
		$this->assertTrue(xhtml_substr('test',0,3)=='tes');
	}

	function testMisc4()
	{
		$this->assertTrue(xhtml_substr('test',1,3)=='est');
	}

	function testMisc5()
	{
		$this->assertTrue(xhtml_substr('test',1,2)=='es');
	}

	function testMisc6()
	{
		$this->assertTrue(xhtml_substr('test',-3)=='est');
	}

	function testMisc7()
	{
		$this->assertTrue(xhtml_substr('test',-2)=='st');
	}

	function testMisc8()
	{
		$this->assertTrue(xhtml_substr('<i>test</i>',0,NULL)=='<i>test</i>');
	}

	function testMisc9()
	{
		$this->assertTrue(xhtml_substr('<i>test</i>',0,4)=='<i>test</i>');
	}

	function testMisc10()
	{
		$this->assertTrue(xhtml_substr('<i>test</i>',0,3)=='<i>tes</i>');
	}

	function testMisc11()
	{
		$this->assertTrue(xhtml_substr('<i>test</i>',1,3)=='<i>est</i>');
	}

	function testMisc12()
	{
		$this->assertTrue(xhtml_substr('<i>test</i>',1,2)=='<i>es</i>');
	}

	function testMisc13()
	{
		$this->assertTrue(xhtml_substr('<i>test</i>',-3)=='<i>est</i>');
	}

	function testMisc14()
	{
		$this->assertTrue(xhtml_substr('<i>test</i>',-2)=='<i>st</i>');
	}

	function testMisc15()
	{
		$this->assertTrue(xhtml_substr('<a><br /><x><i foo="bar">test</i>',-2)=='<a><x><i foo="bar">st</i></x></a>');
	}

	function testGrammar1()
	{
		$this->assertTrue(xhtml_substr('At least complete the first sentence for me. Second sentence that goes on and on and on so far as to block paragraph completion.',0,33,false,false,0.4)=='At least complete the first sentence for me.');
	}

	function testGrammar2()
	{
		$this->assertTrue(xhtml_substr('<p>At least complete the first paragraph for me. Second sentence.</p><p>Next paragraph.</p>',0,50,false,false,0.4)=='<p>At least complete the first paragraph for me. Second sentence.</p>');
	}

	function testGrammar3()
	{
		$this->assertTrue(xhtml_substr('At least complete any open words. Second sentence that goes on and on and on so far as to block paragraph completion.',0,10,false,false,0.4)=='At least complete');
	}

	function testSimple()
	{
		$before='<div>foobar</div>';
		$after=xhtml_substr($before,0,3,false,false,0.0);
		$expected='<div>foo</div>';
		$this->assertTrue($after==$expected);
	}

	function testWords1()
	{
		$before='<div>foobar</div>';
		$after=xhtml_substr($before,0,3,false,false,1.5);
		$expected='<div>foobar</div>';
		$this->assertTrue($after==$expected);
	}

	function testWords2()
	{
		$before='<div>foobar</div><div>myfoo</div>';
		$after=xhtml_substr($before,0,7,false,false,0.0);
		$expected='<div>foobar</div><div>m</div>';
		$this->assertTrue($after==$expected);
	}

	function testImage_1()
	{
		$before='<a href="www.google.com">My</a><div>foobar<img alt = "kevin" src="'.get_base_url().'/themes/default/images/ocf_emoticons/cheeky.png" />afterfoo </div>';
		$after=xhtml_substr($before,0,3,false,false,0.0);	
		$expected='<a href="www.google.com">My</a><div>f</div>';
		$this->assertTrue($after==$expected);
	}

 	function testImage_2()
	{
		$before='<a href="www.google.com">My</a><div>foobar<img alt = "kevin" src="'.get_base_url().'/themes/default/images/ocf_emoticons/cheeky.png" />afterfoo </div>';
		$after=xhtml_substr($before,0,2,false,false,0.0);
		$expected='<a href="www.google.com">My</a>';
		$this->assertTrue($after==$expected);
	}

 	function testImage_3()
	{
		$before='<a href="www.google.com">My</a><div>foobar<img alt = "kevin" src="'.get_base_url().'/themes/default/images/ocf_emoticons/cheeky.png" />afterfoo </div>';
		$after=xhtml_substr($before,0,12,false,false,0.0);
		$expected='<a href="www.google.com">My</a><div>foobar<img alt = "kevin" src="'.get_base_url().'/themes/default/images/ocf_emoticons/cheeky.png" />aft</div>';
		$this->assertTrue($after==$expected);
	}

 	function testImage_4()
	{
		$before='<a href="www.google.com">My</a><div>foobar<img alt = "kevin" src="'.get_base_url().'/themes/default/images/ocf_emoticons/cheeky.pngthumb_nail.jpg" />afterfoo </div>';
		$after=xhtml_substr($before,0,12,false,false,0.0);
		$expected='<a href="www.google.com">My</a><div>foobar<img alt = "kevin" src="'.get_base_url().'/themes/default/images/ocf_emoticons/cheeky.pngthumb_nail.jpg" /></div>';
		$this->assertTrue($after==$expected);
	}

 	function testAttachmentDoesNotSpoil()
	{
		require_code('lorem');
		require_code('files');

		$tpl=do_template('MEDIA_IMAGE_WEBSAFE',array(
			'URL'=>placeholder_url(),
			'REMOTE_ID'=>placeholder_id(),
			'THUMB_URL'=>placeholder_image_url(),
			'FILENAME'=>lorem_word(),
			'MIME_TYPE'=>lorem_word(),
			'CLICK_URL'=>placeholder_url(),

			'WIDTH'=>placeholder_number(),
			'HEIGHT'=>placeholder_number(),

			'LENGTH'=>placeholder_number(),

			'FILESIZE'=>placeholder_number(),
			'CLEAN_FILESIZE'=>clean_file_size(intval(placeholder_number())),

			'THUMB'=>true,
			'FRAMED'=>true,
			'WYSIWYG_EDITABLE'=>true,
			'NUM_DOWNLOADS'=>placeholder_number(),
			'DESCRIPTION'=>'',
		));

		$before=$tpl->evaluate();
		$after=xhtml_substr($before,0,5,false,false,0.0);

		$expected=$before;
		$this->assertTrue(preg_replace('#\s#','',$after)==preg_replace('#\s#','',$expected));
	}

 	function testNoBreak()
	{
		$before='<div class="xhtml_substr_no_break">Blah blah blah</div>';
		$after=xhtml_substr($before,0,5,false,false,0.0);

		$expected=$before;
		$this->assertTrue($after==$expected);
	}

 	function testDoesBreak()
	{
		$before='<div class="blah">Blah blah blah</div>';
		$after=xhtml_substr($before,0,5,false,false,0.0);

		$expected=$before;
		$this->assertTrue($after!=$expected);
	}
}