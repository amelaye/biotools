<?php
/**
 * Dependency injections for the bundle
 * Created 24 august 2026
 * Last modified 24 august 2026
 */
namespace Amelaye\BioTools\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

/**
 * Class AmelayeBioToolsExtension
 * @package Amelaye\BioTools\DependencyInjection
 * @author Amélie DUVERNET aka Amelaye <amelieonline@gmail.com>
 */
class AmelayeBioToolsExtension extends Extension
{
    /**
     * Loads the BioTools forms, managers and validators
     * @param array $configs
     * @param ContainerBuilder $container
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('amelaye_biotools.nucleotids_graphs', $config['nucleotids_graphs']);
        $container->setParameter('amelaye_biotools.protein_colors', $config['protein_colors']);
    }

    /**
     * @return string
     */
    public function getAlias(): string
    {
        return 'amelaye_biotools';
    }
}
