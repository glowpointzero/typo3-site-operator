<?php
namespace Glowpointzero\SiteOperator\SiteCheckup\Processors;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

interface SiteCheckupProcessorInterface {
    /**
     * @param string $identifier
     */
    public function setIdentifier(string $identifier);

    /**
     * @param array $configuration
     */
    public function setConfiguration(array $configuration);

    /**
     * @param InputInterface $input
     */
    public function setInputInterface(InputInterface $input);

    /**
     * @param SymfonyStyle $io
     */
    public function setSymfonyStyle(SymfonyStyle $io);

    /**
     * @return bool
     */
    public function run();
}
