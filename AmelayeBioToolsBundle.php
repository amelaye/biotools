<?php
/**
 * Bundle initialisation
 * Created 24 august 2026
 * Last modified 24 august 2026
 */
namespace Amelaye\BioTools;

use Amelaye\BioTools\DependencyInjection\AmelayeBioToolsExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

/**
 * Class AmelayeBioToolsBundle
 * @package Amelaye\BioTools
 * @author Amélie DUVERNET aka Amelaye <amelieonline@gmail.com>
 */
class AmelayeBioToolsBundle extends Bundle
{
    /**
     * @return ExtensionInterface|null
     */
    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null == $this->extension) {
            $this->extension = new AmelayeBioToolsExtension();
        }
        return $this->extension;
    }
}
