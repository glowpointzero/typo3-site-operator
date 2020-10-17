<?php
namespace Glowpointzero\SiteOperator\Mail;

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

use mysql_xdevapi\Exception;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\TemplatePaths;
use Symfony\Component\Mime\Part\AbstractPart;
use Symfony\Component\Mime\Header\Headers;

class EmailMessage extends \TYPO3\CMS\Core\Mail\FluidEmail implements \Psr\Log\LoggerAwareInterface
{
    use \Psr\Log\LoggerAwareTrait;

    /**
     * Holds the main content, when it's assigned via $this->assign
     * or $this->assignMultiple for later rendering and assignment
     * just before the render call.
     *
     * @var string
     */
    protected $plainTextContent = '';
    protected $htmlContent = '';

    protected static $defaultEmbeddables = ['all_sites' => []];
    protected $embeddables = [];

    protected static $defaultCssFilePath = ['all_sites' => ''];
    protected $cssFilePath = '';

    protected static $additionalDefaultVariables = [];


    public function __construct(TemplatePaths $templatePaths = null, Headers $headers = null, AbstractPart $body = null)
    {
        $this->setLogger(GeneralUtility::makeInstance(\TYPO3\CMS\Core\Log\LogManager::class)->getLogger(__CLASS__));
        parent::__construct($templatePaths, $headers, $body);
    }

    /**
     * Extends the default 'initializeView' method.
     *
     * @param TemplatePaths|null $templatePaths
     * @throws \Exception
     */
    protected function initializeView(TemplatePaths $templatePaths = null): void
    {
        parent::initializeView($templatePaths);

        $siteIdentifier = \Glowpointzero\SiteOperator\ProjectInstance::getSiteIdentifier();

        foreach (self::$defaultEmbeddables['all_sites'] as $defaultEmbeddableIdentifier => $defaultEmbeddableFilePath) {
            $this->addEmbeddable($defaultEmbeddableIdentifier, $defaultEmbeddableFilePath);
        }
        if ($siteIdentifier && isset(self::$defaultEmbeddables[$siteIdentifier])) {
            foreach (self::$defaultEmbeddables[$siteIdentifier] as $defaultEmbeddableIdentifier => $defaultEmbeddableFilePath) {
                $this->addEmbeddable($defaultEmbeddableIdentifier, $defaultEmbeddableFilePath);
            }
        }

        $cssFilePath = self::$defaultCssFilePath['all_sites'];
        if ($siteIdentifier && isset(self::$defaultCssFilePath[$siteIdentifier])) {
            $cssFilePath = self::$defaultCssFilePath[$siteIdentifier];
        }
        $this->setCssFilePath($cssFilePath);
    }

    /**
     * @param string $variableName
     * @param $variableValue
     */
    public static function addDefaultVariable(string $variableName, $variableValue)
    {
        self::$additionalDefaultVariables[$variableName] = $variableValue;
    }

    /**
     * @return array
     */
    protected function getDefaultVariables(): array
    {
        $variables = array_merge_recursive(
            parent::getDefaultVariables(),
            self::$additionalDefaultVariables
        );
        return $variables;
    }

    /**
     * Adds an image file to be embeddable in the fluid view.
     *
     * @param string $embeddableIdentifier Reference string to later identify the embeddable in the template.
     * @param string $filePath                Original file path.
     * @param string $limitedToSiteIdentifier Site key to limit this as a default embeddable to.
     */
    public static function addDefaultEmbeddable(string $embeddableIdentifier, string $filePath, string $limitedToSiteIdentifier = '')
    {
        $siteKey = $limitedToSiteIdentifier ? $limitedToSiteIdentifier : 'all_sites';
        self::$defaultEmbeddables[$siteKey][$embeddableIdentifier] = $filePath;
    }

    /**
     * Adds an embeddable for this instance.
     *
     * @param string $embeddableIdentifier
     * @param string $filePath
     * @return bool
     */
    public function addEmbeddable(string $embeddableIdentifier, string $filePath)
    {
        $absoluteFilePath = GeneralUtility::getFileAbsFileName($filePath);
        if (!is_file($absoluteFilePath)) {
            $this->logger->critical(
                sprintf(
                    'Given path for the embeddable "%s" (%s) does not exist.',
                    $embeddableIdentifier,
                    $filePath
                )
            );
            return false;
        }
        $this->embeddables[$embeddableIdentifier] = $absoluteFilePath;
        return true;
    }

    /**
     * @param string $cssFilePath
     * @param string $limitedToSiteIdentifier
     */
    public static function setDefaultCssFilePath(string $cssFilePath, string $limitedToSiteIdentifier = '')
    {
        $siteKey = $limitedToSiteIdentifier ? $limitedToSiteIdentifier : 'all_sites';
        self::$defaultCssFilePath[$siteKey] = $cssFilePath;
    }

