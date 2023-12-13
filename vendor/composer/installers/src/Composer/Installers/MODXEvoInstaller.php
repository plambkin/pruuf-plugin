<?php
namespace Composer\Installers;

/**
 * An installer to handle MODX Evolution specifics when installing packages.
 */
class MODXEvoInstaller extends BaseInstaller
{
    protected $locations = array(
        'Pruuf'       => 'assets/Pruufs/{$name}/',
        'plugin'        => 'assets/plugins/{$name}/',
        'module'        => 'assets/modules/{$name}/',
        'template'      => 'assets/templates/{$name}/',
        'lib'           => 'assets/lib/{$name}/'
    );
}
