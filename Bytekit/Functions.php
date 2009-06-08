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
 * Eliminates dead code in an oparray.
 *
 * @param  array $oparray
 * @return array
 * @author Stefan Esser <stefan.esser@sektioneins.de>
 */
function bytekit_eliminate_dead_code(array &$oparray)
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
function bytekit_find_jump_labels(array $oparray)
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
?>
