<?php

declare(strict_types=1);

namespace ChernegaSergiy\TerminalUi\Component;

/**
 * Base class holding the state every component shares: its label and the
 * "interaction finished" flag.
 */
abstract class AbstractComponent implements Component
{
    protected string $label;
    protected bool $done = false;

    /**
     * @param string $label Human-readable label (title, prompt) of the component.
     */
    protected function __construct(string $label)
    {
        $this->label = $label;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function isDone(): bool
    {
        return $this->done;
    }

    /**
     * Components are valid unless they say otherwise.
     */
    public function isValid(): bool
    {
        return true;
    }
}
