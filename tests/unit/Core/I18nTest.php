<?php
/**
 * Tests for internationalization (i18n) setup.
 *
 * @package APD\Tests\Unit\Core
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Core;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

/**
 * Class I18nTest
 *
 * Tests for the i18n system including POT file generation and text domain setup.
 */
class I18nTest extends TestCase {

    use MockeryPHPUnitIntegration;

    /**
     * POT file path.
     *
     * @var string
     */
    private string $pot_file;

    /**
     * POT file contents.
     *
     * @var string
     */
    private string $pot_contents;

    /**
     * Set up test fixtures.
     */
    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();

        $this->pot_file = dirname( __DIR__, 3 ) . '/languages/all-purpose-directory.pot';

        if ( file_exists( $this->pot_file ) ) {
            $this->pot_contents = file_get_contents( $this->pot_file );
        } else {
            $this->pot_contents = '';
        }
    }

    /**
     * Tear down test fixtures.
     */
    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * Test that the POT file exists.
     */
    public function test_pot_file_exists(): void {
        $this->assertFileExists(
            $this->pot_file,
            'POT file should exist at languages/all-purpose-directory.pot'
        );
    }

    /**
     * Test POT file has correct Project-Id-Version header.
     */
    public function test_pot_file_has_project_id_version(): void {
        $this->assertStringContainsString(
            'Project-Id-Version: All Purpose Directory',
            $this->pot_contents,
            'POT file should have correct Project-Id-Version header'
        );
    }

    /**
     * Test POT file has correct text domain in headers.
     */
    public function test_pot_file_has_keyword_list(): void {
        $this->assertStringContainsString(
            'X-Poedit-KeywordsList:',
            $this->pot_contents,
            'POT file should have Poedit keyword list'
        );
    }

    /**
     * Test POT file has plural forms header.
     */
    public function test_pot_file_has_plural_forms(): void {
        $this->assertStringContainsString(
            'Plural-Forms: nplurals=2; plural=(n != 1);',
            $this->pot_contents,
            'POT file should have plural forms header'
        );
    }

    /**
     * Test POT file has UTF-8 charset.
     */
    public function test_pot_file_has_utf8_charset(): void {
        $this->assertStringContainsString(
            'Content-Type: text/plain; charset=UTF-8',
            $this->pot_contents,
            'POT file should have UTF-8 charset'
        );
    }

    /**
     * Test POT file has report-msgid-bugs-to header.
     */
    public function test_pot_file_has_bug_report_url(): void {
        $this->assertStringContainsString(
            'Report-Msgid-Bugs-To:',
            $this->pot_contents,
            'POT file should have bug report URL'
        );
    }

    /**
     * Test POT file has POT-Creation-Date header.
     */
    public function test_pot_file_has_creation_date(): void {
        $this->assertMatchesRegularExpression(
            '/POT-Creation-Date: \d{4}-\d{2}-\d{2}/',
            $this->pot_contents,
            'POT file should have creation date'
        );
    }

    /**
     * Test POT file has translator comments.
     */
    public function test_pot_file_has_translator_comments(): void {
        $this->assertStringContainsString(
            '#. translators:',
            $this->pot_contents,
            'POT file should contain translator comments'
        );
    }

    /**
     * Test POT file has context strings.
     */
    public function test_pot_file_has_context_strings(): void {
        $this->assertStringContainsString(
            'msgctxt',
            $this->pot_contents,
            'POT file should contain context strings (msgctxt)'
        );
    }

    /**
     * Test POT file has plural strings.
     */
    public function test_pot_file_has_plural_strings(): void {
        $this->assertStringContainsString(
            'msgid_plural',
            $this->pot_contents,
            'POT file should contain plural strings'
        );
    }

    /**
     * Test POT file has minimum expected string count.
     */
    public function test_pot_file_has_minimum_strings(): void {
        preg_match_all( '/^msgid "(?!").+"/m', $this->pot_contents, $matches );
        $string_count = count( $matches[0] );

        $this->assertGreaterThan(
            500,
            $string_count,
            'POT file should contain at least 500 translatable strings'
        );
    }

    /**
     * Test POT file contains core plugin strings.
     */
    public function test_pot_file_contains_core_strings(): void {
        $expected_strings = [
            'Listings',
            'Listing',
            'Add New',
            'Reviews',
            'Categories',
        ];

        foreach ( $expected_strings as $string ) {
            $this->assertStringContainsString(
                'msgid "' . $string . '"',
                $this->pot_contents,
                "POT file should contain core string: {$string}"
            );
        }
    }

    /**
     * Test POT file contains admin strings.
     */
    public function test_pot_file_contains_admin_strings(): void {
        $this->assertStringContainsString(
            'Listing Fields',
            $this->pot_contents,
            'POT file should contain admin meta box strings'
        );
    }

    /**
     * Test POT file contains frontend strings.
     */
    public function test_pot_file_contains_frontend_strings(): void {
        $expected_strings = [
            'Submit',
            'Search',
            'My Listings',
            'Dashboard',
        ];

        foreach ( $expected_strings as $string ) {
            $this->assertStringContainsString(
                $string,
                $this->pot_contents,
                "POT file should contain frontend string: {$string}"
            );
        }
    }

    /**
     * Test POT file contains email strings.
     */
    public function test_pot_file_contains_email_strings(): void {
        $this->assertStringContainsString(
            'templates/emails',
            $this->pot_contents,
            'POT file should contain references to email templates'
        );
    }

    /**
     * Test POT file contains post type context strings.
     */
    public function test_pot_file_contains_post_type_contexts(): void {
        $this->assertStringContainsString(
            'msgctxt "post type general name"',
            $this->pot_contents,
            'POT file should contain post type context strings'
        );
    }

    /**
     * Test POT file contains taxonomy context strings.
     */
    public function test_pot_file_contains_taxonomy_contexts(): void {
        $this->assertStringContainsString(
            'msgctxt "taxonomy general name"',
            $this->pot_contents,
            'POT file should contain taxonomy context strings'
        );
    }

    /**
     * Test POT file does not contain PHP code.
     */
    public function test_pot_file_does_not_contain_php_code(): void {
        $this->assertStringNotContainsString(
            '<?php',
            $this->pot_contents,
            'POT file should not contain PHP code'
        );
    }

    /**
     * Test POT file does not contain JavaScript code.
     */
    public function test_pot_file_does_not_contain_javascript(): void {
        $this->assertStringNotContainsString(
            'function(',
            $this->pot_contents,
            'POT file should not contain JavaScript code'
        );
    }

    /**
     * Test POT file has source file references.
     */
    public function test_pot_file_has_source_references(): void {
        $this->assertMatchesRegularExpression(
            '/#: [a-zA-Z0-9_\/\.\-]+:\d+/',
            $this->pot_contents,
            'POT file should contain source file references with line numbers'
        );
    }

    /**
     * Test POT file references multiple source directories.
     */
    public function test_pot_file_references_multiple_directories(): void {
        $expected_paths = [
            'src/',
            'includes/',
            'templates/',
        ];

        foreach ( $expected_paths as $path ) {
            $this->assertStringContainsString(
                $path,
                $this->pot_contents,
                "POT file should contain references to {$path}"
            );
        }
    }

    /**
     * Test TRANSLATING.md documentation exists.
     */
    public function test_translating_documentation_exists(): void {
        $doc_file = dirname( __DIR__, 3 ) . '/languages/TRANSLATING.md';
        $this->assertFileExists(
            $doc_file,
            'TRANSLATING.md should exist in languages folder'
        );
    }

    /**
     * Test TRANSLATING.md contains key sections.
     */
    public function test_translating_documentation_has_key_sections(): void {
        $doc_file    = dirname( __DIR__, 3 ) . '/languages/TRANSLATING.md';
        $doc_content = file_get_contents( $doc_file );

        $expected_sections = [
            '# Translating All Purpose Directory',
            'Text Domain',
            'all-purpose-directory',
            'Poedit',
            'Loco Translate',
            'File Naming Convention',
            'npm run i18n:pot',
        ];

        foreach ( $expected_sections as $section ) {
            $this->assertStringContainsString(
                $section,
                $doc_content,
                "TRANSLATING.md should contain section: {$section}"
            );
        }
    }

    /**
     * Test package.json has i18n:pot script.
     */
    public function test_package_json_has_i18n_script(): void {
        $package_file = dirname( __DIR__, 3 ) . '/package.json';
        $this->assertFileExists( $package_file );

        $package_content = file_get_contents( $package_file );
        $package_data    = json_decode( $package_content, true );

        $this->assertArrayHasKey( 'scripts', $package_data );
        $this->assertArrayHasKey( 'i18n:pot', $package_data['scripts'] );
        $this->assertStringContainsString(
            'wp-pot',
            $package_data['scripts']['i18n:pot'],
            'i18n:pot script should use wp-pot'
        );
    }

    /**
     * Test language folder structure.
     */
    public function test_language_folder_structure(): void {
        $language_dir = dirname( __DIR__, 3 ) . '/languages';

        $this->assertDirectoryExists( $language_dir );
        $this->assertFileExists( $language_dir . '/all-purpose-directory.pot' );
        $this->assertFileExists( $language_dir . '/TRANSLATING.md' );
    }
}
