<?php
/**
 * The Components_Dependencies_Injector:: class provides the
 * Components dependencies based on the Horde injector.
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
 * The Components_Dependencies_Injector:: class provides the
 * Components dependencies based on the Horde injector.
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
class Injector extends \Horde_Injector implements Dependencies
{
    /**
     * Use a pager for \Horde_Cli?
     *
     * @var boolean
     */
    protected $_usePager = false;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(new \Horde_Injector_TopLevel());
        $this->setInstance('Horde\Components\Dependencies', $this);
        $this->setInstance('Dependencies', $this);
        $this->bindFactory(
            'Horde_Cli', 'Dependencies', 'createCli'
        );
        $this->bindFactory(
            'Horde\Components\Output', 'Dependencies', 'createOutput'
        );
        $this->bindFactory(
            'Output', 'Dependencies', 'createOutput'
        );
        $shortHands = [
            'Component\Factory',
            'Runner\CiSetup',
            'Runner\CiPrebuild',
            'Runner\Distribute',
            'Runner\WebDocs',
            'Runner\FetchDocs',
            'Runner\Composer',
            'Runner\Release',
            'Runner\Qc',
            'Runner\Change',
            'Runner\Snapshot',
            'Runner\Dependencies',
            'Runner\Installer',
            'Runner\Update',
            'Release\Tasks',
            'Qc\Task\Cpd',
            'Qc\Task\Cs',
            'Qc\Task\Dcd',
            'Qc\Task\Lint',
            'Qc\Task\Loc',
            'Qc\Task\Md',
            'Qc\Task\Unit',
            'Qc\Task\Loc'            
        ];
        foreach ($shortHands as $short) {
            $this->bindImplementation($short, 'Horde\Components\\' . $short);
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
        $this->setInstance('Horde\Components\Config', $config);
        $this->setInstance('Config', $config);
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
        $this->setInstance('Horde_Cli_Modular', $modules);
    }

    /**
     * Return the list of modules.
     *
     * @retunr \Horde_Cli_Modular The list of modules.
     */
    public function getModules()
    {
        return $this->getInstance('Horde_Cli_Modular');
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
        $this->setInstance('Horde_Argv_Parser', $parser);
    }

    /**
     * Return the CLI parser.
     *
     * @retunr \Horde_Argv_Parser The parser.
     */
    public function getParser()
    {
        return $this->getInstance('Horde_Argv_Parser');
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
        return $this->getInstance('Horde\Components\Component\Factory');
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
     * Enables a pager for \Horde_Cli objects.
     */
    public function useCliPager()
    {
        $this->_usePager = true;
    }

    /**
     * Create the CLI handler.
     *
     * @return \Horde_Cli The CLI handler.
     */
    public function createCli()
    {
        return \Horde_Cli::init(array('pager' => $this->_usePager));
    }

    /**
     * Create the Components\Output handler.
     *
     * @return Output The output handler.
     */
    public function createOutput($injector)
    {
        return new Output(
            $injector->getInstance('Horde_Cli'),
            $injector->getInstance('Config')->getOptions()
        );
    }
}
