<?php

declare(strict_types=1);

namespace ChernegaSergiy\TerminalUi\Component;

/**
 * Interface for interactive terminal components.
 */
interface Component
{
    /**
     * Renders the component's current state.
     * @return string The frame to print; the component itself never writes to the output.
     */
    public function render(): string;

    /**
     * Handles a key press and updates the component's state.
     * @param string $key The key that was pressed (e.g., 'up', 'enter', 'a').
     */
    public function handleKey(string $key): void;

    /**
     * Checks if the component has finished its interaction.
     * @return bool True if the interaction is complete, false otherwise.
     */
    public function isDone(): bool;

    /**
     * Gets the final result from the component after interaction is complete.
     * @return mixed The result of the interaction.
     */
    public function getResult(): mixed;

    /**
     * Checks if the component's current state is valid.
     * @return bool True if the component is valid, false otherwise.
     */
    public function isValid(): bool;

    /**
     * Gets a human-readable label for the component.
     * @return string The label of the component.
     */
    public function getLabel(): string;

    /**
     * Gets a unique identifier for the component.
     * @return string The unique ID of the component.
     */
    public function getId(): string;
}
