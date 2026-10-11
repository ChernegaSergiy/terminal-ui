# terminal-ui

This library provides a set of interactive components for building terminal-based user interfaces in PHP. It enables developers to create intuitive CLI applications with ready-to-use elements like forms, menus, sliders, checkboxes, and validated inputs.

## Core Concepts

### `Component` Interface

All interactive elements in this library implement the `Component` interface. This interface defines the contract for how an interactive component should behave, ensuring a consistent API for interaction and data retrieval.

-   `render(): string`
    -   **Purpose**: Describes the component's current state as a frame. The component never writes to the terminal itself; the `Runner` (or a parent `Form`) decides where the frame goes.
    -   **Details**: Returns the lines of the component separated by `"\n"`, with no leading or trailing newline and no outer margin. It may contain ANSI styling (each styled span should reset itself). Because the result is a plain string, a component can be nested inside another one and tested by comparing the returned string.

-   `handleKey(Key|string $key): void`
    -   **Purpose**: Processes user key presses and updates the component's internal state accordingly.
    -   **Details**: Takes either a `Key` case for a non-printable key (e.g., `Key::Up`, `Key::Enter`) or the typed character itself as a string (e.g., `'a'`, `'1'`, `' '`). Components should define how they react to different keys (e.g., a slider might increment/decrement on arrow keys, an input field might append characters).

-   `isDone(): bool`
    -   **Purpose**: Indicates whether the interaction with the component is complete.
    -   **Details**: Returns `true` when the component has gathered all necessary input or reached a final state (e.g., after the user presses Enter in an input field or confirms a selection).

-   `getResult(): mixed`
    -   **Purpose**: Returns the final result or value collected by the component after interaction is complete.
    -   **Details**: The return type can vary (`mixed`) depending on the component (e.g., `int` for `Slider`, `string` for a text input, `array` for a form).

-   `isValid(): bool`
    -   **Purpose**: Checks if the component's current state or collected value is valid according to its internal rules.
    -   **Details**: Returns `true` if the component's data meets its validation criteria (e.g., a number is within a specified range, a required field is not empty). Components like `NumberInput` delegate this check to their `ValidatorInterface`, so `isValid()` reflects whether the current value passes it.

-   `getLabel(): string`
    -   **Purpose**: Returns a human-readable label or prompt for the component.
    -   **Details**: Used to display descriptive text to the user, often as part of the `render()` method or by a parent `Form`.


### `Key` Enum

`ChernegaSergiy\TerminalUi\Terminal\Key` lists the non-printable keys the library understands: `Up`, `Down`, `Left`, `Right`, `Enter`, `Esc`, `Tab` and `Backspace`. Printable characters, including the space bar, have no case: they are passed to `handleKey()` as the plain string that was typed, so check for them with `is_string($key)`.

```php
public function handleKey(Key|string $key): void
{
    if ($key === Key::Enter) {
        $this->done = true;
    } elseif ($key === ' ') {
        $this->checked = !$this->checked;
    }
}
```

### `Form` Class

The `Form` class serves as a powerful container and manager for multiple `Component` objects, enabling the creation of complex interactive terminal forms.

-   **Responsibilities**:
    -   **Aggregating Components**: Allows you to group various `Component` instances (e.g., `Slider`, `NumberInput`) into a single logical unit.
    -   **Navigation**: Manages the focus between child components, typically allowing users to move between them using `up`/`down` arrow keys or `Tab`.
    -   **Input Handling**: Delegates key presses to the currently focused child component, allowing each component to handle its specific interactions.
    -   **Result Collection**: Once the form interaction is complete, it can collect and return the results from all its contained components.
    -   **Form Validation**: Provides a mechanism to check the overall validity of the form based on the validity of its child components.

-   **Constructor**:
    ```php
    public function __construct(string $title, array $fields = [])
    ```
    -   `$title` (string): The title displayed above the form.
    -   `$fields` (array<string, Component>): The child components keyed by field name, in the order they are shown. More can be added later with `add()`. A list without names throws an `InvalidArgumentException`.

-   **Adding fields**: `add(string $name, Component $component): static` appends a field and returns the form, so calls can be chained. The name must be non-empty and unique within the form; use `add()` instead of the constructor array for names that PHP would turn into integer keys (e.g. `'2024'`).

-   **Result**: `getResult()` returns an array keyed by field name, e.g. `['age' => 25]`.

-   **Behaviour**:
    -   Enter confirms the focused field and moves to the next one. A field that fails validation keeps the focus.
    -   Enter on the last field submits the form only if every child is valid; otherwise focus jumps to the first invalid field.

### `Slider` Class

An interactive component designed for selecting a numeric value within a defined range. Users typically adjust the value using left/right arrow keys.

