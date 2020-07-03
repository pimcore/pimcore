<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Workflow\Dumper;

use Pimcore\Workflow\Manager;
use Pimcore\Workflow\Transition;
use Symfony\Component\Workflow\Definition;
use Symfony\Component\Workflow\Dumper\DumperInterface;
use Symfony\Component\Workflow\Marking;

/**
 * GraphvizDumper dumps a workflow as a graphviz file.
 *
 * You can convert the generated dot file with the dot utility (http://www.graphviz.org/):
 *
 *   dot -Tpng workflow.dot > workflow.png
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Grégoire Pineau <lyrixx@lyrixx.info>
 */
class GraphvizDumper implements DumperInterface
{
    protected static $defaultOptions = [
        'workflowName' => '',
        'graph' => ['ratio' => 'compress', 'rankdir' => 'LR'],
        'node' => ['fontsize' => 9, 'fontname' => 'Arial', 'color' => '#333333', 'fillcolor' => 'lightblue', 'fixedsize' => false, 'width' => 1, 'height' => 0.8],
        'edge' => ['fontsize' => 9, 'fontname' => 'Arial', 'color' => '#333333', 'arrowhead' => 'normal', 'arrowsize' => 0.5],
    ];

    /**
     * @var Manager
     */
    private $workflowManager;

    public function __construct(Manager $workflowManager)
    {
        $this->workflowManager = $workflowManager;
    }

    /**
     * {@inheritdoc}
     *
     * Dumps the workflow as a graphviz graph.
     *
     * Available options:
     *
     *  * graph: The default options for the whole graph
     *  * node: The default options for nodes (places + transitions)
     *  * edge: The default options for edges
     */
    public function dump(Definition $definition, Marking $marking = null, array $options = [])
    {
        $places = $this->findPlaces($definition, $marking, $options['workflowName']);
        $transitions = $this->findTransitions($definition);
        $edges = $this->findEdges($definition);

        $options = array_replace_recursive(self::$defaultOptions, $options);

        return $this->startDot($options)
            .$this->addPlaces($places)
            .$this->addTransitions($transitions)
            .$this->addEdges($edges)
            .$this->endDot();
    }

    /**
     * @internal
     */
    protected function findPlaces(Definition $definition, Marking $marking = null, string $workflowName = '')
    {
        $places = [];
        foreach ($definition->getPlaces() as $place) {
            $attributes = [];
            if ($place === $definition->getInitialPlace()) {
                $attributes['style'] = 'filled';
                $attributes['fillcolor'] = '#DFDFDF';
            }
            if ($marking && $marking->has($place)) {
                $attributes['fillcolor'] = '#FF4500';
                $attributes['fontcolor'] = '#FFFFFF';
                $attributes['style'] = 'filled';
                $attributes['color'] = '#FF4500';
            }

            $placeConfig = $this->workflowManager->getPlaceConfig($workflowName, $place);

            $places[$place] = [
                'attributes' => $attributes,
                'label' => $placeConfig->getLabel(),
            ];
        }

        return $places;
    }

    /**
     * @internal
     */
    protected function findTransitions(Definition $definition)
    {
        $transitions = [];

        foreach ($definition->getTransitions() as $transition) {
            $transitions[] = [
                'attributes' => ['shape' => 'box', 'regular' => false, 'height' => 0.6, 'style' => 'dotted'],
                'name' => $transition->getName(),
                'label' => $transition instanceof Transition ? $transition->getLabel() : $transition->getName(),
            ];
        }

        return $transitions;
    }

    /**
     * @internal
     */
    protected function addPlaces(array $places)
    {
        $code = '';
        foreach ($places as $id => $place) {
            $code .= sprintf("  place_%s [label=\"%s\", shape=ellipse%s];\n", $this->dotize($id), $place['label'], $this->addAttributes($place['attributes']));
        }

        return $code;
    }

    /**
     * @internal
     */
    protected function addTransitions(array $transitions)
    {
        $code = '';

        foreach ($transitions as $transition) {
            $code .= sprintf("  transition_%s [label=\"%s\", shape=box%s];\n", $this->dotize($transition['name']), $transition['label'], $this->addAttributes($transition['attributes']));
        }

        return $code;
    }

    /**
     * @internal
     */
    protected function findEdges(Definition $definition)
    {
        $dotEdges = [];

        foreach ($definition->getTransitions() as $transition) {
            foreach ($transition->getFroms() as $from) {
                $dotEdges[] = [
                    'from' => $from,
                    'to' => $transition->getName(),
                    'direction' => 'from',
                ];
            }
            foreach ($transition->getTos() as $to) {
                $dotEdges[] = [
                    'from' => $transition->getName(),
                    'to' => $to,
                    'direction' => 'to',
                ];
            }
        }

        return $dotEdges;
    }

    /**
     * @internal
     */
    protected function addEdges(array $edges)
    {
        $code = '';

        foreach ($edges as $edge) {
            $code .= sprintf("  %s_%s -> %s_%s [style=\"solid\"];\n",
                'from' === $edge['direction'] ? 'place' : 'transition',
                $this->dotize($edge['from']),
                'from' === $edge['direction'] ? 'transition' : 'place',
                $this->dotize($edge['to'])
            );
        }

        return $code;
    }

    /**
     * @internal
     */
    protected function startDot(array $options)
    {
        return sprintf("digraph workflow {\n  %s\n  node [%s];\n  edge [%s];\n\n",
            $this->addOptions($options['graph']),
            $this->addOptions($options['node']),
            $this->addOptions($options['edge'])
        );
    }

    /**
     * @internal
     */
    protected function endDot()
    {
        return "}\n";
    }

    /**
     * @internal
     */
    protected function dotize($id)
    {
        return strtolower(preg_replace('/[^\w]/i', '_', $id));
    }

    private function addAttributes(array $attributes)
    {
        $code = [];

        foreach ($attributes as $k => $v) {
            $code[] = sprintf('%s="%s"', $k, $v);
        }

        return $code ? ', '.implode(', ', $code) : '';
    }

    private function addOptions(array $options)
    {
        $code = [];

        foreach ($options as $k => $v) {
            $code[] = sprintf('%s="%s"', $k, $v);
        }

        return implode(' ', $code);
    }
}
