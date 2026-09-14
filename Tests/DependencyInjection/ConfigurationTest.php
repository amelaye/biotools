<?php
namespace Tests\DependencyInjection;

use Amelaye\BioTools\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    public function testDefaultsAreUsedWhenNoConfigurationIsProvided()
    {
        $aDefaults = require __DIR__ . '/../../Resources/config/defaults.php';

        $processor = new Processor();
        $config = $processor->processConfiguration(new Configuration(), []);

        $this->assertEquals($aDefaults['nucleotids_graphs'], $config['nucleotids_graphs']);
        $this->assertEquals($aDefaults['protein_colors'], $config['protein_colors']);
    }

    public function testProvidedConfigurationOverridesDefaults()
    {
        $processor = new Processor();
        $config = $processor->processConfiguration(new Configuration(), [
            'amelaye_biotools' => [
                'nucleotids_graphs' => ['path_graphs' => '/custom/path/'],
            ],
        ]);

        // variableNode: an override entirely replaces the default value, it does not merge
        $this->assertEquals(['path_graphs' => '/custom/path/'], $config['nucleotids_graphs']);

        $aDefaults = require __DIR__ . '/../../Resources/config/defaults.php';
        $this->assertEquals($aDefaults['protein_colors'], $config['protein_colors']);
    }
}
