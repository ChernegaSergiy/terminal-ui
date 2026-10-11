# ChernegaSergiy/TerminalUI

This library provides a set of interactive components for building terminal-based user interfaces in PHP.

## Core Concepts

### `Component` Interface

All interactive elements in this library implement the `Component` interface. This interface defines the contract for how an interactive component should behave, ensuring a consistent API for interaction and data retrieval.

-   `render(): void`
    -   **Purpose**: Displays the component's current state on the terminal screen. This method is responsible for all visual output of the component.
    -   **Details**: Should output formatted text, progress bars, or any other visual representation relevant to the component.

-   `handleKey(string $key): void`
    -   **Purpose**: Processes user key presses and updates the component's internal state accordingly.
    -   **Details**: Takes a string representing the pressed key (e.g., 'up', 'down', 'enter', 'a', '1'). Components should define how they react to different keys (e.g., a slider might increment/decrement on arrow keys, an input field might append characters).

-   `isDone(): bool`
    -   **Purpose**: Indicates whether the interaction with the component is complete.
    -   **Details**: Returns `true` when the component has gathered all necessary input or reached a final state (e.g., after the user presses Enter in an input field or confirms a selection).

-   `getResult(): mixed`
    -   **Purpose**: Returns the final result or value collected by the component after interaction is complete.
    -   **Details**: The return type can vary (`mixed`) depending on the component (e.g., `int` for `SliderInput`, `string` for a text input, `array` for a form).

-   `isValid(): bool`
    -   **Purpose**: Checks if the component's current state or collected value is valid according to its internal rules.
    -   **Details**: Returns `true` if the component's data meets its validation criteria (e.g., a number is within a specified range, a required field is not empty). Note that some components, like `InteractiveInput`, might delegate validation to external validators, in which case this method might always return `true` internally.

-   `getLabel(): string`
    -   **Purpose**: Returns a human-readable label or prompt for the component.
    -   **Details**: Used to display descriptive text to the user, often as part of the `render()` method or by a parent `Form`.

-   `getId(): string`
    -   **Purpose**: Returns a unique identifier for the component.
    -   **Details**: Useful for distinguishing components within a `Form` and for retrieving specific results from the form's output.

### `Form` Class

The `Form` class serves as a powerful container and manager for multiple `Component` objects, enabling the creation of complex interactive terminal forms.

-   **Responsibilities**:
    -   **Aggregating Components**: Allows you to group various `Component` instances (e.g., `SliderInput`, `InteractiveInput`) into a single logical unit.
    -   **Navigation**: Manages the focus between child components, typically allowing users to move between them using `up`/`down` arrow keys or `Tab`.
    -   **Input Handling**: Delegates key presses to the currently focused child component, allowing each component to handle its specific interactions.
    -   **Result Collection**: Once the form interaction is complete, it can collect and return the results from all its contained components.
    -   **Form Validation**: Provides a mechanism to check the overall validity of the form based on the validity of its child components.

-   **Constructor**:
    ```php
    public function __construct(string $title, array $items, ?string $id = null, string $navigationInstruction = 'Використовуйте ↑↓ для навігації, Enter для вибору, ESC для виходу')
    ```
    -   `$title` (string): The title of the menu.
    -   `$items` (array): An array of menu items (strings).
    -   `$id` (string|null): Optional unique ID for the component.
    -   `$navigationInstruction` (string): Optional. The instruction message displayed for navigation (default: 'Використовуйте ↑↓ для навігації, Enter для вибору, ESC для виходу').

### `SliderInput` Class

An interactive component designed for selecting a numeric value within a defined range. Users typically adjust the value using left/right arrow keys.

-   **Constructor**:
    ```php
    public function __construct(string $label, int $min, int $max, ?int $default = null, int $step = 1, ?string $id = null, string $changeValueInstruction = '← → для зміни значення, Enter для підтвердження', string $quickInputInstruction = 'Або введіть цифру для швидкого вводу')
    ```
    -   `$label` (string): The prompt or label displayed for the slider.
    -   `$min` (int): The minimum allowed value for the slider.
    -   `$max` (int): The maximum allowed value for the slider.
    -   `$default` (int|null): The initial value of the slider. If `null`, it defaults to `$min`.
    -   `$step` (int): The increment/decrement amount when adjusting the slider value.
    -   `$id` (string|null): An optional unique identifier for the slider component.
    -   `$changeValueInstruction` (string): Optional. The instruction message for changing the value (default: '← → для зміни значення, Enter для підтвердження').
    -   `$quickInputInstruction` (string): Optional. The instruction message for quick input (default: 'Або введіть цифру для швидкого вводу').

