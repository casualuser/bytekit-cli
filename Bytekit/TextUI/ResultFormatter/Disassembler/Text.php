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
 * Formatter for result sets from Bytekit_Disassembler::disassemble().
 *
 * @author    Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright 2009 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version   Release: @package_version@
 * @link      http://github.com/sebastianbergmann/bytekit-cli/tree
 * @since     Class available since Release 1.0.0
 */
class Bytekit_TextUI_ResultFormatter_Disassembler_Text
{
    /**
     * Formats a result set from Bytekit_Disassembler::disassemble().
     *
     * @param  array $result
     * @return string
     */
    public function formatResult(array $result)
    {
        $buffer = '';

        foreach ($result as $fileName => $functions) {
            uasort($functions, array($this, 'compare'));

            $first = TRUE;

            foreach ($functions as $functionName => $data) {
                $buffer .= sprintf(
                  "%sFilename:           %s\n" .
                  "Function:           %s\n" .
                  "Number of oplines:  %d\n",
                  $first ? '' : "\n",
                  $fileName,
                  $functionName,
                  $data['num_ops']
                );

                if (!empty($data['cv'])) {
                    $buffer .= sprintf(
                      "Compiled variables: %s\n",
                      join(', ', $data['cv'])
                    );
                }

                $buffer .= "\n  line  #     opcode                           result  operands\n" .
                           "  -----------------------------------------------------------------------------\n";
                $bb      = 1;
                $op      = 0;

                foreach ($data['ops'] as $lineNumber => $ops) {
                    $first = TRUE;

                    foreach ($ops as $_op) {
                        if ($_op['bb'] !== NULL && $_op['bb'] != $bb) {
                            $bb = $_op['bb'];
                            $buffer .= "\n";
                        }

                        if ($first) {
                            $first = FALSE;
                        } else {
                            $lineNumber = '';
                        }

                        $buffer .= sprintf(
                          "  %-5s %-5d %-32s %-7s %s\n",
                          $lineNumber,
                          $op++,
                          $_op['mnemonic'],
                          $_op['results'],
                          $_op['operands']
                        );
                    }
                }

                $buffer .=  "\n";
            }
        }

        return $buffer;
    }

    /**
     * Callback for uasort().
     *
     * @param  array $a
     * @param  array $b
     * @return integer
     */
    protected function compare($a, $b)
    {
        $a = array_keys($a['ops']);
        $a = $a[0];

        $b = array_keys($b['ops']);
        $b = $b[0];

        if ($a == $b) return 0;
        return ($a < $b) ? -1 : 1;
    }
}
?>
