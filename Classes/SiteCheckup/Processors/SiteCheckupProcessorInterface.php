<?php
namespace Glowpointzero\SiteOperator\SiteCheckup\Processors;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

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