-   **Constructor**:
    ```php
    public function __construct(string $label, int $min, int $max, ?int $default = null, int $step = 1, string $changeValueInstruction = '← → to change the value, Enter to confirm', string $quickInputInstruction = 'Or type a digit for quick input')
    ```
    -   `$label` (string): The prompt or label displayed for the slider.
    -   `$min` (int): The minimum allowed value for the slider.
    -   `$max` (int): The maximum allowed value for the slider.
    -   `$default` (int|null): The initial value of the slider. If `null`, it defaults to `$min`.
    -   `$step` (int): The increment/decrement amount when adjusting the slider value.
    -   `$changeValueInstruction` (string): Optional. The instruction message for changing the value (default: '← → to change the value, Enter to confirm').
    -   `$quickInputInstruction` (string): Optional. The instruction message for quick input (default: 'Or type a digit for quick input').

### `NumberInput` Class

An interactive component for capturing a number from the user: it accepts digits only (up to 3 characters) and returns an `int`. It delegates validation to external `ValidatorInterface` implementations.

-   **Constructor**:
    ```php
    public function __construct(string $prompt, ?ValidatorInterface $validator = null)
    ```
    -   `$prompt` (string): The message displayed to the user asking for input.
    -   `$validator` (ValidatorInterface|null): An optional validator instance to apply to the input. If provided, the input will only be considered "done" if the validator returns `null` (i.e., no error).

### Validation with `ValidatorInterface`

To keep UI components clean and free of business logic, validation is handled externally via the `ValidatorInterface`. This allows developers to define custom validation rules and error messages.

#### `ValidatorInterface`

```php
namespace ChernegaSergiy\TerminalUi\Validator;

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

### `Runner` Class

This utility class provides a static method to run any `Component` (including a `Form`). It manages the terminal's raw input mode, prints the string returned by the component's `render()` after every key press, and passes key presses to the component's `handleKey` method until the component's `isDone` method returns `true`. It then returns the component's result.

The runner draws every frame in the terminal's alternative screen, so the output printed before it started is left untouched, and it restores the terminal (input mode, cursor, screen) when it finishes, fails, or the process is interrupted.

**Requirements**: an interactive POSIX terminal with `stty` available. Windows and non-interactive input (pipes, redirected STDIN) are not supported and raise a `RuntimeException`. The optional `pcntl` extension lets the terminal be restored on `SIGINT`/`SIGTERM`/`SIGHUP`.

### Colours and `NO_COLOR`

Components style their output through `ChernegaSergiy\TerminalUi\Terminal\Ansi`. Colours are switched off automatically when the `NO_COLOR` environment variable is set to a non-empty value (see [no-color.org](https://no-color.org)) or when `STDOUT` is not a terminal, so escape codes do not end up in log files or pipes. `Ansi::setEnabled(true|false)` overrides the detection (`null` restores it); this is handy in tests, where `render()` output should not depend on the environment. Use `Ansi::style(Ansi::BOLD_YELLOW, 'text')` in your own components to get the same behaviour.

## Usage Example

Here's a simple example demonstrating how to create a form with `Slider` and `NumberInput` components and run it, including external validation for `NumberInput`:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use ChernegaSergiy\TerminalUi\Component\Form;
use ChernegaSergiy\TerminalUi\Component\Slider;
use ChernegaSergiy\TerminalUi\Runner;
use ChernegaSergiy\TerminalUi\Component\NumberInput;
use ChernegaSergiy\TerminalUi\Validator\CallbackValidator;

// Create components
$slider1 = new Slider('Choose a value for Slider 1', 0, 100, 50, 5);
$slider2 = new Slider('Choose a value for Slider 2', 10, 20, 15, 1);

// Create a validator for age input
$ageValidator = new CallbackValidator(function (string $value): ?string {
    if (empty($value)) {
        return 'Age is required.';
    }
    if (!is_numeric($value)) {
        return 'Please enter a numeric value.';
    }
    $age = (int) $value;
    if ($age < 0 || $age > 120) {
        return 'Age must be between 0 and 120.';
    }
    return null; // Valid
});

$ageInput = new NumberInput('Enter your age', $ageValidator);

// Create the form: every field has a name that becomes its key in the result
$form = new Form('Application settings', [
    'slider_one' => $slider1,
    'slider_two' => $slider2,
    'age_input' => $ageInput,
]);

// Run the form using Runner
$results = Runner::run($form);

// Output the results
echo "\n--- Form results ---\n";
foreach ($results as $name => $value) {
    echo "{$name}: {$value}\n";
}
echo "------------------------\n";

?>
```

To run this example, save it as a PHP file (e.g., `my_form_app.php`) in your project's root directory and execute it from your terminal:

```bash
php my_form_app.php
```

This will launch the interactive form, allowing you to navigate between components, input values, and see the collected results upon completion.

## Contributing

Contributions are welcome and appreciated! Here's how you can contribute:

1. Fork the project
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

Please make sure to update tests as appropriate and adhere to the existing coding style.

## License

This library is licensed under the CSSM Unlimited License v2.0 (CSSM-ULv2). See the [LICENSE](LICENSE) file for details.