<?php
/**
 * bytekit-cli
 *
 * Copyright (c) 2009-2012, Sebastian Bergmann <sb@sebastian-bergmann.de>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Sebastian Bergmann nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package   Bytekit
 * @author    Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright 2009-2012 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @since     File available since Release 1.0.0
 */

require_once 'Symfony/Component/Finder/Finder.php';
require_once 'Symfony/Component/Finder/Glob.php';
require_once 'Symfony/Component/Finder/Iterator/FileTypeFilterIterator.php';
require_once 'Symfony/Component/Finder/Iterator/FilenameFilterIterator.php';
require_once 'Symfony/Component/Finder/Iterator/RecursiveDirectoryIterator.php';
require_once 'Symfony/Component/Finder/Iterator/ExcludeDirectoryFilterIterator.php';
require_once 'Symfony/Component/Finder/SplFileInfo.php';
require_once 'ezc/Base/base.php';

spl_autoload_register(
    function($class) {
        static $classes = NULL;

        if ($classes === NULL) {
            $classes = array(
              'bytekit_disassembler' => '/Disassembler.php',
              'bytekit_scanner' => '/Scanner.php',
              'bytekit_scanner_rule' => '/Scanner/Rule.php',
              'bytekit_scanner_rule_directoutput' => '/Scanner/Rule/DirectOutput.php',
              'bytekit_scanner_rule_disallowedopcodes' => '/Scanner/Rule/DisallowedOpcodes.php',
              'bytekit_scanner_rule_output' => '/Scanner/Rule/Output.php',
              'bytekit_scanner_rule_zendview' => '/Scanner/Rule/ZendView.php',
              'bytekit_textui_command' => '/TextUI/Command.php',
              'bytekit_textui_resultformatter_disassembler_graph' => '/TextUI/ResultFormatter/Disassembler/Graph.php',
              'bytekit_textui_resultformatter_disassembler_text' => '/TextUI/ResultFormatter/Disassembler/Text.php',
              'bytekit_textui_resultformatter_scanner_text' => '/TextUI/ResultFormatter/Scanner/Text.php',
              'bytekit_textui_resultformatter_scanner_xml' => '/TextUI/ResultFormatter/Scanner/XML.php',
              'bytekit_util' => '/Util.php'
            );
        }

        $cn = strtolower($class);

        if (isset($classes[$cn])) {
            require __DIR__ . $classes[$cn];
        }
    }
);

spl_autoload_register(array('ezcBase', 'autoload'));
