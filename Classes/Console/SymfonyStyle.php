<?php
namespace Glowpointzero\SiteOperator\Console;

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

use Glowpointzero\SiteOperator\Command\AbstractCommand;

class SymfonyStyle extends \Symfony\Component\Console\Style\SymfonyStyle
{
    const MAX_LINE_LENGTH = 80;

    const STYLE_DEFAULT = 'fg=default;bg=default';
    const STYLE_SUCCESS = 'fg=black;bg=green';
    const STYLE_NOTICE = 'fg=yellow';
    const STYLE_WARNING = 'fg=yellow';
    const STYLE_ERROR = 'bg=red;fg=white';

    protected $processStartMessageHasBeenPosted = false;
    protected $processStartMessageLevel = 0;


    /**
     * @param string $message
     * @param string $style
     * @return string
     */
    public function wrapTextInStyle($message, $style)
    {
        return sprintf('<%s>%s</>', $style, $message);
    }

    /**
     * @param string $message
     * @param int $indentLevel
     * @return string
     */
    public function indent(string $message, $indentLevel = 1)
    {
        return str_pad('', $indentLevel*2, ' ') . $message;
    }

    /**
     * @param string $text
     * @param $lineCount
     * @return string
     */
    public function wordWrap($text, &$lineCount = null)
    {
        $indentCount = strlen($text)-strlen(ltrim($text));
        $text = trim($text);

        $text = wordwrap($text, static::MAX_LINE_LENGTH-$indentCount, PHP_EOL, true);
        $lines = explode(PHP_EOL, $text);
        $lineCount = count($lines);

        foreach ($lines as $lineIndex => $line) {
            $lines[$lineIndex] = str_pad('', $indentCount, ' ') . $line;
        }
        return implode(PHP_EOL, $lines);
    }

    /**
     * @param string $message
     * @param int $processLevel
     */
    public function startProcess($message, $processLevel = 0)
    {
        $this->abortCurrentProcess();
        $this->processStartMessageLevel = $processLevel;
        $message = $this->wordWrap(
            $this->indent($message, $processLevel),
            $lineCount
        );
        $message = rtrim($message, ' .!:,');
        $this->write($message . ' ... ');
        $this->processStartMessageHasBeenPosted = true;
    }


    /**
     * Creates a new line, "aborting" the current process message
     * (i.e. not waiting for it to finish and continuing outputting
     * messages).
     */
    public function abortCurrentProcess()
    {
        if ($this->processStartMessageHasBeenPosted) {
            $this->newLine();
        }
        $this->processStartMessageHasBeenPosted = false;
        $this->processStartMessageLevel = 0;
    }


    /**
     * @param string $message
     * @param int $messageSeverity
     */
    public function endProcess($message = '', $messageSeverity = AbstractCommand::STATUS_SUCCESS)
    {
        if (!$this->processStartMessageHasBeenPosted) {
            return;
        }
        $this->processStartMessageHasBeenPosted = false;

        switch ($messageSeverity) {
            case AbstractCommand::STATUS_NOTICE:
                $statusSymbol = '🛈';
                $statusSymbolStyle = 'fg=yellow';
                $messageStyle = self::STYLE_NOTICE;
                break;
            case AbstractCommand::STATUS_SUCCESS:
                $statusSymbol = '✓';
                $statusSymbolStyle = 'fg=green';
                $messageStyle = self::STYLE_SUCCESS;
                break;
            case AbstractCommand::STATUS_ERROR:
                $statusSymbol = '❌';
                $statusSymbolStyle = 'fg=red';
                $messageStyle = self::STYLE_ERROR;
                break;
            case AbstractCommand::STATUS_WARNING:
                $statusSymbol = '❗';
                $statusSymbolStyle = 'fg=yellow';
                $messageStyle = self::STYLE_WARNING;
                break;
            default:
                $statusSymbol = '';
                $statusSymbolStyle = self::STYLE_DEFAULT;
                $messageStyle = self::STYLE_DEFAULT;
                break;
        }

        $addTimeoutAfterOutput = $messageSeverity === AbstractCommand::STATUS_WARNING || $messageSeverity === AbstractCommand::STATUS_ERROR;
        $this->outputProcessFinishMessage($message, $messageStyle, $statusSymbol, $statusSymbolStyle, $addTimeoutAfterOutput);
        $this->processStartMessageLevel = 0;
    }

