<?php
/**
 * Copyright 2013-2019 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013-2019 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Components
 */

/**
 * @author    Michael Slusarz <slusarz@horde.org>
 * @author    Ralf Lang <lang@horde.org>
 * @category  Horde
 * @copyright 2013-2019 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Components
 */
class Components_Helper_Composer
{

    protected $_repositories = array();

    /**
     * @var array A list of pear packages to replace by known alternatives
     */
    protected $_substitutes = array();
    /**
     * Updates the composer.json file.
     *
     * @param Components_Wrapper_HordeYml $package  The package definition
     * @param array  $options  The set of options for the operation.
     */
    public function generateComposeJson(Components_Wrapper_HordeYml $package, array $options = array())
    {
        if (!empty($options['composer']['pear-substitutes']))
        {
            $this->_substitutes = $options['composer']['pear-substitutes'];
        }
        $filename = dirname($package->getFullPath()) . '/composer.json';
        $composerDefinition = new stdClass();
        $this->_setName($package, $composerDefinition);
        // Is this intentional? "description" seems always longer than full
        $composerDefinition->description = $package['full'];
        $this->_setType($package, $composerDefinition);
        $composerDefinition->homepage = 'https://www.horde.org';
        $composerDefinition->license = $package['license']['identifier'];
        $this->_setAuthors($package, $composerDefinition);
        // cut off any -git or similar
        list($version) = explode('-', $package['version']['release']);
        $composerDefinition->version = $version;
        $composerDefinition->time = (new Horde_Date(mktime()))->format('Y-m-d');
        $composerDefinition->repositories = [];
        $this->_setRequire($package, $composerDefinition);
        $this->_setSuggest($package, $composerDefinition);
        $this->_setRepositories($package, $composerDefinition);
        $this->_setAutoload($package, $composerDefinition);
        // Development dependencies?
        // Replaces ? Only needed for special cases. Default cases are handled implicitly
        // provides? apps can depend on provided APIs rather than other apps
        file_put_contents($filename, json_encode($composerDefinition, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));

        if (isset($options['logger'])) {
            $options['logger']->ok(
                'Created composer.json file.'
            );
        }
    }

    protected function _setName(Components_Wrapper_HordeYml $package, stdClass $composerDefinition)
    {
        $vendor = 'horde'; // TODO: Make configurable for horde-like separately owned code
        $name = Horde_String::lower($package['name']);
        $composerDefinition->name = "$vendor/$name";
    }

    protected function _setType(Components_Wrapper_HordeYml $package, stdClass $composerDefinition)
    {
        if ($package['type'] == 'library') {
            $composerDefinition->type = 'horde-library';
        }
        if ($package['type'] == 'application') {
            $composerDefinition->type = 'horde-application';
        }
        if ($package['type'] == 'component') {
            $composerDefinition->type = 'horde-application';
        }
        // No type is perfectly valid for composer. Types for themes, bundles?
    }

    protected function _setAuthors(Components_Wrapper_HordeYml $package, stdClass $composerDefinition)
    {
        $composerDefinition->authors = array();
        foreach ($package['authors'] as $author) {
            $person = new stdClass();
            $person->name = $author['name'];
            $person->email = $author['email'];
            $person->role = $author['role'];
            array_push($composerDefinition->authors, $person);
        }
    }

    protected function _setAutoload(Components_Wrapper_HordeYml $package, stdClass $composerDefinition)
    {
        $composerDefinition->autoload = [];

        $name = $package['type'] == 'library' ? 'Horde_' . $package['name'] : $package['name'];
        if (!empty($package['autoload'])) {
            foreach ($package['autoload'] as $type => $definition) {
                if ($type == 'classmap') {
                    $composerDefinition->autoload['classmap']  =  $definition;
                }
                if ($type == 'psr-0') {
                    $composerDefinition->autoload['psr-0']  =  $definition;
                }
            }
        } else {
            $composerDefinition->autoload['psr-0']  = [$name  => 'lib/'];
        }
    }

