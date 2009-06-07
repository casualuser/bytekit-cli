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
 * Printer for result sets from Bytekit_Disassembler::disassemble().
 *
 * @author    Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright 2009 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version   Release: @package_version@
 * @link      http://github.com/sebastianbergmann/bytekit-cli/tree
 * @since     Class available since Release 1.0.0
 */
class Bytekit_TextUI_ResultPrinter_Disassembler_Text
{
    /**
     * Prints a result set from Bytekit_Disassembler::disassemble().
     *
     * @param array $result
     */
    public function printResult(array $result)
    {
        foreach ($result as $file => $functions) {
            $first = TRUE;

            foreach ($functions as $name => $data) {
                printf(
                  "%sFilename:           %s\n" .
                  "Function:           %s\n" .
                  "Number of oplines:  %d\n",
                  $first ? '' : "\n",
                  $file,
                  $name,
                  $data['num_ops']
                );

                if (!empty($data['cv'])) {
                    printf(
                      "Compiled variables: %s\n",
                      join(', ', $data['cv'])
                    );
                }

                print "\n  line  #     opcode                           operands\n" .
                      "  -----------------------------------------------------------------------------\n";

                $op = 0;

                foreach ($data['ops'] as $line => $ops) {
                    $first = TRUE;

                    foreach ($ops as $_op) {
                        if ($first) {
                            $first = FALSE;
                        } else {
                            $line = '';
                        }

                        printf(
                          "  %-5s %-5d %-32s %s\n",
                          $line,
                          $op++,
                          $_op['mnemonic'],
                          $_op['operands']
                        );
                    }
                }

                print "\n";
            }
        }
    }
}
?>
