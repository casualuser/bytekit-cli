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
 * TextUI frontend for Bytekit.
 *
 * @author    Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright 2009-2012 Sebastian Bergmann <sb@sebastian-bergmann.de>
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
        $input = new ezcConsoleInput;

        $input->registerOption(
          new ezcConsoleOption(
            '',
            'eliminate-dead-code',
            ezcConsoleInput::TYPE_NONE
           )
        );

        $input->registerOption(
          new ezcConsoleOption(
            '',
            'exclude',
            ezcConsoleInput::TYPE_STRING,
            array(),
            TRUE
           )
        );

        $input->registerOption(
          new ezcConsoleOption(
            '',
            'format',
            ezcConsoleInput::TYPE_STRING,
            'dot',
            FALSE
           )
        );

        $input->registerOption(
          new ezcConsoleOption(
            '',
            'graph',
            ezcConsoleInput::TYPE_STRING
           )
        );

        $input->registerOption(
          new ezcConsoleOption(
            'h',
            'help',
            ezcConsoleInput::TYPE_NONE,
            NULL,
            FALSE,
            '',
            '',
            array(),
            array(),
            FALSE,
            FALSE,
            TRUE
           )
        );

        $input->registerOption(
          new ezcConsoleOption(
            '',
            'log-pmd',
            ezcConsoleInput::TYPE_STRING
           )
        );

        $input->registerOption(
          new ezcConsoleOption(
            '',
            'rule',
            ezcConsoleInput::TYPE_STRING,
            array(),
            TRUE
           )
        );

        $input->registerOption(
          new ezcConsoleOption(
            '',
            'suffixes',
            ezcConsoleInput::TYPE_STRING,
            'php',
            FALSE
           )
        );

        $input->registerOption(
          new ezcConsoleOption(
            'v',
            'version',
            ezcConsoleInput::TYPE_NONE,
            NULL,
            FALSE,
            '',
            '',
            array(),
            array(),
            FALSE,
            FALSE,
            TRUE
           )
        );

        try {
            $input->process();
        }

        catch (ezcConsoleOptionException $e) {
            print $e->getMessage() . "\n";
            exit(1);
        }

        if ($input->getOption('help')->value) {
            $this->showHelp();
            exit(0);
        }

        else if ($input->getOption('version')->value) {
            $this->printVersionString();
            exit(0);
        }

        $arguments = $input->getArguments();

        if (empty($arguments)) {
            $this->showHelp();
            exit(1);
        }

        $eliminateDeadCode = $input->getOption('eliminate-dead-code')->value;
        $excludes          = $input->getOption('exclude')->value;
        $format            = $input->getOption('format')->value;
        $graph             = $input->getOption('graph')->value;
        $logPmd            = $input->getOption('log-pmd')->value;
        $_rules            = $input->getOption('rule')->value;
        $suffixes          = explode(',', $input->getOption('suffixes')->value);

        array_map('trim', $suffixes);

        $rules = array();

        foreach ($_rules as $rule) {
            $ruleOptions = '';

            if (strpos($rule, ':') !== FALSE) {
                list($rule, $ruleOptions) = explode(':', $rule);
            }

            switch ($rule) {
                case 'DirectOutput': {
                    $rules[] = new Bytekit_Scanner_Rule_DirectOutput;
                }
                break;

                case 'DisallowedOpcodes': {
                    $disallowedOpcodes = explode(',', $ruleOptions);
                    array_map('trim', $disallowedOpcodes);

                    $rules[] = new Bytekit_Scanner_Rule_DisallowedOpcodes(
                      $disallowedOpcodes
                    );
                }
                break;

                case 'Output': {
                    $rules[] = new Bytekit_Scanner_Rule_Output;
                }
                break;

                case 'ZendView': {
                    $rules[] = new Bytekit_Scanner_Rule_ZendView;
                }
                break;
            }
        }

        $files = $this->findFiles($arguments, $excludes, $suffixes);

        if (empty($files)) {
            $this->showError("No files found to scan.\n");
        }

        $this->printVersionString();

        if (!empty($rules)) {
            $scanner   = new Bytekit_Scanner($rules);
            $result    = $scanner->scan($files);
            $formatter = new Bytekit_TextUI_ResultFormatter_Scanner_Text;

            print $formatter->formatResult($result);

            if (isset($logPmd)) {
                $formatter = new Bytekit_TextUI_ResultFormatter_Scanner_XML;
                file_put_contents($logPmd, $formatter->formatResult($result));
            }

            if (!empty($result)) {
                exit(1);
            }

            exit(0);
        }

        if (count($files) == 1) {
            $disassembler = new Bytekit_Disassembler($files[0]);

            if (isset($graph)) {
                $result = $disassembler->disassemble(FALSE, $eliminateDeadCode);

                $formatter = new Bytekit_TextUI_ResultFormatter_Disassembler_Graph;
                $formatter->formatResult($result, $graph, $format);
            } else {
                $result = $disassembler->disassemble(TRUE, $eliminateDeadCode);

                $formatter = new Bytekit_TextUI_ResultFormatter_Disassembler_Text;
                print $formatter->formatResult($result);
            }

            exit(0);
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
  --format <dot|svg|...>   Format for code flow graphs.

  --rule <rule>:<options>  Applies rules and reports violations.
  --xml <file>             Write violations report in PMD XML format.

  --eliminate-dead-code    Eliminate dead code.
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


    /**
     * @param  array $directories
     * @param  array $excludes
     * @param  array $suffixes
     * @return array
     */
    protected function findFiles(array $directories, array $excludes, array $suffixes)
    {
        $files   = array();
        $finder  = new Symfony\Component\Finder\Finder;
        $iterate = FALSE;

        try {
            foreach ($directories as $directory) {
                if (!is_file($directory)) {
                    $finder->in($directory);
                    $iterate = TRUE;
                } else {
                    $files[] = realpath($directory);
                }
            }

            foreach ($excludes as $exclude) {
                $finder->exclude($exclude);
            }

            foreach ($suffixes as $suffix) {
                $finder->name('*' . $suffix);
            }
        }

        catch (Exception $e) {
            $this->showError($e->getMessage() . "\n");
            exit(1);
        }

        if ($iterate) {
            foreach ($finder as $file) {
                $files[] = $file->getRealpath();
            }
        }

        return $files;
    }
}
?>
