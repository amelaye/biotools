<?php
namespace Tests\DependencyInjection;

use Amelaye\BioTools\DependencyInjection\AmelayeBioToolsExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AmelayeBioToolsExtensionTest extends TestCase
{
    public function testGetAlias()
    {
        $extension = new AmelayeBioToolsExtension();

        $this->assertEquals('amelaye_biotools', $extension->getAlias());
    }

    public function testLoadSetsDefaultParameters()
    {
        $aDefaults = require __DIR__ . '/../../Resources/config/defaults.php';

        $container = new ContainerBuilder();
        $extension = new AmelayeBioToolsExtension();
        $extension->load([], $container);

        $this->assertEquals($aDefaults['nucleotids_graphs'], $container->getParameter('amelaye_biotools.nucleotids_graphs'));
        $this->assertEquals($aDefaults['protein_colors'], $container->getParameter('amelaye_biotools.protein_colors'));
    }

    public function testLoadSetsParametersFromProvidedConfig()
    {
        $container = new ContainerBuilder();
        $extension = new AmelayeBioToolsExtension();
        $extension->load([
            ['nucleotids_graphs' => ['path_graphs' => '/custom/path/']],
        ], $container);

        $this->assertEquals(['path_graphs' => '/custom/path/'], $container->getParameter('amelaye_biotools.nucleotids_graphs'));
    }

    public function testLoadRegistersServicesFromServicesXml()
    {
        $container = new ContainerBuilder();
        $extension = new AmelayeBioToolsExtension();
        $extension->load([], $container);

        $this->assertTrue($container->hasDefinition('Amelaye\BioTools\Service\ChaosGameRepresentationManager'));
        $this->assertTrue($container->hasDefinition('Amelaye\BioTools\Service\DnaToProteinManager'));
        $this->assertTrue($container->hasDefinition('Amelaye\BioTools\Service\RestrictionDigestManager'));
    }
}
