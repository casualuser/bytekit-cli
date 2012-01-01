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

/**
 * Utility methods.
 *
 * @author    Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright 2009-2012 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version   Release: @package_version@
 * @link      http://github.com/sebastianbergmann/bytekit-cli/tree
 * @since     Class available since Release 1.0.0
 */
abstract class Bytekit_Util
{
    /**
     * Decodes the results and operands of an opline.
     *
     * @param  array $operands Operands array to decode
     * @param  array $labels   Result from Bytekit_Util::findJumpLabels()
     * @return array
     * @see    Bytekit_Util::findJumpLabels
     */
    public static function decodeOperands(array $operands, array $labels = array())
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
                    } else {
                        $result['operands'][] = $operand['string'];
                    }
                } else {
                    $result['operands'][] = $operand['string'];
                }
            }
        }

        return $result;
    }

    /**
     * Eliminates dead code in an oparray.
     *
     * @param  array $oparray
     * @return array
     * @author Stefan Esser <stefan.esser@sektioneins.de>
     */
    public static function eliminateDeadCode(array &$oparray)
    {
        $count         = count($oparray['cfg']);
        $deadCode      = array();
        $foundDeadCode = FALSE;

        do {
            $in = array();

            for ($i = 1; $i <= $count; $i++) {
                $in[$i] = 0;
            }

            for ($i = 1; $i <= $count; $i++) {
                foreach ($oparray['cfg'][$i] as $child => $value) {
                    $in[$child]++;
                }
            }

            $foundDeadCode = FALSE;

            for ($i = 2; $i <= $count; $i++) {
                if ($in[$i] == 0 && !in_array($i, $deadCode)) {
                    $oparray['cfg'][$i] = array();
                    $foundDeadCode      = TRUE;
                    $deadCode[]         = $i;
                }
            }
        }
        while ($foundDeadCode);

        return $deadCode;
    }

    /**
     * Finds jump labels in an oparray.
     *
     * @param  array $oparray
     * @return array(address => label)
     * @author Stefan Esser <stefan.esser@sektioneins.de>
     */
    public static function findJumpLabels(array $oparray)
    {
        $_labels = array();

        foreach ($oparray['code'] as $opline) {
            foreach ($opline['operands'] as $operand) {
                if ($operand['type'] == BYTEKIT_TYPE_SYMBOL) {
                    $_labels[$operand['value']] = $operand['string'];
                }
            }
        }

        $labels = array();

        foreach ($oparray['code'] as $op => $opline) {
            if (isset($_labels[$opline['address']])) {
                $labels[$_labels[$opline['address']]] = $op;
            }
        }

        return $labels;
    }
}
?>
