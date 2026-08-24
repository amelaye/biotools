<?php
/**
 * Configuration tree for the bundle
 * Created 24 august 2026
 * Last modified 24 august 2026
 */
namespace Amelaye\BioTools\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration
 * @package Amelaye\BioTools\DependencyInjection
 * @author Amélie DUVERNET aka Amelaye <amelieonline@gmail.com>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * Both settings are declared as variable nodes on purpose: they are colour and
     * geometry lookup tables whose keys are data (amino acid letters, palette names
     * such as "20", "Murphy15" or "3IMG"), not a fixed schema a typed tree could
     * describe. Defaults come from Resources/config/defaults.php so that the bundle
     * is usable without any configuration.
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $aDefaults = require __DIR__ . '/../Resources/config/defaults.php';

        $treeBuilder = new TreeBuilder('amelaye_biotools');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->variableNode('nucleotids_graphs')
                    ->info('Geometry, file names and output directory used to render CGR/FCGR and skew images.')
                    ->defaultValue($aDefaults['nucleotids_graphs'])
                ->end()
                ->variableNode('protein_colors')
                    ->info('Colour palettes used when rendering reduced protein alphabets.')
                    ->defaultValue($aDefaults['protein_colors'])
                ->end()
            ->end();

        return $treeBuilder;
    }
}
