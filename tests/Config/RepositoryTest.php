<?php
namespace Marvin\Config\Repository;

use Marvin\Config\Repository;

class RepositoryTest extends \PHPUnit_Framework_TestCase
{

    protected $configs = [
        'name'  => 'Marvin',
        'hosts' => [
            'apache'
        ],
    ];

    public function testSetItemsList()
    {
        $configRepository = new Repository($this->configs);
        $this->assertAttributeEquals($this->configs, 'items', $configRepository);
    }

    public function testReturnIfHasKeyInConfigs()
    {
        $configs          = [
            'name'        => 'Marvin',
            'myConfig'    => 'New Config',
            'otherConfig' => 'More configurations'
        ];
        $configRepository = new Repository($configs);
        $this->assertTrue($configRepository->has('name'));
        $this->assertTrue($configRepository->has('myConfig'));
        $this->assertTrue($configRepository->has('otherConfig'));
        $this->assertNotTrue($configRepository->has('null'));
    }

    public function testGetValueUsingKey()
    {
        $configRepository = new Repository($this->configs);
        $this->assertEquals('Marvin', $configRepository->get('name'));
    }

    public function testReturnValueDefaultIfValueIsNull()
    {
        $configRepository = new Repository($this->configs);
        $this->assertEquals('Marvin', $configRepository->get('package', 'Marvin'));
    }

    public function testSetKeyAndValueInConfigList()
    {
        $configRepository = new Repository($this->configs);

        $configRepository->set('host', 'apache');
        $configRepository->set('name', 'Trillian');

        $this->assertEquals('apache', $configRepository->get('host'));
        $this->assertEquals('Trillian', $configRepository->get('name'));
    }

    public function testReturnCompleteConfigurationList()
    {
        $configRepository = new Repository($this->configs);

        $this->assertEquals($this->configs, $configRepository->all());
    }

}
