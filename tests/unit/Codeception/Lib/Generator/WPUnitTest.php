<?php namespace Codeception\Lib\Generator;

use Codeception\TestCase\WPTestCase;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;

class WPUnitTest extends \Codeception\Test\Unit
{

    use SnapshotAssertions;

    /**
     * A backup of the current PHPUnit Series env var.
     * @var array|false|string
     */
    protected $phpunitSeriesEnv;
    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * It should scaffold PHPUnit v8 compatible code on series 8
     *
     * @test
     * @dataProvider phpUnitEq8Series
     */
    public function should_scaffold_php_unit_v_8_code_on_series_8($series)
    {
        $this->setPhpUnitSeriesTo('8.0');
        $settings = ['namespace' => 'Acme'];
        $name = 'SomeClass';
        $generator = new WPUnit($settings, $name, WPTestCase::class);

        $code = $generator->produce();

        $this->assertMatchesCodeSnapshot($code, 'php');
    }

    protected function setPhpUnitSeriesTo($series)
    {
        putenv("WPBROWSER_PHPUNIT_SERIES={$series}");
    }

    public function phpUnitLt8Series()
    {
        return [
            '5.5' => ['5.5'],
            '5.5.5' => ['5.5'],
            '6.2' => ['6.2'],
            '6.2.3' => ['6.2'],
            '7.5' => ['7.5'],
            '7.5.6' => ['7.5'],
        ];
    }

    /**
     * It should scaffold PHPUnit lt 8.0 compatible code on series lt 8
     *
     * @test
     * @dataProvider phpUnitLt8Series
     */
    public function should_scaffold_php_unit_lt_8_0_compatible_code_on_series_lt_8($series)
    {
        $this->setPhpUnitSeriesTo($series);
        $settings = ['namespace' => 'Acme'];
        $name = 'SomeClass';
        $generator = new WPUnit($settings, $name, WPTestCase::class);

        $code = $generator->produce();

        $this->assertMatchesCodeSnapshot($code, 'php');
    }

    public function phpUnitEq8Series()
    {
        return [
            '8.0' => ['8.0'],
            '8.0.4' => ['8.0.4'],
            '8.1' => ['8.1'],
            '8.1.6' => ['8.1.6'],
        ];
    }

    protected function _before()
    {
        $this->phpunitSeriesEnv = getenv('WPBROWSER_PHPUNIT_SERIES');
    }

    protected function _after()
    {
        putenv('WPBROWSER_PHPUNIT_SERIES='.$this->phpunitSeriesEnv);
    }
}
