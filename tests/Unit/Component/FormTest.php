<?php

declare(strict_types=1);

namespace Tests\Unit\Component;

use ChernegaSergiy\TerminalUi\Component\Checkbox;
use ChernegaSergiy\TerminalUi\Component\Form;
use ChernegaSergiy\TerminalUi\Component\NumberInput;
use ChernegaSergiy\TerminalUi\Component\TextInput;
use ChernegaSergiy\TerminalUi\Component\Textarea;
use ChernegaSergiy\TerminalUi\Terminal\Ansi;
use ChernegaSergiy\TerminalUi\Terminal\Key;
use ChernegaSergiy\TerminalUi\Validator\CallbackValidator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class FormTest extends TestCase
{
    protected function setUp() : void
    {
        Ansi::setEnabled(false);
    }

    public function testConstructorValidatesNonStringName() : void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Form fields must be keyed by name');
        new Form('Form', [0 => new Checkbox('Box')]);
    }

    public function testAddValidatesEmptyName() : void
    {
        $form = new Form('Form');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A form field needs a non-empty name.');
        $form->add('', new Checkbox('Box'));
    }

    public function testAddValidatesDuplicateName() : void
    {
        $form = new Form('Form');
        $form->add('agree', new Checkbox('Agree'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("The form already has a field named 'agree'.");
        $form->add('agree', new Checkbox('Agree again'));
    }

    public function testEmptyFormCompletesOnAnyKey() : void
    {
        $form = new Form('Empty Form');
        $this->assertFalse($form->isDone());

        $form->handleKey(Key::Enter);
        $this->assertTrue($form->isDone());
        $this->assertSame([], $form->getResult());
    }

    public function testNavigationWithTabUpDown() : void
    {
        $form = new Form('Nav Form', [
            'first' => new TextInput('First:'),
            'second' => new TextInput('Second:'),
            'third' => new TextInput('Third:'),
        ]);

        $rendered = $form->render();
        $this->assertStringContainsString('▶ First:', $rendered);

        // Tab moves to second
        $form->handleKey(Key::Tab);
        $this->assertStringContainsString('▶ Second:', $form->render());

        // Down moves to third
        $form->handleKey(Key::Down);
        $this->assertStringContainsString('▶ Third:', $form->render());

        // Tab wraps around to first
        $form->handleKey(Key::Tab);
        $this->assertStringContainsString('▶ First:', $form->render());

        // Up wraps around to third
        $form->handleKey(Key::Up);
        $this->assertStringContainsString('▶ Third:', $form->render());
    }

    public function testPassingKeysToFocusedComponent() : void
    {
        $input = new TextInput('Name:');
        $form = new Form('User', ['name' => $input]);

        $form->handleKey('A');
        $form->handleKey('l');
        $form->handleKey('i');

        $this->assertSame('Ali', $input->getValue());
        $this->assertSame(['name' => 'Ali'], $form->getResult());
    }

    public function testEnterAdvancesAndSubmitsWhenValid() : void
    {
        $form = new Form('Registration', [
            'name' => new TextInput('Name:', default: 'Bob'),
            'agree' => new Checkbox('Agree:', default_checked: true),
        ]);

        $this->assertTrue($form->isValid());

        // Enter on first field moves focus to second
        $form->handleKey(Key::Enter);
        $this->assertFalse($form->isDone());
        $this->assertStringContainsString('▶ [x] Agree:', $form->render());

        // Enter on last valid field completes form
        $form->handleKey(Key::Enter);
        $this->assertTrue($form->isDone());
        $this->assertSame(['name' => 'Bob', 'agree' => true], $form->getResult());
    }

    public function testEnterStaysOnInvalidComponent() : void
    {
        $validator = new CallbackValidator(static fn (string $v) : ?string => strlen($v) >= 2 ? null : 'Too short');
        $input = new TextInput('Code:', validator: $validator);
        $form = new Form('Code Form', ['code' => $input, 'opt' => new Checkbox('Opt')]);

        // Code is empty, so invalid
        $form->handleKey(Key::Enter);
        $this->assertFalse($form->isDone());
        $this->assertStringContainsString('▶ Code:', $form->render());
    }

    public function testSubmitFailedRedirectsToFirstInvalidField() : void
    {
        $ageValidator = new CallbackValidator(static fn (string $v) : ?string => (int) $v >= 18 ? null : 'Age >= 18');
        $ageInput = new NumberInput('Age:', $ageValidator); // starts with 0 (< 18)
        $checkbox = new Checkbox('Terms:', default_checked: true);

        $form = new Form('Application', [
            'age' => $ageInput,
            'terms' => $checkbox,
        ]);

        // Navigate directly to terms (last field)
        $form->handleKey(Key::Tab);
        $this->assertStringContainsString('▶ [x] Terms:', $form->render());

        // Press Enter on valid terms, but first field is invalid
        $form->handleKey(Key::Enter);
        $this->assertFalse($form->isDone());

        $rendered = $form->render();
        $this->assertStringContainsString('✗ Please fix the errors in the form.', $rendered);
        // Focus was returned to the first invalid field (age)
        $this->assertStringContainsString('▶ Age:', $rendered);
    }

    public function testTextareaNavigationWithinAndAcrossFields() : void
    {
        $form = new Form('Bio Form', [
            'first' => new TextInput('First:'),
            'area' => new Textarea('Area:', default: "Line 1\nLine 2"),
            'last' => new TextInput('Last:'),
        ]);

        $this->assertStringContainsString('▶ First:', $form->render());

        // Move to Textarea (focus index 1)
        $form->handleKey(Key::Tab);
        $this->assertStringContainsString('▶ Area:', $form->render());

        // Initial cursor in area is on row 1 (last row) because default was passed.
        // Up arrow moves up inside Textarea without changing Form focus.
        $form->handleKey(Key::Up);
        $this->assertStringContainsString('▶ Area:', $form->render());

        // Now cursor is at row 0 (top line). Up arrow moves to previous component (First).
        $form->handleKey(Key::Up);
        $this->assertStringContainsString('▶ First:', $form->render());

        // Down arrow from First moves focus to Area (cursor still at row 0)
        $form->handleKey(Key::Down);
        $this->assertStringContainsString('▶ Area:', $form->render());

        // Down arrow inside Textarea moves cursor to row 1 without changing Form focus
        $form->handleKey(Key::Down);
        $this->assertStringContainsString('▶ Area:', $form->render());

        // Down arrow at bottom line (row 1 >= lineCount - 1) moves focus to Last
        $form->handleKey(Key::Down);
        $this->assertStringContainsString('▶ Last:', $form->render());

        // Tab moves to First, then Tab moves to Area
        $form->handleKey(Key::Tab);
        $this->assertStringContainsString('▶ First:', $form->render());
        $form->handleKey(Key::Tab);
        $this->assertStringContainsString('▶ Area:', $form->render());

        // Tab from Textarea moves focus to Last
        $form->handleKey(Key::Tab);
        $this->assertStringContainsString('▶ Last:', $form->render());
    }

    public function testTextareaKeyInputAndEnterInForm() : void
    {
        $form = new Form('Notes Form', [
            'notes' => new Textarea('Notes:'),
        ]);

        $form->handleKey('A');
        $form->handleKey(Key::Enter);
        $form->handleKey('B');

        $this->assertFalse($form->isDone());
        $this->assertSame(['notes' => "A\nB"], $form->getResult());
    }

    public function testTextareaEscCompletionMovesToNextFieldWhenNotLast() : void
    {
        $form = new Form('Multi Form', [
            'notes' => new Textarea('Notes:', default: 'Intro'),
            'name' => new TextInput('Name:'),
        ]);

        $this->assertStringContainsString('▶ Notes:', $form->render());

        // Esc on Textarea completes it and moves to next field
        $form->handleKey(Key::Esc);
        $this->assertFalse($form->isDone());
        $this->assertStringContainsString('▶ Name:', $form->render());
    }

    public function testTextareaEscCompletionSubmitsFormWhenLastFieldAndValid() : void
    {
        $form = new Form('Feedback Form', [
            'name' => new TextInput('Name:', default: 'Alice'),
            'feedback' => new Textarea('Feedback:', default: 'Great product!'),
        ]);

        // Move to feedback (last field)
        $form->handleKey(Key::Tab);
        $this->assertStringContainsString('▶ Feedback:', $form->render());

        // Esc completes textarea and submits form since it is the last field and all fields are valid
        $form->handleKey(Key::Esc);
        $this->assertTrue($form->isDone());
        $this->assertSame([
            'name' => 'Alice',
            'feedback' => 'Great product!',
        ], $form->getResult());
    }

    public function testTextareaEscCompletionFailsWhenFormInvalid() : void
    {
        $validator = new CallbackValidator(static fn (string $v) : ?string => '' !== trim($v) ? null : 'Required');
        $form = new Form('Contact Form', [
            'email' => new TextInput('Email:', validator: $validator), // empty, invalid
            'message' => new Textarea('Message:', default: 'Hello'),
        ]);

        // Move focus to message (last field)
        $form->handleKey(Key::Tab);
        $this->assertStringContainsString('▶ Message:', $form->render());

        // Esc on message attempts submit, but email is invalid
        $form->handleKey(Key::Esc);
        $this->assertFalse($form->isDone());

        $rendered = $form->render();
        $this->assertStringContainsString('✗ Please fix the errors in the form.', $rendered);
        $this->assertStringContainsString('▶ Email:', $rendered);
    }
}

