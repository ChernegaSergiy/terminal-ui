<?php

declare(strict_types=1);

namespace Tests\Unit\Component;

use ChernegaSergiy\TerminalUi\Component\Confirm;
use ChernegaSergiy\TerminalUi\Component\Form;
use ChernegaSergiy\TerminalUi\Terminal\Ansi;
use ChernegaSergiy\TerminalUi\Terminal\Key;
use PHPUnit\Framework\TestCase;

final class ConfirmTest extends TestCase
{
    protected function setUp() : void
    {
        Ansi::setEnabled(false);
    }

    public function testDefaultState() : void
    {
        $confirm = new Confirm('Are you sure?');

        $this->assertSame('Are you sure?', $confirm->getLabel());
        $this->assertTrue($confirm->getResult());
        $this->assertTrue($confirm->getValue());
        $this->assertFalse($confirm->isDone());
        $this->assertTrue($confirm->isValid());
        $this->assertSame('Yes', $confirm->getYes());
        $this->assertSame('No', $confirm->getNo());
        $this->assertSame('Use ← → / y/n to select, Enter to confirm', $confirm->getInstruction());

        $rendered = $confirm->render();
        $this->assertStringContainsString('Are you sure?', $rendered);
        $this->assertStringContainsString('● Yes  ○ No', $rendered);
        $this->assertStringContainsString('Use ← → / y/n to select, Enter to confirm', $rendered);
    }

    public function testDefaultFalseState() : void
    {
        $confirm = new Confirm('Delete database?', default: false);

        $this->assertFalse($confirm->getResult());
        $this->assertFalse($confirm->getValue());

        $rendered = $confirm->render();
        $this->assertStringContainsString('○ Yes  ● No', $rendered);
    }

    public function testCustomLabelsAndInstruction() : void
    {
        $confirm = new Confirm(
            'Видалити файл?',
            default: true,
            yes: 'Так',
            no: 'Ні',
            instruction: 'Стрілки для вибору, Enter для підтвердження'
        );

        $this->assertSame('Так', $confirm->getYes());
        $this->assertSame('Ні', $confirm->getNo());
        $this->assertSame('Стрілки для вибору, Enter для підтвердження', $confirm->getInstruction());

        $rendered = $confirm->render();
        $this->assertStringContainsString('● Так  ○ Ні', $rendered);
        $this->assertStringContainsString('Стрілки для вибору, Enter для підтвердження', $rendered);
    }

    public function testEmptyInstructionOmitsInstructionLine() : void
    {
        $confirm = new Confirm('Proceed?', instruction: '');

        $rendered = $confirm->render();
        $this->assertSame("Proceed?\n\n● Yes  ○ No", $rendered);
    }

    public function testArrowKeysNavigation() : void
    {
        $confirm = new Confirm('Continue?', default: true);

        // Right selects No (false)
        $confirm->handleKey(Key::Right);
        $this->assertFalse($confirm->getResult());
        $this->assertStringContainsString('○ Yes  ● No', $confirm->render());

        // Left selects Yes (true)
        $confirm->handleKey(Key::Left);
        $this->assertTrue($confirm->getResult());
        $this->assertStringContainsString('● Yes  ○ No', $confirm->render());

        // Down selects No (false)
        $confirm->handleKey(Key::Down);
        $this->assertFalse($confirm->getResult());

        // Up selects Yes (true)
        $confirm->handleKey(Key::Up);
        $this->assertTrue($confirm->getResult());
    }

    public function testTabKeyToggle() : void
    {
        $confirm = new Confirm('Continue?', default: true);

        $confirm->handleKey(Key::Tab);
        $this->assertFalse($confirm->getResult());

        $confirm->handleKey(Key::Tab);
        $this->assertTrue($confirm->getResult());
    }

    public function testSpaceKeyToggle() : void
    {
        $confirm = new Confirm('Continue?', default: false);

        $confirm->handleKey(' ');
        $this->assertTrue($confirm->getResult());

        $confirm->handleKey(' ');
        $this->assertFalse($confirm->getResult());
    }

    public function testYAndNKeys() : void
    {
        $confirm = new Confirm('Overwrite file?', default: true);

        $confirm->handleKey('n');
        $this->assertFalse($confirm->getResult());

        $confirm->handleKey('y');
        $this->assertTrue($confirm->getResult());

        $confirm->handleKey('N');
        $this->assertFalse($confirm->getResult());

        $confirm->handleKey('Y');
        $this->assertTrue($confirm->getResult());
    }

    public function testCustomInitialsKeys() : void
    {
        $confirm = new Confirm('Зберегти зміни?', default: false, yes: 'Так', no: 'Ні');

        // Ukrainian 'т' selects 'Так' (true)
        $confirm->handleKey('т');
        $this->assertTrue($confirm->getResult());

        // Ukrainian 'н' selects 'Ні' (false)
        $confirm->handleKey('н');
        $this->assertFalse($confirm->getResult());

        // Capital letters 'Т' and 'Н'
        $confirm->handleKey('Т');
        $this->assertTrue($confirm->getResult());

        $confirm->handleKey('Н');
        $this->assertFalse($confirm->getResult());

        // Standard 'y' and 'n' still work as fallbacks
        $confirm->handleKey('y');
        $this->assertTrue($confirm->getResult());

        $confirm->handleKey('n');
        $this->assertFalse($confirm->getResult());
    }

    public function testExplicitYAndNTakePrecedenceOverCustomInitials() : void
    {
        $confirm = new Confirm('Proceed to next?', default: true, yes: 'Next', no: 'Back');

        // 'n' must select false, not match 'Next' ($firstYes)
        $confirm->handleKey('n');
        $this->assertFalse($confirm->getResult());

        // 'y' selects true
        $confirm->handleKey('y');
        $this->assertTrue($confirm->getResult());
    }

    public function testEnterKeyConfirms() : void
    {
        $confirm = new Confirm('Are you sure?');

        $this->assertFalse($confirm->isDone());
        $confirm->handleKey(Key::Enter);
        $this->assertTrue($confirm->isDone());
        $this->assertTrue($confirm->getResult());
    }

    public function testRenderWithAnsiEnabled() : void
    {
        Ansi::setEnabled(true);

        $confirm = new Confirm('Proceed?', default: true);
        $rendered = $confirm->render();

        $this->assertStringContainsString("\033[1;32m● Yes\033[0m", $rendered);
        $this->assertStringContainsString("\033[90m○ No\033[0m", $rendered);

        $confirm->handleKey(Key::Right);
        $renderedNo = $confirm->render();

        $this->assertStringContainsString("\033[90m○ Yes\033[0m", $renderedNo);
        $this->assertStringContainsString("\033[1;31m● No\033[0m", $renderedNo);
    }

    public function testIntegrationWithForm() : void
    {
        $confirm = new Confirm('Agree to terms?', default: false);
        $form = new Form('Survey', [
            'terms' => $confirm,
        ]);

        $this->assertFalse($form->isDone());

        // Toggle using Space
        $form->handleKey(' ');
        $this->assertTrue($confirm->getResult());

        // Confirm and submit
        $form->handleKey(Key::Enter);
        $this->assertTrue($form->isDone());
        $this->assertSame(['terms' => true], $form->getResult());
    }
}
