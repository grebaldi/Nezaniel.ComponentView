<?php

/*
 * This file is part of the Nezaniel.ComponentView package.
 */

declare(strict_types=1);

namespace Nezaniel\ComponentView\Application;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Ui\Fusion\Helper\ContentDimensionsHelper;
use Neos\Neos\Ui\Fusion\Helper\NodeInfoHelper;
use Neos\Neos\Ui\Fusion\Helper\StaticResourcesHelper;

/**
 * The factory to create components necessary for Neos' inline editing
 */
#[Flow\Scope('singleton')]
final class NeosStuffFactory extends AbstractComponentFactory
{
    #[Flow\Inject]
    protected NodeInfoHelper $nodeInfoHelper;

    #[Flow\Inject]
    protected ContentDimensionsHelper $contentDimensionsHelper;

    public function getHeadStuff(bool $inBackend, Node $documentNode, Node $site): ?string
    {
        if (!$inBackend) {
            return null;
        }

        $configuration = [
            'metaData' => [
                'documentNode' => $this->nodeInfoHelper->serializedNodeAddress($documentNode),
                'siteNode' => $this->nodeInfoHelper->serializedNodeAddress($site),
                'previewUrl' => $this->nodeInfoHelper->createRedirectToNode($documentNode, $this->uriService->getControllerContext()),
                'contentDimensions' => [
                    'active' => $this->contentDimensionsHelper->dimensionSpacePointArray(
                        $documentNode->subgraphIdentity->dimensionSpacePoint
                    ),
                    'allowedPresets' => $this->contentDimensionsHelper->allowedPresetsByName(
                        $documentNode->subgraphIdentity->dimensionSpacePoint,
                        $documentNode->subgraphIdentity->contentRepositoryId
                    ) ?: new \stdClass()
                ],
                'documentNodeSerialization' => $this->nodeInfoHelper->renderNodeWithPropertiesAndChildrenInformation(
                    $documentNode,
                    $this->uriService->getControllerContext()
                )
            ]
        ];

        $compiledResourcePackageKey = (new StaticResourcesHelper())->compiledResourcePackage();

        return '
            <script>window[\'@Neos.Neos.Ui:DocumentInformation\']=' . json_encode($configuration) . '</script>
            <script>window.neos = window.parent.neos;</script>
            <link rel="stylesheet" href="' . $this->uriService->getResourceUri($compiledResourcePackageKey, 'Build/Host.css') . '">
        ';
    }

    public function getBodyStuff(bool $inBackend): ?string
    {
        return $inBackend
            ? '
                <div id="neos-backend-container"></div>
                <script type="application/javascript">
                    document.addEventListener("DOMContentLoaded", function () {
                        var event = new CustomEvent("Neos.Neos.Ui.ContentReady");
                        window.parent.document.dispatchEvent(event);
                    });
                </script>
            '
            : null;
    }
}
