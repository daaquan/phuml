<?php
/**
 * PHP version 7.1
 *
 * This source file is subject to the license that is bundled with this package in the file LICENSE.
 */
namespace PhUml\Processors;

use PhUml\Graphviz\ClassGraphElements;
use PhUml\Graphviz\Digraph;
use PhUml\Graphviz\HtmlLabelStyle;
use PhUml\Graphviz\InterfaceGraphElements;
use PhUml\Graphviz\NodeLabelBuilder;
use plGraphvizProcessorOptions;
use plProcessor;
use Twig_Environment as TemplateEngine;
use Twig_Loader_Filesystem as Filesystem;

class GraphvizProcessor extends plProcessor
{
    /** @var plGraphvizProcessorOptions */
    public $options;

    /** @var Digraph */
    private $digraph;

    public function __construct(Digraph $digraph = null)
    {
        $this->options = new plGraphvizProcessorOptions();
        $labelBuilder = new NodeLabelBuilder(new TemplateEngine(
            new FileSystem(__DIR__ . '/../Graphviz/templates')
        ), new HtmlLabelStyle());
        $classElements = new ClassGraphElements($this->options->createAssociations, $labelBuilder);
        $interfaceElements = new InterfaceGraphElements($labelBuilder);
        $this->digraph = $digraph ?? new Digraph($interfaceElements, $classElements);
    }

    public function name(): string
    {
        return 'Graphviz';
    }

    public function getInputType(): string
    {
        return 'application/phuml-structure';
    }

    public function getOutputType(): string
    {
        return 'text/dot';
    }

    public function process($input)
    {
        $this->digraph->fromCodeStructure($input);

        return $this->digraph->toDotLanguage();
    }
}