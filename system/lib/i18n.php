<?php
/*
 * Author: Bo Maryniuk <bo@suse.de>
 *
 * Copyright (c) 2013 Bo Maryniuk. All Rights Reserved.
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


/**
 * One of many I18N implementation out there...
 * Author: Bo Maryniuk bo@suse.de
 */
class TextBundle {
    private $path;
    private $bundle;

    /**
     * Constructor.
     * 
     * @param type $path
     * @throws Exception
     */
    function __construct($path) {
        if (!file_exists($path)) {
            throw new Exception("Path not found: " . $path);
        }

        $this->path = $path;
    }

    /**
     * Load bundle from the given resource.
     * 
     * @param type $bundle
     * @param type $locale
     */
    public function load($bundle, $locale=null) {
        // Country support is not implemented yet.

        $locales = array("en");
        if ($locale != null) {
            $locales[1] = $locale;
        }
        
        $this->bundle = array();
        
        // Load once or twice for mergin over default langauge.
        foreach ($locales as $lc) {
            $target = sprintf("%s/%s_%s.xml", $this->path, $bundle, $lc);
            if (!file_exists($target) && $lc == "en") {
                throw new Exception("Unable to load default language file: " . $target);
            }

            $dom = new DOMDocument();
            $dom->load($target);
            foreach($dom->getElementsByTagName("entry") as $entryNode) {
                $this->bundle[$entryNode->getAttribute("key")] = $entryNode->nodeValue;
            }
        }
    }


    /**
     * Get text for the passed key.
     * @param type $text
     * @param any params as variables for the key variable.
     */
    public function getText($text) {
        $args = null;
        if (func_num_args() > 1) {
            $args = array_slice(func_get_args(), 1);
        }

        $template = null;
        if (isset($this->bundle[$text])) {
            $template = $this->bundle[$text];
        }
        
        if ($template != null && $args != null) {
            $idx = 0;
            foreach($args as $arg) {
                $template = str_replace("}", "", str_replace("{" . $idx, $arg, $template)); // %$#@^$ PHP's curly brackets! >:-(
                $idx++;
            }
        }
        
        return $template != null ? $template : ("***" . $text . "***");
    }
}


/**
 * Example usage
 */
$bundle = new TextBundle("system/resources/messages");

// English
$bundle->load("test");
echo "English:\n";
echo "\t" . $bundle->getText("hello.world") . "\n";
echo "\t" . $bundle->getText("how.are.you", get_current_user()) . "\n";
echo "\t" . $bundle->getText("not.translated") . "\n";
echo "\n";

// Japanese
$bundle->load("test", "jp");
echo "Japanese:\n";
echo "\t" . $bundle->getText("hello.world") . "\n";
echo "\t" . $bundle->getText("how.are.you", get_current_user()) . "\n";
echo "\t" . $bundle->getText("not.translated") . "\n";
echo "\n";

// Ukrainian
$bundle->load("test", "ua");
echo "Ukrainian:\n";
echo "\t" . $bundle->getText("hello.world") . "\n";
echo "\t" . $bundle->getText("how.are.you", get_current_user()) . "\n";
echo "\t" . $bundle->getText("not.translated") . "\n";
echo "\n";

// Not recognized:
echo "Not recognized:\n";
echo "\t" . $bundle->getText("gibberish") . "\n";
echo "\n";

?>