### `InteractiveInput` Class

An interactive component for capturing single-line text input from the user. It focuses on basic input handling and delegates validation to external `ValidatorInterface` implementations.

-   **Constructor**:
    ```php
    public function __construct(string $prompt, ?string $id = null, ?\ChernegaSergiy\\TerminalUI\\Validator\\ValidatorInterface $validator = null)
    ```
    -   `$prompt` (string): The message displayed to the user asking for input.
    -   `$id` (string|null): An optional unique identifier for the input component.
    -   `$validator` (ValidatorInterface|null): An optional validator instance to apply to the input. If provided, the input will only be considered "done" if the validator returns `null` (i.e., no error).

### Validation with `ValidatorInterface`

To keep UI components clean and free of business logic, validation is handled externally via the `ValidatorInterface`. This allows developers to define custom validation rules and error messages.

#### `ValidatorInterface`

```php
namespace ChernegaSergiy\\TerminalUI\\Validator;

interface ValidatorInterface
{
    /**
     * Validates the given value.
     * @param string $value The value to validate.
     * @return string|null Returns an error message if validation fails, or null if valid.
     */
    public function validate(string $value): ?string;
}
```

#### `CallbackValidator`

A convenient implementation of `ValidatorInterface` that allows you to define validation logic using a PHP callable (closure).

-   **Constructor**:
    ```php
    public function __construct(callable $callback)
    ```
    -   `$callback` (callable): A function that takes the input string as an argument and returns `null` for valid input or a string error message for invalid input.

### `ComponentRunner` Class

This utility class provides a static method to run any `Component` (including a `Form`). It manages the terminal's raw input mode, continuously renders the component, and passes key presses to the component's `handleKey` method until the component's `isDone` method returns `true`. It then returns the component's result.

## Usage Example

Here's a simple example demonstrating how to create a form with `SliderInput` and `InteractiveInput` components and run it, including external validation for `InteractiveInput`:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use ChernegaSergiy\TerminalUI\Form;
use ChernegaSergiy\TerminalUI\SliderInput;
use ChernegaSergiy\TerminalUI\ComponentRunner;
use ChernegaSergiy\TerminalUI\InteractiveInput;
use ChernegaSergiy\TerminalUI\Validator\CallbackValidator;

// Create components
$slider1 = new SliderInput('Виберіть значення для Slider 1', 0, 100, 50, 5, 'slider_one');
$slider2 = new SliderInput('Виберіть значення для Slider 2', 10, 20, 15, 1, 'slider_two');

// Create a validator for age input
$ageValidator = new CallbackValidator(function (string $value): ?string {
    if (empty($value)) {
        return 'Вік є обов'язковим полем.';
    }
    if (!is_numeric($value)) {
        return 'Будь ласка, введіть числове значення.';
    }
    $age = (int) $value;
    if ($age < 0 || $age > 120) {
        return 'Вік має бути від 0 до 120 років.';
    }
    return null; // Valid
});

$ageInput = new InteractiveInput('Введіть ваш вік', 'age_input', $ageValidator);

// Create the form and add components
$form = new Form('Налаштування програми', [
    $slider1,
    $slider2,
    $ageInput
]);

// Run the form using ComponentRunner
$results = ComponentRunner::run($form);

// Output the results
echo "\n--- Результати форми ---\n";
foreach ($results as $id => $value) {
    echo "{$id}: {$value}\n";
}
echo "------------------------\n";

?>
```

To run this example, save it as a PHP file (e.g., `my_form_app.php`) in your project's root directory and execute it from your terminal:

```bash
php my_form_app.php
```

This will launch the interactive form, allowing you to navigate between components, input values, and see the collected results upon completion.

## License

This library is licensed under the CSSM Unlimited License v2.0 (CSSM-ULv2). See the [LICENSE](LICENSE) file for details.