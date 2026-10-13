[![Stand With Ukraine](https://raw.githubusercontent.com/vshymanskyy/StandWithUkraine/main/banner-direct-single.svg)](https://stand-with-ukraine.pp.ua)

# terminal-ui

[![Latest Stable Version](https://img.shields.io/packagist/v/chernegasergiy/terminal-ui.svg?label=Packagist&logo=packagist)](https://packagist.org/packages/chernegasergiy/terminal-ui)
[![Total Downloads](https://img.shields.io/packagist/dt/chernegasergiy/terminal-ui.svg?label=Downloads&logo=packagist)](https://packagist.org/packages/chernegasergiy/terminal-ui)
[![License](https://img.shields.io/packagist/l/chernegasergiy/terminal-ui.svg?label=Licence&logo=open-source-initiative)](https://packagist.org/packages/chernegasergiy/terminal-ui)
[![PHP Unit Tests](https://github.com/ChernegaSergiy/terminal-ui/actions/workflows/phpunit.yml/badge.svg)](https://github.com/ChernegaSergiy/terminal-ui/actions/workflows/phpunit.yml)
[![Test Coverage](https://img.shields.io/codecov/c/github/ChernegaSergiy/terminal-ui?label=Test%20Coverage&logo=codecov)](https://app.codecov.io/gh/ChernegaSergiy/terminal-ui)
[![PHPStan](https://github.com/ChernegaSergiy/terminal-ui/actions/workflows/phpstan.yml/badge.svg)](https://github.com/ChernegaSergiy/terminal-ui/actions/workflows/phpstan.yml)

This library provides a set of interactive components for building terminal-based user interfaces in PHP. It enables developers to create intuitive CLI applications with ready-to-use elements like forms, menus, sliders, checkboxes, and validated inputs.

![Terminal UI Demo](docs/demo/wizard.gif)

## Features

- **Interactive Component Library**: Ready-to-use CLI widgets including text and password inputs, multi-line textareas, numbers, sliders, checkboxes, confirmations, single-choice menus, multi-select lists, and searchable dropdowns.
- **Composite Form Engine**: Multi-field form orchestration with `Tab` / `Shift+Tab` keyboard navigation, validation rules, and automatic focus redirection on invalid submission.
- **Alternative Screen Buffer**: Automatically switches to the terminal's alternative screen and hides the cursor, seamlessly restoring the original screen state upon exit.
- **POSIX Signal Handling**: Traps `SIGINT`, `SIGTERM`, and `SIGHUP` via `SignalException`, ensuring clean terminal state restoration before process termination.
- **Headless & Pipeline Safe**: Intelligently inspects stream interactivity (`stream_isatty`) and executes `stty` on active input streams, running safely in non-interactive pipelines and CI.
- **UTF-8 & Non-Blocking Input**: Reads raw terminal input byte-by-byte via `stream_select()`, correctly assembling multi-byte UTF-8 sequences (Cyrillic, emoji) and ANSI escape codes.
- **Built-in ANSI Styling**: Lightweight `Ansi` helper providing standard colors, bright variants, backgrounds, and text formatting attributes without external dependencies.
- **Terminal Dimension Detection**: Dynamically inspects terminal width and height via environment variables, `stty size`, and `tput`, automatically refreshing on window resize via `SIGWINCH`.
- **Zero Runtime Dependencies**: Pure PHP 8.1+ library using native streams and ANSI escape sequences.

## Installation

Install the library with [Composer](https://getcomposer.org):

```bash
composer require chernegasergiy/terminal-ui
```

### Requirements

- PHP 8.1 or newer with the `mbstring` extension
- An interactive POSIX terminal (Linux, macOS) with `stty` available
- Optional: the `pcntl` extension, which lets the terminal be restored on `SIGINT`, `SIGTERM` and `SIGHUP`

Windows and non-interactive input (pipes, redirected `STDIN`) are not supported. See [`Runner` Class](#runner-class) for details.

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


### `AbstractComponent` Class

A base class that implements the parts of the `Component` interface every component shares, so a custom component only has to describe its own behaviour.

-   **Provides**:
    -   `getLabel(): string` returns the label passed to the constructor (also available to subclasses as `$this->label`).
    -   `isDone(): bool` returns the `$done` flag. A subclass sets `$this->done = true` when the interaction is finished.
    -   `isValid(): bool` returns `true` by default. Override it when the component has validation rules.

-   **Constructor**:
    ```php
    protected function __construct(string $label)
    ```
    -   `$label` (string): The title or prompt of the component. The constructor is `protected`, so a subclass calls `parent::__construct($label)` from its own constructor.

-   **To implement**: `render(): string`, `handleKey(Key|string $key): void` and `getResult(): mixed`.

### `Key` Enum

`ChernegaSergiy\TerminalUi\Terminal\Key` lists the non-printable keys the library understands: `Up`, `Down`, `Left`, `Right`, `Home`, `End`, `PageUp`, `PageDown`, `Delete`, `Enter`, `Esc`, `Tab` and `Backspace`. Printable characters, including the space bar, have no case: they are passed to `handleKey()` as the plain string that was typed, so check for them with `is_string($key)`.

Input is read byte by byte, so pasted text and fast typing arrive as individual keys, multi-byte UTF-8 characters (e.g. Cyrillic letters or emoji) arrive whole, and escape sequences are never split. Modifier keys are ignored (`Ctrl+↑` is `Key::Up`), and escape sequences the library does not know (Insert, function keys, Alt combinations) are skipped. A lone `Esc` is recognised after a 50 ms pause, which is how it is told apart from the start of a sequence.

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

An interactive component designed for selecting a numeric value within a defined range. Users typically adjust the value using left/right arrow keys; `Home` and `End` jump to the minimum and the maximum, and typing digits enters a value directly.

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

### `TextInput` Class

An interactive component for capturing a text string from the user. It supports full cursor navigation (`Left`/`Right`, `Home`, `End`), `Backspace`, `Delete`, multi-byte UTF-8 text, an optional placeholder, maximum length constraint, and external validation via `ValidatorInterface`.

-   **Constructor**:
    ```php
    public function __construct(
        string $prompt,
        string $default = '',
        ?ValidatorInterface $validator = null,
        ?string $placeholder = null,
        ?int $maxLength = null
    )
    ```
    -   `$prompt` (string): The message displayed to the user asking for input.
    -   `$default` (string): Optional. The initial text value (default: `''`).
    -   `$validator` (ValidatorInterface|null): Optional. A validator instance. If provided, input will only be submitted when valid.
    -   `$placeholder` (string|null): Optional. Placeholder text shown when input is empty.
    -   `$maxLength` (int|null): Optional. Maximum allowed character length.

### `PasswordInput` Class

An interactive component for secret/password input. Inherits full cursor navigation and validation from `TextInput`, but masks typed characters in the display with asterisks `*` (or a custom mask string, or hides them completely if mask is set to empty string).

-   **Constructor**:
    ```php
    public function __construct(
        string $prompt,
        ?ValidatorInterface $validator = null,
        ?string $placeholder = null,
        ?int $maxLength = null,
        string $mask = '*'
    )
    ```
    -   `$prompt` (string): The message displayed to the user asking for input.
    -   `$validator` (ValidatorInterface|null): Optional. A validator instance.
    -   `$placeholder` (string|null): Optional. Placeholder text shown when input is empty.
    -   `$maxLength` (int|null): Optional. Maximum allowed character length.
    -   `$mask` (string): Masking character (default: `'*'`). Pass an empty string `''` to hide typed characters completely (silent input).

### `Textarea` Class

An interactive multi-line text input component for capturing larger blocks of text (descriptions, notes, messages). It features a bordered viewport box with automatic vertical scrolling indicators, 2D cursor navigation across lines (`Up`/`Down`, `Left`/`Right`, `Home`/`End`, `PageUp`/`PageDown`), line insertion on `Enter`, multi-byte UTF-8 support, optional placeholder, maximum length constraint with a live counter, and external validation via `ValidatorInterface`.

Submission is performed by pressing `Esc` (or `Ctrl+D`).

-   **Constructor**:
    ```php
    public function __construct(
        string $prompt,
        string $default = '',
        ?ValidatorInterface $validator = null,
        ?string $placeholder = null,
        ?int $maxLength = null,
        int $rows = 5,
        int $cols = 50,
        string $instruction = 'Enter for new line, Esc to submit'
    )
    ```
    -   `$prompt` (string): The message displayed to the user asking for input.
    -   `$default` (string): Optional. Initial text value (default: `''`).
    -   `$validator` (ValidatorInterface|null): Optional. Validator instance to check input validity.
    -   `$placeholder` (string|null): Optional. Placeholder text shown when input is empty.
    -   `$maxLength` (int|null): Optional. Maximum allowed character length.
    -   `$rows` (int): Optional. Number of visible text rows inside the box (default: `5`).
    -   `$cols` (int): Optional. Inner width of visible text rows (default: `50`).
    -   `$instruction` (string): Optional. Instruction hint displayed below the textarea (default: `'Enter for new line, Esc to submit'`).

-   **Result**: `getResult(): string` returns the entered multi-line text.

### `Checkbox` Class

An interactive component for a single yes/no choice. `Space` toggles the checkbox and `Enter` confirms it.

-   **Constructor**:
    ```php
    public function __construct(
        string $label,
        bool $default_checked = false,
        string $instruction = 'Press Space to toggle, Enter to confirm'
    )
    ```
    -   `$label` (string): The text displayed next to the checkbox.
    -   `$default_checked` (bool): Optional. The initial state (default: `false`).
    -   `$instruction` (string): Optional. The instruction message shown under the checkbox.

-   **Result**: `getResult(): bool` returns `true` if the checkbox is checked.

### `Confirm` Class

An interactive binary confirmation prompt. Users toggle or select between two choices (by default `Yes` and `No`) using arrow keys (`Left`/`Right`, `Up`/`Down`), `Tab`, `Space`, or keyboard shortcuts (`y`/`n` or the initial letter of localized labels), and confirm with `Enter`.

-   **Constructor**:
    ```php
    public function __construct(
        string $label,
        bool $default = true,
        string $yes = 'Yes',
        string $no = 'No',
        string $instruction = 'Use ← → / y/n to select, Enter to confirm'
    )
    ```
    -   `$label` (string): The prompt/question displayed to the user.
    -   `$default` (bool): Optional. The initial state (default: `true`).
    -   `$yes` (string): Optional. Label for the affirmative option (default: `'Yes'`).
    -   `$no` (string): Optional. Label for the negative option (default: `'No'`).
    -   `$instruction` (string): Optional. The instruction message shown under the choices.

-   **Result**: `getResult(): bool` returns `true` if affirmative is selected, `false` otherwise.

### `Menu` Class

An interactive single-choice menu. Users move through the items with `Up`/`Down` (`Home` and `End` jump to the first and the last item), select the highlighted item with `Enter`, or cancel with `Esc`.

-   **Constructor**:
    ```php
    public function __construct(
        string $title,
        array $items,
        string $navigationInstruction = 'Use ↑↓ to navigate, Enter to select, ESC to cancel'
    )
    ```
    -   `$title` (string): The title displayed above the menu.
    -   `$items` (array): A list of item labels (strings).
    -   `$navigationInstruction` (string): Optional. The instruction message shown under the items.

-   **Result**: `getResult(): ?int` returns the zero-based index of the chosen item, or `null` if the menu was cancelled with `Esc`.

### `MultiSelect` Class

An interactive multiple-selection menu component. Users navigate through options with arrow keys (`Up`/`Down`) or `Home`/`End`, toggle items with `Space`, toggle all items with `a`, confirm with `Enter`, or cancel with `Esc`. Supports default selections as well as minimum and maximum selection limits.

-   **Constructor**:
    ```php
    public function __construct(
        string $title,
        array $options,
        array $default = [],
        ?int $min = null,
        ?int $max = null,
        string $instruction = 'Use ↑↓ to navigate, Space to toggle, a to toggle all, Enter to confirm, ESC to cancel'
    )
    ```
    -   `$title` (string): The title/prompt displayed above the list.
    -   `$options` (array): Array of options (indexed list or associative `['key' => 'Label']`).
    -   `$default` (array): Array of keys/indices pre-selected by default.
    -   `$min` (int|null): Optional. Minimum required number of selected items.
    -   `$max` (int|null): Optional. Maximum allowed number of selected items.
    -   `$instruction` (string): Optional navigation instructions text.

-   **Results**:
    -   `getResult(): ?array`: Returns array of selected keys/indices, or `null` if cancelled with `Esc`.
    -   `getSelectedValues(): array`: Returns array of selected label values.

### `SearchSelect` Class

An interactive searchable selection component. Allows users to type a search query to filter options dynamically in real time, scroll through matches using `Up`/`Down` arrow keys or `Home`/`End`, select the focused item with `Enter`, or cancel with `Esc`.

-   **Constructor**:
    ```php
    public function __construct(
        string $title,
        array $options,
        ?string $placeholder = 'Type to search...',
        int $limit = 7,
        string $instruction = 'Type to filter, ↑↓ to navigate, Enter to select, ESC to cancel'
    )
    ```
    -   `$title` (string): The title/prompt displayed above the component.
    -   `$options` (array): Array of options (indexed list or associative `['key' => 'Label']`).
    -   `$placeholder` (string|null): Optional placeholder text for the search field when empty.
    -   `$limit` (int): Maximum number of visible items in the filtered list before scrolling.
    -   `$instruction` (string): Optional instructions text.

-   **Results**:
    -   `getResult(): int|string|null`: Returns the selected option's key, or `null` if cancelled or no match.
    -   `getSelectedValue(): ?string`: Returns the selected option's label string.

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

### `Terminal` Class

`ChernegaSergiy\TerminalUi\Terminal\Terminal` encapsulates low-level terminal I/O, raw mode management, alternate screen buffering, cursor visibility, and terminal geometry detection.

- **Window Dimensions**:
  - `getWidth(): int`: Returns the terminal width in columns (defaults to `80`).
  - `getHeight(): int`: Returns the terminal height in rows (defaults to `24`).
  - `getDimensions(): array{0: int, 1: int}`: Returns an array containing `[width, height]`.
  - `setDimensions(?int $width = null, ?int $height = null): void`: Manually overrides dimensions (useful in tests or headless runners). Pass `null` to restore auto-detection.
  - `resetDimensions(): void`: Resets both custom overrides and cached dimensions.
- **Detection Strategy**: Probes `COLUMNS` and `LINES` environment variables, falls back to `stty size` over active input streams, then `tput cols` / `tput lines`, and finally defaults to `80x24`.
- **Dynamic Resize Handling**: When the `pcntl` extension is available, traps `SIGWINCH` signals to automatically invalidate cached dimensions whenever the user resizes the window.

### `SignalException` Class

`ChernegaSergiy\TerminalUi\Terminal\SignalException` extends `RuntimeException`. It is thrown when the process receives `SIGINT`, `SIGTERM` or `SIGHUP` while a component is running (this requires the optional `pcntl` extension). The terminal is already restored when the exception reaches your code.

-   `getSignal(): int` returns the number of the received signal.
-   The exception code is `128 + signal`, the conventional exit status after a signal, so it can be passed straight to `exit()`.

```php
try {
    $selected = Runner::run($menu);
} catch (SignalException $e) {
    exit($e->getCode());
}
```

### Colours and `NO_COLOR`

Components style their output through `ChernegaSergiy\TerminalUi\Terminal\Ansi`. Colours are switched off automatically when the `NO_COLOR` environment variable is set to a non-empty value (see [no-color.org](https://no-color.org)) or when `STDOUT` is not a terminal, so escape codes do not end up in log files or pipes. `Ansi::setEnabled(true|false)` overrides the detection (`null` restores it); this is handy in tests, where `render()` output should not depend on the environment. Use `Ansi::style(Ansi::BOLD_YELLOW, 'text')` in your own components to get the same behaviour.

## Usage Example

Here's a simple example demonstrating how to create a form with `Slider` and `NumberInput` components and run it, including external validation for `NumberInput`:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use ChernegaSergiy\TerminalUi\Component\Form;
use ChernegaSergiy\TerminalUi\Component\NumberInput;
use ChernegaSergiy\TerminalUi\Component\Slider;
use ChernegaSergiy\TerminalUi\Runner;
use ChernegaSergiy\TerminalUi\Validator\CallbackValidator;

// Create components
$slider1 = new Slider('Choose a value for Slider 1', 0, 100, 50, 5);
$slider2 = new Slider('Choose a value for Slider 2', 10, 20, 15, 1);

// Create a validator for age input
$ageValidator = new CallbackValidator(function (string $value): ?string {
    if ('' === $value) {
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
```

To run this example, save it as a PHP file (e.g., `my_form_app.php`) in your project's root directory and execute it from your terminal:

```bash
php my_form_app.php
```

This will launch the interactive form, allowing you to navigate between components, input values, and see the collected results upon completion.

## Ready-to-Run Examples

The repository includes a dedicated [`examples/`](examples) directory with interactive CLI scenarios:

- **Login Form ([`examples/login-form.php`](examples/login-form.php))**:
  Demonstrates form aggregation with text inputs, masked passwords, checkboxes, and validation rules:
  ```bash
  php examples/login-form.php
  ```

  ![Login Form Demo](docs/demo/login.gif)

- **Interactive Menu ([`examples/interactive-menu.php`](examples/interactive-menu.php))**:
  Demonstrates a console navigation loop with options, action dispatching, and cancellation:
  ```bash
  php examples/interactive-menu.php
  ```

  ![Interactive Menu Demo](docs/demo/menu.gif)

- **Setup Wizard ([`examples/wizard.php`](examples/wizard.php))**:
  Demonstrates a step-by-step interactive CLI setup process sequentially composing text fields, searchable dropdowns, multi-select checkboxes, and range sliders:
  ```bash
  php examples/wizard.php
  ```

  ![Setup Wizard Demo](docs/demo/wizard.gif)

- **Confirmation Prompt ([`examples/confirm.php`](examples/confirm.php))**:
  Demonstrates binary confirmation prompts with horizontal selection, arrow key navigation, keyboard shortcuts, and custom option labels:
  ```bash
  php examples/confirm.php
  ```

  ![Confirmation Prompt Demo](docs/demo/confirm.gif)

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