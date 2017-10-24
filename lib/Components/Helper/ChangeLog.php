<?php
/**
 * Components_Helper_ChangeLog:: helps with adding entries to the change log(s).
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */

/**
 * Components_Helper_ChangeLog:: helps with adding entries to the change log(s).
 *
 * Copyright 2010-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class Components_Helper_ChangeLog
{
    /** Path to the .horde.yml file. */
    const HORDE_INFO = '/.horde.yml';

    /** Path to the changelog.yml file. */
    const CHANGELOG = '/doc/changelog.yml';

    /** Path to the changelog.yml file up to Horde 5. */
    const CHANGELOG_H5 = '/docs/changelog.yml';

    /** Path to the CHANGES file. */
    const CHANGES = '/doc/CHANGES';

    /** Path to the CHANGES file up to Horde 5. */
    const CHANGES_H5 = '/docs/CHANGES';

    /**
     * The output handler.
     *
     * @var Component_Output
     */
    protected $_output;

    /**
     * The path to the component directory.
     *
     * @var string
     */
    protected $_directory;

    /**
     * Constructor.
     *
     * @param Component_Output $output   The output handler.
     * @param Components_Config $config  The configuration.
     */
    public function __construct(
        Components_Output $output, Components_Config $config
    )
    {
        $this->_output = $output;
        $this->_directory = $config->getPath();
    }

    /**
     * Update changelog.yml file.
     *
     * @param string $log         The log entry.
     * @param array  $options     Additional options.
     *
     * @return string  Path to the updated changelog.yml file.
     */
    public function changelogYml($log, $options)
    {
        if (!strlen($log)) {
            return;
        }

        if ($changelog = $this->changelogFileExists($this->_directory)) {
            if (empty($options['pretend'])) {
                $version = $this->addChangelog($log, $this->_directory);
                $this->_output->ok(
                    sprintf(
                        'Added new note to version %s of %s.',
                        $version,
                        $changelog
                    )
                );
            } else {
                $this->_output->info(
                    sprintf(
                        'Would add change log entry to %s now.',
                        $changelog
                    )
                );
            }
            return $changelog;
        }
    }

    /**
     * Update package.xml file.
     *
     * @param string                 $log     The log entry.
     * @param Horde_Pear_Package_Xml $xml     The package xml handler.
     * @param string                 $file    Path to the package.xml.
     * @param array                  $options Additional options.
     *
     * @return string  Path to the updated package.xml file.
     */
    public function packageXml($log, $xml, $file, $options)
    {
        if (file_exists($file)) {
            if (empty($options['pretend'])) {
                $xml->addNote($log);
                file_put_contents($file, (string)$xml);
                $this->_output->ok(
                    'Added new note to version ' . $xml->getVersion() . ' of ' . $file . '.'
                );
            } else {
                $this->_output->info(
                    sprintf(
                        'Would add change log entry to %s now.',
                        $file
                    )
                );
            }
            return $file;
        }
    }

    /**
     * Update CHANGES file.
     *
     * @param string $log         The log entry.
     * @param array  $options     Additional options.
     *
     * @return string  Path to the updated CHANGES file.
     */
    public function changes($log, $options)
    {
        if ($changes = $this->changesFileExists($this->_directory)) {
            if (empty($options['pretend'])) {
                $this->addChange($log, $changes);
                $this->_output->ok(
                    sprintf(
                        'Added new note to %s.',
                        $changes
                    )
                );
            } else {
                $this->_output->info(
                    sprintf(
                        'Would add change log entry to %s now.',
                        $changes
                    )
                );
            }
            return $changes;
        }
    }

    /**
     * Returns the link to the change log.
     *
     * @param string $root      The root of the component in the repository.
     *
     * @return string|null The link to the change log.
     */
    public function getChangelog($root)
    {
        if ($changes = $this->changesFileExists($this->_directory)) {
            $blob = trim(
                $this->systemInDirectory(
                    'git log --format="%H" HEAD^..HEAD',
                    $this->_directory,
                    array()
                )
            );
            $changes = preg_replace('#^' . $this->_directory . '#', '', $changes);
            return 'https://github.com/horde/horde/blob/' . $blob . $root . $changes;
        }
        return '';
    }

    /**
     * Run a system call.
     *
     * @param string $call       The system call to execute.
     * @param string $target_dir Run the command in the provided target path.
     * @param array  $options    Additional options.
     *
     * @return string The command output.
     */
    protected function systemInDirectory($call, $target_dir, $options)
    {
        $old_dir = getcwd();
        chdir($target_dir);
        $result = $this->system($call, $options);
        chdir($old_dir);
        return $result;
    }

    /**
     * Run a system call.
     *
     * @param string $call    The system call to execute.
     * @param array  $options Additional options.
     *
     * @return string The command output.
     */
    protected function system($call, $options)
    {
        if (empty($options['pretend'])) {
            //@todo Error handling
            return exec($call);
        } else {
            $this->_output->info(sprintf('Would run "%s" now.', $call));
        }
    }

    /**
     * Indicates if there is a changelog.yml file for this component.
     *
     * @return string|boolean The path to the changelog.yml file if it exists,
     *                        false otherwise.
     */
    public function changelogFileExists()
    {
        foreach (array(self::CHANGES, self::CHANGES_H5) as $path) {
            $changes = $this->_directory . $path;
            if (file_exists($changes)) {
                return $changes;
            }
        }
        return false;
    }

    /**
     * Indicates if there is a CHANGES file for this component.
     *
     * @return string|boolean The path to the CHANGES file if it exists, false
     *                        otherwise.
     */
    public function changesFileExists()
    {
        foreach (array(self::CHANGES, self::CHANGES_H5) as $path) {
            $changes = $this->_directory . $path;
            if (file_exists($changes)) {
                return $changes;
            }
        }
        return false;
    }

    /**
     * Add a change log entry to changelog.yml
     *
     * @param string $entry      Change log entry to add.
     *
     * @returns string  The updated version.
     */
    public function addChangelog($entry)
    {
        $hordeInfo = $this->_getHordeInfo($this->_directory);
        $changelog = Horde_Yaml::loadFile($this->_directory . self::CHANGELOG);
        $version = $hordeInfo['version']['release'];
        $info = $changelog[$version];
        $notes = explode("\n", trim($info['notes']));
        array_unshift($notes, $entry);
        $info['notes'] = implode("\n", $notes) . "\n";
        $changelog[$version] = $info;
        file_put_contents(
            $this->_directory . self::CHANGELOG,
            Horde_Yaml::dump($changelog, array('wordwrap' => 0))
        );
        return $version;
    }

    /**
     * Add a change log entry to CHANGES
     *
     * @param string $entry   Change log entry to add.
     * @param string $changes Path to the CHANGES file.
     */
    public function addChange($entry, $changes)
    {
        $tmp = Horde_Util::getTempFile();
        $entry = Horde_String::wrap($entry, 79, "\n      ");

        $oldfp = fopen($changes, 'r');
        $newfp = fopen($tmp, 'w');
        $counter = 0;
        while ($line = fgets($oldfp)) {
            if ($counter == 4) {
                fwrite($newfp, $entry . "\n");
            }
            $counter++;
            fwrite($newfp, $line);
        }
        fclose($oldfp);
        fclose($newfp);
        system("mv -f $tmp $changes");
    }

    /**
     * Updates package.xml from changelog.yml.
     *
     * @param Horde_Pear_Package_Xml $xml     The package xml handler.
     * @param string                 $file    Path to the package.xml.
     * @param array                  $options Additional options.
     *
     * @return string  Path to the updated package.xml file.
     */
    public function updatePackage($xml, $file, $options)
    {
        $changelog = $this->changelogFileExists($this->_directory);
        if (!$changelog || !file_exists($file)) {
            return;
        }

        if (empty($options['pretend'])) {
            $allchanges = Horde_Yaml::loadFile($changelog);
            $xml->setNotes($allchanges);
            file_put_contents($file, (string)$xml);
            $this->_output->ok(sprintf('Updated %s.', $file));
        } else {
            $this->_output->info(sprintf('Would update %s now.', $file));
        }

        return $file;
    }

    /**
     * Updates CHANGES from changelog.yml.
     *
     * @param array $options     Additional options.
     *
     * @return string  Path to the updated CHANGES file.
     */
    public function updateChanges($options)
    {
        $changelog = $this->changelogFileExists($this->_directory);
        $changes = $this->changesFileExists($this->_directory);
        if (!$changelog || !$changes) {
            return;
        }

        $hordeInfo = $this->_getHordeInfo($this->_directory);
        $allchanges = Horde_Yaml::loadFile($changelog);

        if (empty($options['pretend'])) {
            $changesfp = fopen($changes, 'w');
            $started = false;

            foreach ($allchanges as $version => $info) {
                if (!$started && $version != $hordeInfo['version']['release']) {
                    continue;
                }
                if (!$started) {
                    $version .= '-git';
                } else {
                    fwrite($changesfp, "\n\n");
                }
                $started = true;
                $version = 'v' . $version;
                $lines = str_repeat('-', strlen($version)) . "\n";
                fwrite($changesfp, $lines . $version . "\n" . $lines);

                $notes = explode("\n", $info['notes']);
                foreach ($notes as $entry) {
                    $entry = Horde_String::wrap($entry, 79, "\n      ");
                    fwrite($changesfp, "\n" . $entry);
                }
            }
            fclose($changesfp);
            $this->_output->ok(
                sprintf(
                    'Updated %s.',
                    $changes
                )
            );
        } else {
            $this->_output->info(
                sprintf(
                    'Would update %s now.',
                    $changes
                )
            );
        }

        return $changes;
    }

    /**
     * Returns the parsed information from the .horde.yml file.
     *
     * @return array  A Horde component information hash.
     */
    protected function _getHordeInfo()
    {
        $path = $this->_directory . self::HORDE_INFO;
        if (!file_exists($path)) {
            throw new Components_Exception($path . ' not found.');
        }
        return Horde_Yaml::loadFile($path);
    }
}