    /**
     * @param string $cssFilePath
     * @return bool
     */
    public function setCssFilePath(string $cssFilePath)
    {
        $absoluteFilePath = GeneralUtility::getFileAbsFileName($cssFilePath);
        if (!is_file($absoluteFilePath)) {
            $this->logger->critical(
                sprintf(
                    'Given path for the stylesheet (%s) does not exist.',
                    $cssFilePath
                )
            );
            return false;
        }
        $this->cssFilePath = $cssFilePath;
        return true;
    }

    /**
     * Overwrites the default rendering of a content part
     * (html and/or plain text). Main differences:
     * - Assigns given 'subject' to the template variables.
     * - Re-renders the 'content' from the $this->>assign
     *   or $this->assignMultiple call that must have
     *   happened previously.
     * - Provides the embeddables and CSS for the
     *   HTML version.
     *
     * @param string $format
     * @return string
     */
    protected function renderContent(string $format): string
    {
        $this->view->setFormat($format);
        $this->view->setTemplate($this->templateName);

        $this->view->assign('subject', $this->getSubject());
        $this->view->assign('content', $this->getContent($format));

        // Assign and re-assign contents, if we're rendering the HTML version.
        if ($format === EmailMessage::FORMAT_HTML) {
            $embeddables = [];
            foreach ($this->embeddables as $embeddableIdentifier => $embeddableFilePath) {
                $this->embedFromPath($embeddableFilePath, $embeddableIdentifier);
                $embeddables[$embeddableIdentifier] = 'cid:' . $embeddableIdentifier;
            }
            $this->view->assignMultiple(
                [
                    'embeddables' => $embeddables,
                    'css' => empty($this->cssFilePath) ? '' : file_get_contents(GeneralUtility::getFileAbsFileName($this->cssFilePath))
                ]
            );
        }

        return $this->view->render();
    }

    protected function getContent($format) {
        if ($format === EmailMessage::FORMAT_HTML) {
            return $this->htmlContent;
        }
        return $this->plainTextContent;
    }

    /**
     * Catches 'assign' calls that include a 'content' variable,
     * which it'll interpret as main content, assign it to the
     * local property and re-assigns it to the view later,
     * strictly interpreting its contents as text or html
     * (depending on the content type).
     *
     * @see assignMultiple
     * @see renderContent
     *
     * @param $key
     * @param $value
     * @return EmailMessage|void
     */
    public function assign($key, $value) {
        parent::assign($key, $value);
        if ($key === 'content') {
            $this->setContent($value);
        }
    }

    /**
     * @see assignMultiple
     * @see renderContent
     *
     * @param array $values
     * @return EmailMessage|void
     */
    public function assignMultiple($values) {
        parent::assignMultiple($values);
        if (isset($values['content'])) {
            $this->setContent($values['content']);
        }
    }

    /**
     * @param $contentValue
     */
    protected function setContent($contentValue) {
        if ($this->stringLooksLikeHtml($contentValue)) {
            $this->htmlContent = $contentValue;
            if (!$this->plainTextContent) {
                $this->plainTextContent = $this->getPlainTextContentFromHtml($contentValue);
            }
            return;
        }
        $this->plainTextContent = $contentValue;
        if (!$this->htmlContent) {
            $this->htmlContent = $this->getHtmlContentFromText($contentValue);
        }
    }

    /**
     * Utility method to detect HTML input.
     * @TODO: Make sure cases don't return false positives
     * @TODO: when non-html input starts with '<'.
     *
     * @param string $string
     * @return bool
     */
    protected function stringLooksLikeHtml(string $string)
    {
        return substr(trim($string), 0, 1) === '<';
    }

    /**
     * Utility method to convert html content to plain text
     * very roughly. This is intended for conversion
     * of text of low complexity (p.e. for email messages)
     * @TODO: Extend this method to handle lists, titles, etc.
     *
     * @see getHtmlContentFromText
     * @param string $htmlContent
     * @return string
     */
    protected function getPlainTextContentFromHtml(string $htmlContent)
    {
        $plainText = str_replace('</tr>', PHP_EOL, $htmlContent);
        $plainText = str_replace('</td><td>', ': ', $plainText);
        $plainText = strip_tags($plainText);

        return $plainText;
    }

    /**
     * Utility method to convert plain text content
     * to HTML very roughly. This is intended for conversion
     * of text of low complexity (p.e. for email messages).
     * @TODO: Maybe we should extend this?
     *
     * @see getPlainTextContentFromHtml
     * @param string $plainTextContent
     * @return string
     */
    public function getHtmlContentFromText(string $plainTextContent)
    {
        if (self::stringLooksLikeHtml($plainTextContent)) {
            return $plainTextContent;
        }

        $htmlContent = '<p>' . nl2br($plainTextContent) . '</p>';
        return $htmlContent;
    }
}
