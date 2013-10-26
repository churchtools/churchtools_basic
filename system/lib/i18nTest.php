<?php
/*
 * Author: Bo Maryniuk <bo@suse.de>
 *
 * Copyright (c) 2013 Bo Maryniuk. All Rights Reserved.
 *               2013 Patrick Georgi
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are
 * met:
 *
 *     1. Redistributions of source code must retain the above copyright notice,
 *     this list of conditions and the following disclaimer.
 *
 *     2. Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in the
 *     documentation and/or other materials provided with the distribution.
 *
 *     3. The name of the author may not be used to endorse or promote products
 *     derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY BO MARYNIUK "AS IS" AND ANY EXPRESS OR IMPLIED
 * WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO
 * EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED
 * TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
 * LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

require 'i18n.php';

class I18NTest extends PHPUnit_Framework_TestCase {
	public function testBasicI18nOperation() {
		$bundle = new TextBundle("system/resources/messages");

		// English
		$bundle->load("test");
		$this->assertEquals("Hello, World!", $bundle->getText("hello.world"));
		$this->assertEquals("How are you, PHPUnit?", $bundle->getText("how.are.you", 'PHPUnit'));
		$this->assertEquals("This message is only in <strong>English</strong>", $bundle->getText("not.translated"));

		// Japanese
		$bundle->load("test", "jp");
		$this->assertEquals("こんにちは、世界。", $bundle->getText("hello.world"));
		$this->assertEquals("どのようにあなたは、PHPUnitさん？", $bundle->getText("how.are.you", 'PHPUnit'));
		$this->assertEquals("This message is only in <strong>English</strong>", $bundle->getText("not.translated"));

		// Ukrainian
		$bundle->load("test", "ua");
		$this->assertEquals("Здоров, світе!", $bundle->getText("hello.world"));
		$this->assertEquals("Як си маєте, пане PHPUnit?", $bundle->getText("how.are.you", 'PHPUnit'));
		$this->assertEquals("This message is only in <strong>English</strong>", $bundle->getText("not.translated"));

		// Not recognized:
		$this->assertEquals("***gibberish***", $bundle->getText("gibberish"));
	}
}

