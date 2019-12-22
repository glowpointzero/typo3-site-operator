<?php
namespace Glowpointzero\SiteOperator\SiteCheckup\Processors;

use Glowpointzero\SiteOperator\Console\MessageCollector;
use Symfony\Component\Console\Input\InputInterface;
use Glowpointzero\SiteOperator\Console\SymfonyStyle;

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
     * @param MessageCollector $messageCollector
     */
    public function setMessageCollector(MessageCollector &$messageCollector);

    /**
     * @return int
     */
    public function run();
}
