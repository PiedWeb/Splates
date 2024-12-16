<?php

namespace PiedWeb\Splates\Tests\Template;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use PiedWeb\Splates\Engine;
use PiedWeb\Splates\Template\Theme;
use Throwable;

final class ThemeTest extends TestCase
{
    private Engine $engine;

    private ?string $result = null;

    private ?Throwable $exception = null;

    public function testEngine_renders_with_single_themes(): void
    {
        $this->given_a_directory_structure_is_setup_like('templates', ['main.php' => '<html></html>']);
        $this->given_an_engine_is_created_with_theme(Theme::new($this->vfsPath('templates')));
        $this->when_the_engine_renders('main');
        $this->then_the_rendered_template_matches('<html></html>');
    }

    public function testEngine_renders_with_theme_hierarchy(): void
    {
        $this->given_a_directory_structure_is_setup_like('templates', [
            'parent' => [
                'main.php' => '<?php $this->layout("layout") ?>parent',
                'layout.php' => '<html>parent: <?=$this->section("content")?></html>',
            ],
            'child' => [
                'layout.php' => '<html>child: <?=$this->section("content")?></html>',
            ],
        ]);
        $this->given_an_engine_is_created_with_theme(Theme::hierarchy([
            Theme::new($this->vfsPath('templates/parent'), 'Parent'),
            Theme::new($this->vfsPath('templates/child'), 'Child'),
        ]));
        $this->when_the_engine_renders('main');
        $this->then_the_rendered_template_matches('<html>child: parent</html>');
    }

    public function testDuplicate_theme_names_in_hierarchies_are_not_allowed(): void
    {
        $this->when_a_theme_is_created_like(function (): void {
            Theme::hierarchy([
                Theme::new('templates/a'),
                Theme::new('templates/b'),
            ]);
        });
        $this->then_an_exception_is_thrown_with_message('Duplicate theme names in hierarchies are not allowed. Received theme names: [Default, Default].');
    }

    public function testNested_hierarchies_are_not_allowed(): void
    {
        $this->when_a_theme_is_created_like(function (): void {
            Theme::hierarchy([
                Theme::hierarchy([Theme::new('templates', 'A'), Theme::new('templates', 'B')]),
            ]);
        });
        $this->then_an_exception_is_thrown_with_message('Nested theme hierarchies are not allowed, make sure to use Theme::new when creating themes in your hierarchy. Theme B is already in a hierarchy.');
    }

    public function testEmpty_hierarchies_are_not_allowed(): void
    {
        $this->when_a_theme_is_created_like(function (): void {
            Theme::hierarchy([]);
        });
        $this->then_an_exception_is_thrown_with_message('Empty theme hierarchies are not allowed.');
    }

    public function testTemplate_not_found_errors_reference_themes_checked(): void
    {
        $this->given_a_directory_structure_is_setup_like('templates', []);
        $this->given_an_engine_is_created_with_theme(Theme::hierarchy([
            Theme::new($this->vfsPath('templates/one'), 'One'),
            Theme::new($this->vfsPath('templates/two'), 'Two'),
        ]));
        $this->when_the_engine_renders('main');
        $this->then_an_exception_is_thrown_with_message('The template "main" was not found in the following themes: Two:vfs://templates/two/main.php, One:vfs://templates/one/main.php');
    }

    /**
     * @param array<mixed> $directoryStructure
     */
    private function given_a_directory_structure_is_setup_like(string $rootDir, array $directoryStructure): void
    {
        vfsStream::setup($rootDir);
        vfsStream::create($directoryStructure);
    }

    private function given_an_engine_is_created_with_theme(Theme $theme): void
    {
        $this->engine = Engine::fromTheme($theme);
    }

    private function when_a_theme_is_created_like(callable $fn): void
    {
        try {
            $fn();
        } catch (Throwable $throwable) {
            $this->exception = $throwable;
        }
    }

    private function vfsPath(string $path): string
    {
        return vfsStream::url($path);
    }

    /**
     * @param array<mixed> $data
     */
    private function when_the_engine_renders(string $templateName, array $data = []): void
    {
        try {
            $this->result = $this->engine->render($templateName, $data);
        } catch (Throwable $throwable) {
            $this->exception = $throwable;
        }
    }

    private function then_the_rendered_template_matches(string $expected): void
    {
        if ($this->exception instanceof Throwable) {
            throw $this->exception;
        }

        $this->assertSame($expected, $this->result);
    }

    private function then_an_exception_is_thrown_with_message(string $expectedMessage): void
    {
        $this->assertNotNull($this->exception, 'Expected an exception to be thrown with message: ' . $expectedMessage);
        $this->assertSame($expectedMessage, $this->exception->getMessage(), 'Expected an exception to be thrown with message: ' . $expectedMessage);
    }
}
