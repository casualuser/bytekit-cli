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
 * Visualizer for result sets from Bytekit_Disassembler::disassemble().
 *
 * @author    Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright 2009 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version   Release: @package_version@
 * @link      http://github.com/sebastianbergmann/bytekit-cli/tree
 * @since     Class available since Release 1.0.0
 */
class Bytekit_TextUI_ResultFormatter_Disassembler_Graph
{
    const GRAPH = <<<EOT
digraph flowgraph {
node [
    fontname="Courier"
    fontsize="12"
    shape="plaintext"
];

graph [
    rankdir="HR"
    bgcolor="#eeeeec"
    label="Control Flow Graph for %s()"
    labeljust="c"
    labelloc="t"
    fontname="Courier"
    fontsize="16"
];

mindist = 0.4;
overlap = false;

%s
%s
}
EOT;

    const NODE = <<<EOT
"bb_%d" [
  label =<<table border="2" cellborder="0" cellspacing="0" bgcolor="#d3d7cf">
<tr><td bgcolor="#fcaf3e" colspan="4" align="left"><font face="Courier-Bold" point-size="12">%s</font></td></tr>
%s
</table>>
];
EOT;

    const INSTRUCTION = <<<EOT
<tr><td align="left">%s</td><td align="left">%s</td><td align="left">%s</td><td align="left">%s</td></tr>

EOT;

    /**
     * @var boolean
     */
    protected $format;

    /**
     * Visualizes a result set from Bytekit_Disassembler::disassemble().
     *
     * @param array  $result
     * @param string $directory
     */
    public function formatResult(array $result, $directory, $format = 'dot')
    {
        if (!is_dir($directory)) {
            mkdir($directory);
        }

        $this->format = $format;
        $id           = 1;

        foreach ($result as $file => $functions) {
            foreach ($functions as $function => $data) {
                $bb    = 1;
                $nodes = array();

                foreach ($data['ops'] as $line => $ops) {
                    foreach ($ops as $_op) {
                        if ($_op['bb'] !== NULL && $_op['bb'] != $bb) {
                            $bb = $_op['bb'];

                            $nodes[$bb] = array(
                              'id'           => $id++,
                              'instructions' => array()
                            );
                        }

                        $nodes[$bb]['instructions'][] = array(
                          'address'  => $_op['address'],
                          'mnemonic' => $_op['mnemonic'],
                          'operands' => $_op['operands'],
                          'results'  => $_op['results']
                        );
                    }
                }

                $_nodes = '';

                foreach ($nodes as $bb => $node) {
                    $instructions = '';

                    foreach ($node['instructions'] as $instruction) {
                        $instructions .= sprintf(
                          self::INSTRUCTION,
                          htmlentities(sprintf('%08x', $instruction['address'])),
                          htmlentities($instruction['mnemonic']),
                          htmlentities($instruction['results']),
                          htmlentities($instruction['operands'])
                        );
                    }

                    $_nodes .= sprintf(
                      self::NODE,
                      $bb,
                      htmlentities(sprintf('%08x', $node['instructions'][0]['address'])),
                      $instructions
                    );
                }

                $edges = '';

                foreach ($data['cfg'] as $id => $cfg) {
                    if (isset($nodes[$id])) {
                        foreach ($cfg as $key => $value) {
                            switch ($value) {
                                case BYTEKIT_EDGE_TRUE: {
                                    $style = 'color="#4e9a06"';
                                }
                                break;

                                case BYTEKIT_EDGE_FALSE: {
                                    $style = 'color="#a40000"';
                                }
                                break;

                                case BYTEKIT_EDGE_NORMAL: {
                                    $style = 'color="#2e3436"';
                                }
                                break;

                                case BYTEKIT_EDGE_EXCEPTION: {
                                    $style = 'style=dotted, penwidth=3.0, color="#204a87"';
                                }
                                break;

                                default: {
                                    $style = 'color="#204a87"';
                                }

                            }

                            $edges .= sprintf(
                              '"bb_%d" -> "bb_%d" [%s];' . "\n",
                              $id,
                              $key,
                              $style
                            );
                        }
                    }
                }

                $dot = sprintf(
                  self::GRAPH,
                  $function,
                  $_nodes,
                  $edges
                );

                $filename = sprintf(
                  '%s%s%s.%s',
                  $directory,
                  DIRECTORY_SEPARATOR,
                  preg_replace('#[^\w.]#', '_', $function),
                  $this->format
                );

                if ($format == 'dot') {
                    file_put_contents($filename, $dot);
                } else {
                    $process = proc_open(
                      'dot -T' . $format . ' -o' . $filename,
                      array(0 => array('pipe', 'r')),
                      $pipes
                    );

                    if (is_resource($process)) {
                        fwrite($pipes[0], $dot);
                        fclose($pipes[0]);
                        proc_close($process);
                    }
                }

                printf('Wrote "%s".' . "\n", $filename);
            }
        }
    }
}
?>
