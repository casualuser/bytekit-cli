<?php
/**
 * bytekit-cli
 *
 * Copyright (c) 2009, Sebastian Bergmann <sb@sebastian-bergmann.de>.
 * Copyright (c) 2009, Lars Strojny <lstrojny@php.net>.
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
 * @author    Sebastian Bergmann <sb@sebastian-bergmann.de>, Lars Strojny <lstrojny@php.net>
 * @copyright 2009 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @since     File available since Release 1.0.0
 */

require_once 'Bytekit/Scanner/Rule.php';

/**
 * Scans for direct output of properties like in Zend_View
 *
 * @author    Sebastian Bergmann <sb@sebastian-bergmann.de>, Lars Strojny <lstrojny@php.net>
 * @copyright 2009 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version   Release: @package_version@
 * @link      http://github.com/sebastianbergmann/bytekit-cli/tree
 * @since     Class available since Release 1.0.0
 */
class Bytekit_Scanner_Rule_ZendView extends Bytekit_Scanner_Rule
{
    /**
     * Scan an oparray for direct output of properties
     *
     * @param array  $oparray
     * @param string $file
     * @param string $function
     * @param array  $result
     */
    public function process(array $oparray, $file, $function, array &$result)
    {
        foreach ($oparray['code'] as $n => $opline) {
            $violation = FALSE;

            if ($opline['mnemonic'] == 'ECHO' ||
                $opline['mnemonic'] == 'PRINT') {

                if ($this->_lastOpCodeIs('FETCH_OBJ_R', $oparray, $n)) {

                    $violation = TRUE;
                    $propertyChain = array();

                    $c = $n;
                    while ($c >= 0 && $this->_lastOpCodeIs('FETCH_OBJ_R', $oparray, $c--)) {

                        $operand = array_pop(
                            $oparray['code'][$c]['operands']
                        );

                        $propertyChain[] = $operand['value'];
                    }


                    if (isset($oparray['raw']['cv'][0]) &&
                        $oparray['raw']['line_end'] == $opline['opline']) {

                        $propertyChain[] = $oparray['raw']['cv'][0];
                    } else {
                        $propertyChain[] = 'this';
                    }
                }
            }

            if ($violation !== FALSE) {
                $this->addViolation(
                  sprintf(
                    'Property $%s has been printed without being'
                    . ' safeguarded with $this->escape()',
                    join('->', array_reverse($propertyChain))
                  ),
                  $oparray,
                  $file,
                  $oparray['raw']['opcodes'][$opline['opline']]['lineno'],
                  $function,
                  $result
                );
            }
        }
    }

    protected function _lastOpCodeIs($opcode, $oparray, $current)
    {
        return isset($oparray['code'][$current - 1]) &&
            $oparray['code'][$current - 1]['mnemonic'] == $opcode;
    }
}
?>