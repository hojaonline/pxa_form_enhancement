<?php
declare(strict_types=1);

namespace Pixelant\PxaFormEnhancement\Domain\Finishers;

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

use Pixelant\PxaFormEnhancement\Domain\Model\FileReference as AttachFileReference;
use Pixelant\PxaFormEnhancement\Domain\Model\Form;
use Pixelant\PxaFormEnhancement\Domain\Repository\FormRepository;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Form\Domain\Finishers\Exception\FinisherException;
use TYPO3\CMS\Form\Domain\Model\FormElements\FileUpload;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\CMS\Form\ViewHelpers\RenderRenderableViewHelper;

/**
 * This finisher redirects to another Controller.
 *
 * Scope: frontend
 */
class SaveFormFinisher extends AbstractFinisher
{

    /**
     * @var array
     */
    protected $defaultOptions = [
        'pageUid' => 1,
        'name' => '',
    ];

    /**
     * @var FormRepository
     */
    protected $formRepository;

    /**
     * @var ResourceFactory
     */
    protected $resourceFactory;

    /**
     * @var Form
     */
    protected $saveForm;

    /**
     * Storage for records
     *
     * @var int
     */
    protected $pid = 0;

    /**
     * Executes this finisher
     * @see AbstractFinisher::execute()
     */
    protected function executeInternal()
    {
        $this->formRepository = $this->objectManager->get(FormRepository::class);
        $this->resourceFactory = $this->objectManager->get(ResourceFactory::class);
        $this->saveForm = $this->objectManager->get(Form::class);
        $this->pid = $this->getPid();

        $count = $this->formRepository->countByPid($this->pid);

        $formRuntime = $this->finisherContext->getFormRuntime();
        $standaloneView = $this->initializeStandaloneView($formRuntime);

        $message = trim($standaloneView->render());

        $this->saveForm->setFormData($message);
        $this->saveForm->setPid($this->pid);
        $this->saveForm->setName($this->options['name'] . ' #' . ++$count);
        
        $formArguments = $formRuntime->getFormState()->getFormValues();
        if (is_array($formArguments)) {
            $matchedArray = $this->checkMarkerInitialized($this->options['name']);
            if (!empty($matchedArray[1])) {
                foreach ($matchedArray[1] as $index => $value) {
                    if (isset($formArguments[$value]) && !empty($formArguments[$value])) {
                        $matchedArray[1][$index] = $formArguments[$value];
                    }
                }
            }
            $this->options['name'] = str_replace($matchedArray[0], $matchedArray[1], $this->options['name']);
            $this->saveForm->setName($this->options['name'] . ' #' . ++$count);
        }
        $this->attachFiles($formRuntime);
        $this->formRepository->add($this->saveForm);
        $this->objectManager->get(PersistenceManager::class)->persistAll();
    }

    /**
     * checkMarkerInitialized
     *
     * @param $option string
     * @return multiple
     */
    public function checkMarkerInitialized($option)
    {
        $pattern  = "/{(.*?)}/";
        $stringss = preg_match_all($pattern, $option, $matches);
        if (!empty($matches)) {
            return $matches;
        }
        else {
            return false;
        }
    }

    /**
     * Attach files
     *
     * @param FormRuntime $formRuntime
     */
    protected function attachFiles(FormRuntime $formRuntime)
    {
        $elements = $formRuntime->getFormDefinition()->getRenderablesRecursively();

        foreach ($elements as $element) {
            if ($element instanceof FileUpload) {
                $file = $formRuntime[$element->getIdentifier()];

                if ($file) {
                    /** @var AttachFileReference $attachment */
                    $attachment = $this->objectManager->get(AttachFileReference::class);

                    if ($file instanceof FileReference) {
                        $file = $file->getOriginalResource();
                    }

                    $newFileReferenceObject = $this->resourceFactory->createFileReferenceObject(
                        [
                            'uid_local' => $file->getOriginalFile()->getUid(),
                            'uid_foreign' => uniqid('NEW_'),
                            'uid' => uniqid('NEW_')
                        ]
                    );

                    $attachment->setOriginalResource($newFileReferenceObject);
                    $attachment->setPid($this->pid);

                    $this->saveForm->addAttachment($attachment);
                }
            }
        }
    }

    /**
     * Get pid as storage
     *
     * @return int
     */
    protected function getPid(): int
    {
        if (GeneralUtility::isFirstPartOfStr($this->options['pageUid'], 'pages_')) {
            $pid = (int)substr($this->options['pageUid'], 6);
        } else {
            $pid = (int)$this->options['pageUid'];
        }

        return $pid;
    }
    /**
     * @param FormRuntime $formRuntime
     * @return StandaloneView
     * @throws FinisherException
     */
    protected function initializeStandaloneView(FormRuntime $formRuntime): StandaloneView
    {
        if (!isset($this->options['templatePathAndFilename'])) {
            throw new FinisherException(
                'The option "templatePathAndFilename" must be set for the EmailFinisher.',
                1327058829
            );
        }

        /** @var StandaloneView $standaloneView */
        $standaloneView = $this->objectManager->get(StandaloneView::class);
        $standaloneView->setTemplatePathAndFilename($this->options['templatePathAndFilename']);
        $standaloneView->assign('finisherVariableProvider', $this->finisherContext->getFinisherVariableProvider());

        if (isset($this->options['partialRootPaths']) && is_array($this->options['partialRootPaths'])) {
            $standaloneView->setPartialRootPaths($this->options['partialRootPaths']);
        }

        if (isset($this->options['layoutRootPaths']) && is_array($this->options['layoutRootPaths'])) {
            $standaloneView->setLayoutRootPaths($this->options['layoutRootPaths']);
        }

        if (isset($this->options['variables'])) {
            $standaloneView->assignMultiple($this->options['variables']);
        }

        $standaloneView->assign('form', $formRuntime);
        $standaloneView->getRenderingContext()
            ->getViewHelperVariableContainer()
            ->addOrUpdate(RenderRenderableViewHelper::class, 'formRuntime', $formRuntime);

        return $standaloneView;
    }
}
