<?php

declare(strict_types=1);

namespace ChernegaSergiy\TerminalUi;

/**
 * Base class holding the state every component shares: its label, a unique ID
 * and the "interaction finished" flag.
 */
abstract class AbstractComponent implements Component
{
    protected string $label;
    protected string $id;
    protected bool $done = false;

    /**
     * @param string $label Human-readable label (title, prompt) of the component.
     * @param string|null $id Optional unique ID; generated from $idPrefix when omitted.
     * @param string $idPrefix Prefix for generated IDs, e.g. 'slider_'.
     */
    protected function __construct(string $label, ?string $id, string $idPrefix)
    {
        $this->label = $label;
        $this->id = $id ?? uniqid($idPrefix);
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getId(): string
    {
        return $this->id;
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
