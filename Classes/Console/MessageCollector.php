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

class MessageCollector {
    /**
     * @var int
     */
    protected $mostSevereMessageLevel = AbstractCommand::STATUS_OK;

    /**
     * @var array
     */
    protected $messages = [];

    /**
     * @param $message
     * @param int $severity
     * @param int $indentLevel
     */
    public function addMessage($message, $severity = 0, $indentLevel = 0)
    {
        $this->messages[] = [
            'message' => $message,
            'severity' => $severity,
            'indentLevel' => $indentLevel
        ];
        if ($this->mostSevereMessageLevel < $severity) {
            $this->mostSevereMessageLevel = $severity;
        }
    }

    /**
     * Merges all messages from another MessageCollector instance
     *
     * @param MessageCollector $messageCollector
     * @param int $indentLevel
     */
    public function mergeMessagesFrom(MessageCollector $messageCollector, $indentLevel = 0)
    {
        foreach ($messageCollector->getMessages() as $message) {
            $this->addMessage($message['message'], $message['severity'], $indentLevel);
        }
    }

    /**
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * @return int
     */
    public function getMostSevereMessageLevel()
    {
        return $this->mostSevereMessageLevel;
    }

    /**
     * @return array
     */
    public function getRenderedMessages()
    {
        $renderedMessages = [];
        foreach ($this->getMessages() as $message) {
            $renderedMessages[] = $this->renderMessage($message);
        }
        return $renderedMessages;
    }

    /**
     * @param array $message
     * @return string
     */
    public function renderMessage(array $message)
    {
        $indents = str_repeat('  ', isset($message['indentLevel']) ? $message['indentLevel'] : 0);

        $renderedMessage = sprintf(
            '%s%s',
            $indents,
            $message['message']
        );

        return $renderedMessage;
    }

    /**
     * Wrapper method for 'info'-level messages.
     *
     * @param $message
     */
    public function addInfo($message)
    {
        $this->addMessage($message, AbstractCommand::STATUS_INFO);
    }

    /**
     * Wrapper method for 'notice'-level messages.
     *
     * @param $message
     */
    public function addNotice($message)
    {
        $this->addMessage($message, AbstractCommand::STATUS_NOTICE);
    }

    /**
     * Wrapper method for 'warning'-level messages.
     *
     * @param $message
     */
    public function addWarning($message)
    {
        $this->addMessage($message, AbstractCommand::STATUS_WARNING);
    }

    /**
     * Wrapper method for 'error'-level messages.
     *
     * @param $message
     */
    public function addError($message)
    {
        $this->addMessage($message, AbstractCommand::STATUS_ERROR);
    }

    /**
     * @return bool
     */
    public function hasErrors()
    {
        $messageLevel = $this->getMostSevereMessageLevel();
        $errorLevel = SymfonyStyle::MESSAGE_SEVERITY_ERROR;

        return ((int)$messageLevel <= (int)$errorLevel) ? true : false;
    }
}
