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

use TYPO3\CMS\Core\Utility\GeneralUtility;

class MailMessage extends \TYPO3\CMS\Core\Mail\MailMessage implements \Psr\Log\LoggerAwareInterface
{
    use \Psr\Log\LoggerAwareTrait;

    protected $htmlContent = '';
    protected $plainTextContent = '';

    protected static $defaultEmbeddables = ['all_sites' => []];
    protected $embeddables = [];

    protected static $defaultPlainTextTemplatePathAndFilename = ['all_sites' => ''];
    protected $plainTextPathAndFilename = '';
    
    protected static $defaultHtmlTemplatePathAndFilename = ['all_sites' => ''];
    protected $htmlTemplatePathAndFilename = '';

    protected static $defaultCssFilePath = ['all_sites' => ''];
    protected $cssFilePath = '';

    /**
     * @inheritdoc
     */
    public function __construct(
        string $subject = null,
        string $body = null,
        string $contentType = null,
        string $charset = null
    ) {
        $siteIdentifier = \Glowpointzero\SiteOperator\ProjectInstance::getSiteIdentifier();
        $this->setLogger(GeneralUtility::makeInstance(\TYPO3\CMS\Core\Log\LogManager::class)->getLogger(__CLASS__));

        foreach (self::$defaultEmbeddables['all_sites'] as $defaultEmbeddableIdentifier => $defaultEmbeddableFilePath) {
            $this->addEmbeddable($defaultEmbeddableIdentifier, $defaultEmbeddableFilePath);
        }
        if ($siteIdentifier && isset(self::$defaultEmbeddables[$siteIdentifier])) {
            foreach (self::$defaultEmbeddables[$siteIdentifier] as $defaultEmbeddableIdentifier => $defaultEmbeddableFilePath) {
                $this->addEmbeddable($defaultEmbeddableIdentifier, $defaultEmbeddableFilePath);
            }
        }

        $plainTextTemplate = self::$defaultPlainTextTemplatePathAndFilename['all_sites'];
        if ($siteIdentifier && isset(self::$defaultPlainTextTemplatePathAndFilename[$siteIdentifier])) {
            $plainTextTemplate = self::$defaultPlainTextTemplatePathAndFilename[$siteIdentifier];
        }
        $htmlTemplate = self::$defaultHtmlTemplatePathAndFilename['all_sites'];
        if ($siteIdentifier && isset(self::$defaultHtmlTemplatePathAndFilename[$siteIdentifier])) {
            $htmlTemplate = self::$defaultHtmlTemplatePathAndFilename[$siteIdentifier];
        }
        $this->setTemplatePathAndFilename($plainTextTemplate, $htmlTemplate);

        $cssFilePath = self::$defaultCssFilePath['all_sites'];
        if ($siteIdentifier && isset(self::$defaultCssFilePath[$siteIdentifier])) {
            $cssFilePath = self::$defaultCssFilePath[$siteIdentifier];
        }
        $this->setCssFilePath($cssFilePath);

        parent::__construct($subject, $body, $contentType, $charset);
    }

    /**
     * @return bool
     */
    protected function isSetUpToRenderFluidTemplates()
    {
        return !empty($this->plainTextPathAndFilename) && !empty($this->htmlTemplatePathAndFilename);
    }

    /**
     * Sets the email template root path, at the same time
     * activating this feature. If the template root path
     * is empty, the MailMessage class behaves exactly like
     * in the core.
     *
     * @param string $plainTextAndFilename
     * @param string $htmlPathAndFilename
     * @param string $limitedToSiteIdentifier
     */
    public static function setDefaultTemplatePathAndFilename(string $plainTextAndFilename, string $htmlPathAndFilename, string $limitedToSiteIdentifier = '')
    {
        $siteKey = $limitedToSiteIdentifier ? $limitedToSiteIdentifier : 'all_sites';
        self::$defaultPlainTextTemplatePathAndFilename[$siteKey] = $plainTextAndFilename;
        self::$defaultHtmlTemplatePathAndFilename[$siteKey] = $htmlPathAndFilename;
    }

