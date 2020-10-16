<?php
/**
 * The Components_Dependencies_Bootstrap:: class provides the Components
 * dependencies specifically for the bootstrapping process.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
namespace Horde\Components\Dependencies;
use Horde\Components\Component\Factory as ComponentFactory;
use Horde\Components\Config;
use Horde\Components\Config\Bootstrap as ConfigBootstrap;
use Horde\Components\Dependencies;
use Horde\Components\Output;
use Horde\Components\Release\Tasks as ReleaseTasks;
use Horde\Components\Runner\Change as RunnerChange;
use Horde\Components\Runner\CiDistribute as RunnerDistribute;
use Horde\Components\Runner\CiPrebuild as RunnerCiPrebuild;
use Horde\Components\Runner\CiSetup as RunnerCiSetup;
use Horde\Components\Runner\Composer as RunnerComposer;
use Horde\Components\Runner\Dependencies as RunnerDependencies;
use Horde\Components\Runner\FetchDocs as RunnerFetchDocs;
use Horde\Components\Runner\Installer as RunnerInstaller;
use Horde\Components\Runner\Qc as RunnerQc;
use Horde\Components\Runner\Release as RunnerRelease;
use Horde\Components\Runner\Snapshot as RunnerSnapshot;
use Horde\Components\Runner\Update as RunnerUpdate;
use Horde\Components\Runner\WebDocs as RunnerWebDocs;


/**
 * The Components_Dependencies_Bootstrap:: class provides the Components
 * dependencies specifically for the bootstrapping process.
 *
 * Copyright 2011-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class Bootstrap implements Dependencies
{
    /**
     * Initialized instances.
     *
     * @var array
     */
    private $_instances;

    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    /**
     * Returns an instance.
     *
     * @param string $interface The interface matching the requested instance.
     *
     * @return mixed the instance.
     */
    public function getInstance($interface)
    {
        if (!isset($this->_instances[$interface])) {
            switch ($interface) {
            case 'Horde\Components\Component\Factory':
            case 'Component\Factory':
                require_once __DIR__ . '/../Component/Factory.php';
                $this->_instances[$interface] = new $interface(
                    $this->getInstance('Config'),
                    $this->getInstance('Pear\Factory'),
                    new \Horde_Http_Client()
                );
                break;
            case 'Pear\Factory':
            case 'Horde\Components\Pear\Factory':
                require_once __DIR__ . '/../Pear/Factory.php';
                $this->_instances[$interface] = new $interface($this);
                break;
            case 'Config':
            case 'Horde\Components\Config':
                require_once __DIR__ . '/../Config.php';
                require_once __DIR__ . '/../Config/Base.php';
                require_once __DIR__ . '/../Config/Bootstrap.php';
                $this->_instances[$interface] = new ConfigBootstrap();
                break;
            case 'Output':
            case 'Horde\Components\Output':
                require_once __DIR__ . '/../Output.php';
                $this->_instances[$interface] = new Output(
                    $this->getInstance('Horde_Cli'),
                    $this->getInstance('Config')->getOptions()
                );
                break;
            case 'Horde_Cli':
                require_once __DIR__ . '/../../../../Cli/lib/Horde/Cli.php';
                $this->_instances[$interface] = new \Horde_Cli();
                break;
            }
        }
        return $this->_instances[$interface];
    }

    /**
     * Creates an instance.
     *
     * @param string $interface The interface matching the requested instance.
     *
     * @return mixed the instance.
     */
    public function createInstance($interface)
    {
        switch ($interface) {
        case 'Horde\Components\Pear\Environment':
        case 'Pear\Environment':
            return new $interface($this->getInstance('Output'));
        case 'Horde\Components\Pear\Package':
        case 'Pear\Package':
            return new $interface($this->getInstance('Output'));
        case 'Horde\Components\Pear\Dependencies':
        case 'Pear\Dependencies':
            return new $interface($this->getInstance('Output'));
        }
    }

    /**
     * Initial configuration setup.
     *
     * @param Config $config The configuration.
     *
     * @return void
     */
    public function initConfig(Config $config)
    {
    }

    /**
     * Set the list of modules.
     *
     * @param \Horde_Cli_Modular $modules The list of modules.
     *
     * @return void
     */
    public function setModules(\Horde_Cli_Modular $modules)
    {
    }

    /**
     * Return the list of modules.
     *
     * @retunr \Horde_Cli_Modular The list of modules.
     */
    public function getModules()
    {
    }

    /**
     * Set the CLI parser.
     *
     * @param \Horde_Argv_Parser $parser The parser.
     *
     * @return void
     */
    public function setParser($parser)
    {
    }

    /**
     * Return the CLI parser.
     *
     * @return \Horde_Argv_Parser The parser.
     */
    public function getParser()
    {
    }

    /**
     * Returns the continuous integration setup handler.
     *
     * @return RunnerCiSetup The CI setup handler.
     */
    public function getRunnerCiSetup()
    {
        return $this->getInstance('Runner\CiSetup');
    }

    /**
     * Returns the continuous integration pre-build handler.
     *
     * @return RunnerCiPrebuild The CI pre-build handler.
     */
    public function getRunnerCiPrebuild()
    {
        return $this->getInstance('Runner\CiPrebuild');
    }

    /**
     * Returns the distribution handler for a package.
     *
     * @return RunnerDistribute The distribution handler.
     */
    public function getRunnerDistribute()
    {
        return $this->getInstance('Runner\Distribute');
    }

    /**
     * Returns the website documentation handler for a package.
     *
     * @return RunnerWebdocs The documentation handler.
     */
    public function getRunnerWebdocs()
    {
        return $this->getInstance('Runner\Webdocs');
    }

    /**
     * Returns the documentation fetch handler for a package.
     *
     * @return RunnerFetchdocs The fetch handler.
     */
    public function getRunnerFetchdocs()
    {
        return $this->getInstance('Runner\Fetchdocs');
    }

    /**
     * Returns the composer handler for a package.
     *
     * @return RunnerComposer The composer handler.
     */
    public function getRunnerComposer()
    {
        return $this->getInstance('Runner\Composer');
    }

    /**
     * Returns the release handler for a package.
     *
     * @return RunnerRelease The release handler.
     */
    public function getRunnerRelease()
    {
        return $this->getInstance('Runner\Release');
    }

    /**
     * Returns the qc handler for a package.
     *
     * @return RunnerQc The qc handler.
     */
    public function getRunnerQc()
    {
        return $this->getInstance('Runner\Qc');
    }

    /**
     * Returns the change log handler for a package.
     *
     * @return RunnerChange The change log handler.
     */
    public function getRunnerChange()
    {
        return $this->getInstance('Runner\Change');
    }

    /**
     * Returns the snapshot packaging handler for a package.
     *
     * @return RunnerSnapshot The snapshot handler.
     */
    public function getRunnerSnapshot()
    {
        return $this->getInstance('Runner\Snapshot');
    }

    /**
     * Returns the dependency list handler for a package.
     *
     * @return RunnerDependencies The dependency handler.
     */
    public function getRunnerDependencies()
    {
        return $this->getInstance('Runner\Dependencies');
    }

    /**
     * Returns the installer for a package.
     *
     * @return RunnerInstaller The installer.
     */
    public function getRunnerInstaller()
    {
        return $this->getInstance('Runner\Installer');
    }

    /**
     * Returns the package XML handler for a package.
     *
     * @return RunnerUpdate The package XML handler.
     */
    public function getRunnerUpdate()
    {
        return $this->getInstance('Runner\Update');
    }

    /**
     * Returns the release tasks handler.
     *
     * @return ReleaseTasks The release tasks handler.
     */
    public function getReleaseTasks()
    {
        return $this->getInstance('Release\Tasks');
    }

    /**
     * Returns the output handler.
     *
     * @return Output The output handler.
     */
    public function getOutput()
    {
        return $this->getInstance('Output');
    }

    /**
     * Returns a component instance factory.
     *
     * @return ComponentFactory The component factory.
     */
    public function getComponentFactory()
    {
        return $this->getInstance('Component\Factory');
    }

    /**
     * Returns the handler for remote PEAR servers.
     *
     * @return \Horde_Pear_Remote The handler.
     */
    public function getRemote()
    {
        return $this->getInstance('Horde_Pear_Remote');
    }

    /**
     * Create the CLI handler.
     *
     * @return \Horde_Cli The CLI handler.
     */
    public function createCli()
    {
        return \Horde_Cli::init();
    }
}
