<?php
/**
 * PHP version 7.1
 *
 * This source file is subject to the license that is bundled with this package in the file LICENSE.
 */
namespace PhUml\Actions;

use PhUml\Processors\Processor;

interface CanGenerateClassDiagram
{
    public function runningParser(): void;

    public function runningProcessor(Processor $processor): void;

    public function savingResult(): void;
}