    /**
     * Sets the template paths for this instance.
     *
     * @param string $plainTextPathAndFilename
     * @param string $htmlPathAndFilename
     * @return bool
     */
    public function setTemplatePathAndFilename(string $plainTextPathAndFilename, string $htmlPathAndFilename)
    {
        if (!is_file(GeneralUtility::getFileAbsFileName($plainTextPathAndFilename))) {
            $this->logger->critical(
                sprintf(
                    'Given path for the plain text email template (%s) does not exist. Falling back to the default behavior.',
                    $plainTextPathAndFilename
                )
            );
            return false;
        }
        if (!is_file(GeneralUtility::getFileAbsFileName($htmlPathAndFilename))) {
            $this->logger->critical(
                sprintf(
                    'Given path for the html email template (%s) does not exist. Falling back to the default behavior.',
                    $htmlPathAndFilename
                )
            );
            return false;
        }

        $this->plainTextPathAndFilename = $plainTextPathAndFilename;
        $this->htmlTemplatePathAndFilename = $htmlPathAndFilename;

        return true;
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
     * 'setBody' override, automagically detecting whether
     * this instance is prepared to be rendered via Fluid
     * and, secondly, also detecting whether the given
     * content is plain text or HTML.
     *
     * @param mixed $body
     * @param null $contentType
     * @param null $charset
     * @return \TYPO3\CMS\Core\Mail\MailMessage
     */
   public function setBody($body, $contentType = null, $charset = null)
   {
       if (!$this->isSetUpToRenderFluidTemplates()) {
           return parent::setBody($body, $contentType, $charset);
       }

       $contentType = !is_null($contentType) ? $contentType : 'text/plain';
       if ($this->stringLooksLikeHtml($body)) {
           $contentType = 'text/html';
       }
       if ($contentType === 'text/plain') {
           $this->setPlainTextContent($body);
           $this->setHtmlContent($this->getHtmlContentFromText($body));
       }
       if ($contentType === 'text/html') {
           $this->setHtmlContent($body);
           $this->setPlainTextContent($this->getPlainTextContentFromHtml($body));
       }

       return $this->addPart($body, $contentType, $charset);
   }

    /**
     * Intercepts calls to 'addPart', redirecting plain text
     * and html content into the respective properties of this
     * class for later processing via fluid.
     * If no specific content type is given, this method detects
     * whether it is html or plain text and extends both
     * containers.
     *
     * @param string|Swift_OutputByteStream $part
     * @param null $contentType
     * @param null $charset
     * @return $this|\TYPO3\CMS\Core\Mail\MailMessage
     */
   public function addPart($part, $contentType = null, $charset = null)
   {
       if (!$this->isSetUpToRenderFluidTemplates()) {
           return parent::addPart($part, $contentType, $charset);
       }

       if (is_string($part) && ($contentType === 'text/plain' || !$this->stringLooksLikeHtml($part))) {
           $this->addPlainTextContent($part);
           if (is_null($contentType)) {
               $this->addPlainTextContent($this->getPlainTextContentFromHtml($part));
           }
           return $this;
       }
       if (is_string($part) && ($contentType === 'text/html') || $this->stringLooksLikeHtml($part)) {
           $this->addHtmlContent($part);
           if (is_null($contentType)) {
               $this->addPlainTextContent($this->getPlainTextContentFromHtml($part));
           }
           return $this;
       }
       // In case we're not processing plain text or html content, fall back to
       // the original 'addPart' method from the parent class.
       return parent::addPart($part, $contentType, $charset);
   }

    /**
     * Utility method to detect HTML input.
     *
     * @param string $string
     * @return bool
     */
   protected function stringLooksLikeHtml(string $string)
   {
       return substr(trim($string), 0, 1) === '<';
   }

    /**
     * Utility method to convert plain text content
     * to HTML.
     *
     * @param string $plainTextContent
     * @return string
     */
    public function getHtmlContentFromText(string $plainTextContent)
   {
       $htmlContent = '<p>' . nl2br($plainTextContent) . '</p>';
       return $htmlContent;
   }

    /**
     * @param string $htmlContent
     */
    public function setHtmlContent(string $htmlContent)
    {
        $this->htmlContent = $htmlContent;
    }

    /**
     * @param string $htmlContent
     */
    public function addHtmlContent(string $htmlContent)
    {
        $this->htmlContent .= $htmlContent;
    }

    /**
     * Utility method to convert html content
     * to plain text.
     *
     * @param string $htmlContent
     * @return string
     */
    public function getPlainTextContentFromHtml(string $htmlContent)
    {
        $plainText = str_replace('</tr>', PHP_EOL, $htmlContent);
        $plainText = str_replace('</td><td>', ': ', $plainText);
        $plainText = strip_tags($plainText);

        return $plainText;
    }

    /**
     * @param string $plainText
     */
    public function setPlainTextContent(string $plainText)
    {
        $this->plainTextContent = $plainText;
    }

    /**
     * @param string $plainTextContent
     */
    public function addPlainTextContent(string $plainTextContent)
    {
        $this->plainTextContent .= $plainTextContent;
    }

    /**
     * Overrides the original 'send()' method, pre-processing
     * the current content via fluid.
     *
     * @return int
     */
    public function send()
    {
        $this->renderContentsViaFluid();
        return parent::send();
    }

    /**
     * Processes all contents (html and plain text) using
     * the given
     */
    protected function renderContentsViaFluid()
    {
        if (!$this->isSetUpToRenderFluidTemplates()) {
            return;
        }

        /** @var \TYPO3\CMS\Fluid\View\StandaloneView $emailTextView */
        $emailTextView = GeneralUtility::makeInstance(\TYPO3\CMS\Fluid\View\StandaloneView::class);
        $emailTextView->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($this->plainTextPathAndFilename));
        $emailTextView->assignMultiple([
            'subject' => $this->getSubject(),
            'content' => $this->plainTextContent
        ]);

        parent::addPart($emailTextView->render(), 'text/plain');

        /** @var \TYPO3\CMS\Fluid\View\StandaloneView $emailHtmlView */
        $emailHtmlView = GeneralUtility::makeInstance(\TYPO3\CMS\Fluid\View\StandaloneView::class);
        $emailHtmlView->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($this->htmlTemplatePathAndFilename));
        $embeddables = [];
        foreach ($this->embeddables as $embeddableIdentifier => $embeddableFilePath) {
            $embeddables[$embeddableIdentifier] = $this->embed(\Swift_Image::fromPath($embeddableFilePath));
        }

        $emailHtmlView->assignMultiple(
            [
                'subject' => $this->getSubject(),
                'embeddables' => $embeddables,
                'content' => $this->htmlContent,
                'css' => empty($this->cssFilePath) ? '' : file_get_contents(GeneralUtility::getFileAbsFileName($this->cssFilePath))
            ]
        );

        // Explicitly use the parent 'setBody' method (same reason as for the plaintext
        // version, see above).
        parent::addPart($emailHtmlView->render(), 'text/html');
    }

}