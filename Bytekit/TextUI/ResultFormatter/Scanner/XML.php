<?php
/**
 * bytekit-cli
 *
 * Copyright (c) 2009, Sebastian Bergmann <sb@sebastian-bergmann.de>.
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
 * @copyright 2009 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @since     File available since Release 1.0.0
 */

/**
 * XML Checkstyle formatter for result sets from Bytekit_Scanner::scan().
 *
 * @author    Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright 2009 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version   Release: @package_version@
 * @link      http://github.com/sebastianbergmann/bytekit-cli/tree
 * @since     Class available since Release 1.0.0
 */
class Bytekit_TextUI_ResultFormatter_Scanner_XML
{
    /**
     * Formats a result set from Bytekit_Scanner::scan() as Checkstyle XML.
     *
     * @param  array $result
     * @return string
     */
    public function formatResult(array $result)
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = TRUE;

        $pmd = $document->createElement('pmd');
        $pmd->setAttribute('version', 'bytekit-cli @package_version@');
        $document->appendChild($pmd);

        foreach ($result as $file => $items) {
            $xmlFile = $document->createElement('file');
            $xmlFile->setAttribute('name', $file);
            $pmd->appendChild($xmlFile);

            foreach ($items as $item) {
                $namespace = FALSE;
                $class     = FALSE;
                $tmp       = explode('\\', $item['function']);
                $function  = array_pop($tmp);

                if (!empty($tmp)) {
                    $namespace = join('\\', $tmp);
                }

                $_function = explode('::', $function);
                $function  = array_pop($_function);

                if (!empty($_function)) {
                    $class = $_function[0];
                }

                $xmlViolation = $document->createElement('violation');
                $xmlViolation->setAttribute('rule', $item['mnemonic']);
                $xmlViolation->setAttribute('line', $item['line']);

                if ($namespace) {
                    $xmlViolation->setAttribute('package', $namespace);
                }

                if ($class) {
                    $xmlViolation->setAttribute('class', $class);
                    $xmlViolation->setAttribute('method', $function);
                } else {
                    $xmlViolation->setAttribute('function', $function);
                }

                $xmlFile->appendChild($xmlViolation);
            }
        }

        return $document->saveXML();
    }
}
?>
