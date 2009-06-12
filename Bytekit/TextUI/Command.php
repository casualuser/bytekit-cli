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

require_once 'Bytekit/TextUI/Getopt.php';
require_once 'Bytekit/Util/FilterIterator.php';

/**
 * TextUI frontend for Bytekit.
 *
 * @author    Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright 2009 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version   Release: @package_version@
 * @link      http://github.com/sebastianbergmann/bytekit-cli/tree
 * @since     Class available since Release 1.0.0
 */
class Bytekit_TextUI_Command
{
    /**
     * Main method.
     */
    public function main()
    {
        try {
            $options = Bytekit_TextUI_Getopt::getopt(
              $_SERVER['argv'],
              '',
              array(
                'format=',
                'graph=',
                'help',
                'scan=',
                'suffixes=',
                'xml=',
                'version'
              )
            );
        }

        catch (RuntimeException $e) {
            $this->showError($e->getMessage());
        }

        $format    = 'dot';
        $mnemonics = array();
        $suffixes  = array('php');

        foreach ($options[0] as $option) {
            switch ($option[0]) {
                case '--format': {
                    $format = $option[1];
                }
                break;

                case '--graph': {
                    $graph = $option[1];
                }
                break;

                case '--help': {
                    $this->showHelp();
                    exit(0);
                }
                break;

                case '--scan': {
                    $mnemonics = explode(',', $option[1]);
                    array_map('trim', $mnemonics);
                }
                break;

                case '--suffixes': {
                    $suffixes = explode(',', $option[1]);
                    array_map('trim', $suffixes);
                }
                break;

                case '--version': {
                    $this->printVersionString();
                    exit(0);
                }
                break;

                case '--xml': {
                    $xml = $option[1];
                }
                break;
            }
        }

        $files = array();

        if (isset($options[1][0])) {
            foreach ($options[1] as $path) {
                if (is_dir($path)) {
                    $iterator = new Bytekit_Util_FilterIterator(
                      new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($path)
                      ),
                      $suffixes
                    );

                    foreach ($iterator as $item) {
                        $files[] = $item->getPathName();
                    }
                }

                else if (is_file($path)) {
                    $files[] = $path;
                }
            }
        }

        if (empty($files)) {
            $this->showHelp();
            exit(1);
        }

        $this->printVersionString();

        if (!empty($mnemonics)) {
            require_once 'Bytekit/Scanner.php';
            require_once 'Bytekit/TextUI/ResultFormatter/Scanner/Text.php';

            $scanner = new Bytekit_Scanner(
              array(new Bytekit_Scanner_Rule_DisallowedOpcodes($mnemonics))
            );

            $result = $scanner->scan($files);

            $formatter = new Bytekit_TextUI_ResultFormatter_Scanner_Text;
            print $formatter->formatResult($result);

            if (isset($xml)) {
                require_once 'Bytekit/TextUI/ResultFormatter/Scanner/XML.php';

                $formatter = new Bytekit_TextUI_ResultFormatter_Scanner_XML;
                file_put_contents($xml, $formatter->formatResult($result));
            }

            exit(0);
        }

        if (count($files) == 1) {
            require_once 'Bytekit/Disassembler.php';

            $disassembler = new Bytekit_Disassembler($files[0]);

            if (isset($graph)) {
                require_once 'Bytekit/TextUI/ResultFormatter/Disassembler/Graph.php';

                $result = $disassembler->disassemble(FALSE);

                $formatter = new Bytekit_TextUI_ResultFormatter_Disassembler_Graph;
                $formatter->formatResult($result, $graph, $format);
            } else {
                require_once 'Bytekit/TextUI/ResultFormatter/Disassembler/Text.php';

                $result = $disassembler->disassemble();

                $formatter = new Bytekit_TextUI_ResultFormatter_Disassembler_Text;
                print $formatter->formatResult($result);
            }

            exit(0);
        }
    }

    /**
     * Returns a set of files.
     *
     * @param  string $path
     * @param  array  $suffixes
     * @return Traversable
     */
    protected function getFiles($path, array $suffixes)
    {
        if (is_dir($path)) {
            return new Bytekit_Util_FilterIterator(
              new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path)
              ),
              $suffixes
            );
        }

        else if (is_file($path)) {
            return array(new SPLFileInfo($path));
        }
    }

    /**
     * Shows an error.
     *
     * @param string $message
     */
    protected function showError($message)
    {
        $this->printVersionString();

        print $message;

        exit(1);
    }

    /**
     * Shows the help.
     */
    protected function showHelp()
    {
        $this->printVersionString();

        print <<<EOT
Usage: bytekit [switches] <directory|file> ...

  --graph <directory>      Write code flow graph(s) to directory.
  --scan <MNEMONIC,...>    Scans for unwanted mnemonics.
  --xml <file>             Write violations report in PMD XML format.

  --format <dot|svg|...>   Format for code flow graphs.
  --suffixes <suffix,...>  A comma-separated list of file suffixes to check.

  --help                   Prints this usage information.
  --version                Prints the version and exits.

EOT;
    }

    /**
     * Prints the version string.
     */
    protected function printVersionString()
    {
        print "bytekit-cli @package_version@ by Sebastian Bergmann.\n\n";
    }
}
?>