    /**
     * Convert .horde.yml requirements to composer format
     *
     * References to the horde pear channel will be changed to composer vcs/github
     */
    protected function _setRequire(Components_Wrapper_HordeYml $package, stdClass $composerDefinition)
    {
        if (empty($package['dependencies']['required'])) {
            return;
        }
        $composerDefinition->require = array('horde/horde-installer-plugin' => '*');
        foreach ($package['dependencies']['required'] as $element => $required) {
            if ($element == 'pear') {
                foreach ($required as $pear => $version) {
                    list($repo, $basename) = explode('/', $pear);
                    // If it's on our substitute whitelist, convert to composer-native
                    if ($this->_substitute($pear, $version, $composerDefinition->require)) {
                        continue;
                    }
                    // If it's a horde pear component, rather use composer-native and add github vcs as repository
                    if ($repo == 'pear.horde.org') {
                        $vendor = 'horde';
                        if ($basename == 'horde') {
                            // the "horde" app lives in the "base" repo.
                            $repo = 'base';
                        } elseif(substr($basename, 0, 6) === 'Horde_') {
                            $basename = $repo = substr($basename, 6);
                        } else {
                            // regular app
                            $repo = $basename;
                        }
                        $this->_handleVersion($version, $composerDefinition->require, 'horde', $repo, $basename, $vendor);
                        continue;
                    }
                    if ($repo == 'pecl.php.net') {
                        $this->_handleVersion($version, $composerDefinition->require, 'ext', $repo, $basename);
                        continue;
                    }
                    // Else, require from pear and add pear as a source.
                    $this->_handleVersion($version, $composerDefinition->require, 'pear', $repo, $basename);
                }
            }
            if ($element == 'php') {
                $composerDefinition->require[$element] = $required;
            }
            if ($element == 'ext') {
               foreach ($required as $ext => $version) {
                    $this->_handleVersion($version, $composerDefinition->require, 'ext', $repo, $ext);
               }
            }
        }
    }

    // Deal with packages appropriately
    protected function _handleVersion($version, &$stack, $type, $repo, $basename, $vendor = '')
    {
        $ext = '';
        if (is_array($version)) {
            $ext = empty($version['providesextension']) ? '' : $version['providesextension'];
            $version = empty($version['version']) ? '*' : $version['version'];
        }
        if ($type == 'ext') {
            $ext = $basename;
        }
        if ($ext) {
            $stack['ext-' . $ext] = $version;
        } elseif ($type == 'pear') {
            $stack['pear-' . "$repo/$basename"] = $version;
            $this->_repositories['pear-' . $repo] = ['url' => 'https://' . $repo, 'type' => 'pear'];
        } else {
            // Most likely, this is always composer
            $stack[Horde_String::lower("$vendor/$basename")] = $version;
            // Developer mode - don't add horde vcs repos in releases, use packagist
            $this->_repositories["$vendor/$basename"] = ['url' => "https://github.com/$vendor/$repo", 'type' => 'vcs'];
        }
    }

    protected function _addPearRepo($pear)
    {
        $repo = substr($pear, 0, strrpos($pear, '/'));
        $this->_repositories['pear-' . $repo] = ['url' => 'https://' . $repo, 'type' => 'pear'];
    }

    /**
     * Convert .horde.yml suggestions to composer format
     *
     * References to the horde pear channel will be changed to composer vcs/github
     */
    protected function _setSuggest(Components_Wrapper_HordeYml $package, stdClass $composerDefinition)
    {
        $composerDefinition->suggest = array();
        if (empty($package['dependencies']['optional'])) {
            return;
        }
        foreach ($package['dependencies']['optional'] as $element => $suggested) {
            if ($element == 'pear') {
                foreach ($suggested as $pear => $version) {
                    list($repo, $basename) = explode('/', $pear);
                    // If it's on our substitute whitelist, convert to composer-native
                    if ($this->_substitute($pear, $version, $composerDefinition->suggest)) {
                        continue;
                    }
                    // If it's a horde pear component, rather use composer-native and add github vcs as repository
                    if ($repo == 'pear.horde.org') {
                        $vendor = 'horde';
                        if ($basename == 'horde') {
                            // the "horde" app lives in the "base" repo.
                            $repo = 'base';
                        } elseif(substr($basename, 0, 6) === 'Horde_') {
                            $basename = $repo = substr($basename, 6);
                        } else {
                            // regular app
                            $repo = $basename;
                        }
                        $this->_handleVersion($version, $composerDefinition->suggest, 'horde', $repo, $basename, $vendor);
                        continue;
                    }
                    if ($repo == 'pecl.php.net') {
                        $this->_handleVersion($version, $composerDefinition->suggest, 'ext', $repo, $basename);
                        continue;
                    }
                    // Else, take from pear and add pear as a source.
                    $this->_handleVersion($version, $composerDefinition->suggest, 'pear', $repo, $basename);
                }
            }
            if ($element == 'php') {
                $composerDefinition->suggest[$element] = $suggested;
            }
            if ($element == 'ext') {
               foreach ($suggested as $ext => $version) {
                    $this->_handleVersion($version, $composerDefinition->suggest, 'ext', $repo, $ext);
               }
            }
        }

    }

    // Handle the substitution list
    protected function _substitute($pear, $version, &$stack)
    {
        if (!empty($this->_substitutes[$pear])) {
            $stack[$this->_substitutes[$pear]['name']] = $version;
            if ($this->_substitutes[$pear]['source'] != 'Packagist')
            {
                throw new Components_Exception("Non-Packagist substitutes not yet implemented:" . $this->_substitutes[$pear]['source']);
            }
            return true;
        }
        return false;
    }

    protected function _setRepositories(Components_Wrapper_HordeYml $package, stdClass $composerDefinition)
    {
        $composerDefinition->repositories = array_values($this->_repositories);
    }
}
