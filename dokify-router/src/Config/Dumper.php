<?php

namespace Dokify\Router\Config;

use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class Dumper
{
    /**
     * @var Finder
     */
    private $finder;

    /**
     * @var []
     */
    private $parameters;

    /**
     * @var string
     */
    private $parametersYaml;

    public function __construct(
        Finder $finder,
        string $parametersYaml
    ) {
        $this->finder = $finder;
        $this->parametersYaml = $parametersYaml;

        if (false === file_exists($this->parametersYaml)) {
            throw new \InvalidArgumentException('no existe el archivo '. $this->parametersYaml);
        }
    }

    public function dump(
        string $cacheDir,
        bool $debug = true
    ) {
        foreach ($this->finder as $fileInfo) {
            $this->createCreateOrRefreshOrSkip($cacheDir, $fileInfo, $debug);
        }
    }

    private function createCreateOrRefreshOrSkip(
        string $cacheDir,
        \SplFileInfo $fileInfo,
        bool $debug
    ) {
        $filePath = $fileInfo->getRealPath();
        $cache = new ConfigCache($cacheDir . '/'. $fileInfo->getFilename(), $debug);

        if (false === $cache->isFresh()) {
            $content = file_get_contents($filePath);

            foreach ($this->getParameters() as $parameter => $value) {
                $content = str_replace(
                    '%'.$parameter.'%',
                    $value,
                    $content
                );
            }

            $cache->write($content, [new FileResource($filePath)]);
        }
    }

    private function getParameters()
    {
        if (null === $this->parameters) {
            $yamlParser = new Yaml();
            $this->parameters = $yamlParser->parse(file_get_contents($this->parametersYaml));
        }

        return $this->parameters['parameters'];
    }
}