    /**
     * @param string $message
     * @param string $messageStyle
     * @param string $statusSymbol
     * @param string $statusSymbolStyle
     * @param bool $addTimeoutAfterOutput
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    protected function outputProcessFinishMessage($message, $messageStyle, $statusSymbol, $statusSymbolStyle, $addTimeoutAfterOutput = false)
    {
        if ($statusSymbol) {
            $this->write($this->wrapTextInStyle($statusSymbol . ' ', $statusSymbolStyle));
        }

        if (strlen($message) < 20) {
            $this->write($this->wrapTextInStyle($message, $statusSymbolStyle), true);
            if ($addTimeoutAfterOutput) {
                sleep(2);
            }
            return;
        }

        if ($message) {
            $this->newLine();
            $message = $this->wordWrap($this->indent($message, $this->processStartMessageLevel));
            $this->write($this->wrapTextInStyle($message, $messageStyle), true);
            if ($addTimeoutAfterOutput) {
                sleep(2);
            }
        }
    }

    /**
     * @param string $message
     * @param int $severity
     * @param int $indentLevel
     */
    public function say($message, $severity = AbstractCommand::STATUS_OK, $indentLevel = 0)
    {
        $message = $this->indent($message, $indentLevel);

        switch ($severity) {
            case AbstractCommand::STATUS_SUCCESS:
                $this->success($message);
                break;
            case AbstractCommand::STATUS_NOTICE:
                $this->notice($message);
                break;
            case AbstractCommand::STATUS_WARNING:
                $this->warning($message);
                break;
            case AbstractCommand::STATUS_ERROR:
                $this->error($message);
                break;
            default:
                $this->info($message);
                break;
        }
    }

    /**
     * @param $message
     * @param int $severity
     * @param int $indentLevel
     */
    public function sayVerbosely($message, $severity = AbstractCommand::STATUS_INFO, $indentLevel = 0)
    {
        if (!$this->isVerbose()) {
            return;
        }
        $message = $this->wordWrap(
            $this->indent($message, $indentLevel),
            $lineCount
        );
        $this->abortCurrentProcess();
        $this->say($message, $severity);
    }

    /**
     * {@inheritdoc}
     */
    public function info($message)
    {
        $this->writeln($this->wrapTextInStyle($this->wordWrap($message), self::STYLE_DEFAULT));
    }

    /**
     * {@inheritdoc}
     */
    public function note($message)
    {
        $this->info($message);
    }

    /**
     * {@inheritdoc}
     */
    public function comment($message)
    {
        $this->info($message);
    }

    /**
     * {@inheritdoc}
     */
    public function notice($message)
    {
        $this->writeln($this->wrapTextInStyle($this->wordWrap($message), self::STYLE_NOTICE));
    }

    /**
     * {@inheritdoc}
     */
    public function caution($message)
    {
        $this->warning($message);
    }

    /**
     * {@inheritdoc}
     */
    public function warning($message)
    {
        $this->writeln($this->wrapTextInStyle($this->wordWrap($message), self::STYLE_WARNING));
    }

    /**
     * {@inheritdoc}
     */
    public function success($message)
    {
        $this->writeln($this->wrapTextInStyle($this->wordWrap($message), self::STYLE_SUCCESS));
    }

    /**
     * {@inheritdoc}
     */
    public function error($message)
    {
        $this->writeln($this->wrapTextInStyle($this->wordWrap($message), self::STYLE_ERROR));
    }
}
