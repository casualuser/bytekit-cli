<?php
/**
 * bytekit-cli
 *
 * Copyright (c) 2009-2011, Sebastian Bergmann <sb@sebastian-bergmann.de>.
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
 * @author    Lars Strojny <lstrojny@php.net>
 * @copyright 2009-2011 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @since     File available since Release 1.0.0
 */

require_once 'Bytekit/Scanner/Rule.php';

/**
 * Scans for attributes that are not safe-guarded by Zend_View::escape().
 *
 * @author    Sebastian Bergmann <sb@sebastian-bergmann.de>, Lars Strojny <lstrojny@php.net>
 * @author    Lars Strojny <lstrojny@php.net>
 * @copyright 2009-2011 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version   Release: @package_version@
 * @link      http://github.com/sebastianbergmann/bytekit-cli/tree
 * @since     Class available since Release 1.0.0
 */
class Bytekit_Scanner_Rule_ZendView extends Bytekit_Scanner_Rule
{
    /**
     * Scan an oparray for attributes that are not safe-guarded by
     * Zend_View::escape().
     *
     * @param array  $oparray
     * @param string $file
     * @param string $function
     * @param array  $result
     */
    public function process(array $oparray, $file, $function, array &$result)
    {
        $opArraySize = count($oparray);

        foreach ($oparray['code'] as $n => $opline) {
            $violation = FALSE;

            if ($opline['mnemonic'] == 'ECHO' ||
                $opline['mnemonic'] == 'PRINT') {

                if ($this->lastOpCodeIs('FETCH_OBJ_R', $oparray, $n) ||
                    $this->lastOpCodeIs('FETCH_DIM_R', $oparray, $n)) {
                    $c             = $n;
                    $propertyChain = array();
                    $violation     = TRUE;

                    while ($c >= 0 &&
                           ($this->lastOpCodeIs('FETCH_OBJ_R', $oparray, $c) ||
                            $this->lastOpCodeIs('FETCH_DIM_R', $oparray, $c))) {
                        $operand = array_pop(
                          $oparray['code'][--$c]['operands']
                        );

                        if ($this->lastOpCodeIs('FETCH_DIM_R', $oparray, $c + 1)) {
                            $propertyChain[] = array(
                              'name' => $operand['value'],
                              'type' => 'FETCH_DIM_R',
                            );
                        } else {
                            $propertyChain[] = array(
                              'name' => $operand['value'],
                              'type' => 'FETCH_OBJ_R',
                            );
                        }
                    }

                    $searchPosition = $n;
                    $variableName   = 'this';

                    while ($searchPosition <= $opArraySize &&
                           isset($oparray['code'][$searchPosition]['operands'])) {
                        foreach ($oparray['code'][$searchPosition]['operands'] as $operand) {
                            if ($operand['string'][0] == '!') {
                                $cvPos        = str_replace('!', '', $operand['string']);
                                $variableName = $oparray['raw']['cv'][$cvPos];
                                break 2;
                            }
                        }

                        ++$searchPosition;
                    }

                    $propertyChain[] = array(
                      'name' => $variableName,
                      'type' => 'FETCH_OBJ_R',
                    );
                }
            }

            if ($violation !== FALSE) {
                $formattedProperty = NULL;

                foreach (array_values(array_reverse($propertyChain)) as $position => $property) {
                    if ($formattedProperty === NULL) {
                        $formattedProperty = sprintf('$%s', $property['name']);
                        continue;
                    }

                    if ($property['type'] == 'FETCH_DIM_R') {
                        $formattedProperty .= sprintf('["%s"]', $property['name']);
                        continue;
                    }

                    if ($property['type'] == 'FETCH_OBJ_R') {
                        $formattedProperty .= sprintf('->%s', $property['name']);
                    }
                }

                $this->addViolation(
                  sprintf(
                    'Attribute %s is not safe-guarded by Zend_View::escape()',
                    $formattedProperty
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
}
?>
