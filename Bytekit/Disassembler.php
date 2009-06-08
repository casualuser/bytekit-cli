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

require_once 'Bytekit/Functions.php';

/**
 * Disassembler.
 *
 * @author    Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright 2009 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version   Release: @package_version@
 * @link      http://github.com/sebastianbergmann/bytekit-cli/tree
 * @since     Class available since Release 1.0.0
 */
class Bytekit_Disassembler
{
    /**
     * Wrapper for bytekit_disassemble_file().
     *
     * @param  string $file
     * @return array
     */
    public function disassemble($file)
    {
        $bytecode = @bytekit_disassemble_file($file);
        $result   = array();

        foreach ($bytecode['functions'] as $function => $oparray) {
            $cv     = array();
            $ops    = array();
            $labels = bytekit_find_jump_labels($oparray);

            if (isset($oparray['raw']['cv'])) {
                foreach ($oparray['raw']['cv'] as $key => $name) {
                    $cv[] = sprintf('!%d = $%s', $key, $name);
                }
            }

            foreach ($oparray['code'] as $opline) {
                if (!isset($ops[$oparray['raw']['opcodes'][$opline['opline']]['lineno']])) {
                    $ops[$oparray['raw']['opcodes'][$opline['opline']]['lineno']] = array();
                }

                $operands = $this->decodeOperands($opline['operands'], $labels);

                $ops[$oparray['raw']['opcodes'][$opline['opline']]['lineno']][] = array(
                  'mnemonic' => $opline['mnemonic'],
                  'operands' => join(', ', $operands['operands']),
                  'results'  => join(', ', $operands['results'])
                );
            }

            $result[$function] = array(
              'cv' => $cv, 'ops' => $ops, 'num_ops' => count($oparray['code'])
            );
        }

        return array($file => $result);
    }

    /**
     * Decodes the results and operands of an opline.
     *
     * @param  array $operands
     * @param  array $labels
     * @return string
     */
    protected function decodeOperands(array $operands, array $labels)
    {
        $result = array(
          'operands' => array(), 'results' => array()
        );

        foreach ($operands as $operand) {
            $flags = $operand['flags'] & BYTEKIT_SRC_MASK;

            if ($flags == BYTEKIT_SRC_RES1) {
                $result['results'][0] = $operand['string'];
            }

            else if ($flags == BYTEKIT_SRC_RES2) {
                $result['results'][1] = $operand['string'];
            }

            else {
                if ($operand['type'] == BYTEKIT_TYPE_SYMBOL) {
                    if (isset($labels[$operand['string']])) {
                        $result['operands'][] = '->' . $labels[$operand['string']];
                    }
                } else {
                    $result['operands'][] = $operand['string'];
                }
            }
        }

        return $result;
    }
}
?>
