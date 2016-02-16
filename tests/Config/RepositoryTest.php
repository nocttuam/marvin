<?php
namespace Marvin\Config\Repository;

use Marvin\Config\Repository;

class RepositoryTest extends \PHPUnit_Framework_TestCase
{

    protected $defaults = [
        'name'  => 'Marvin',
        'hosts' => [
            'apache'
        ],
    ];

    public function testSetItemsList()
    {
        $configRepository = new Repository($this->defaults);
        $this->assertAttributeEquals($this->defaults, 'items', $configRepository);
    }

    public function testReturnIfHasKeyInConfigs()
    {
        $configs          = [
            'name'         => 'Marvin',
            'my-config'    => 'New Config',
            'other-config' => 'More configurations'
        ];
        $configRepository = new Repository($configs);
        $this->assertTrue($configRepository->has('name'));
        $this->assertTrue($configRepository->has('my-config'));
        $this->assertTrue($configRepository->has('other-config'));
    }

    public function testReturnFalseIfArrayIsEmptyOrKeyIsNull()
    {
        $configRepository = new Repository([]);
        $this->assertNotTrue($configRepository->has('name'));
    }

    public function testGetValueUsingKey()
    {
        $configRepository = new Repository($this->defaults);
        $this->assertEquals('Marvin', $configRepository->get('name'));
    }

    public function testGetAllItemsIfParameterIsInvalid()
    {
        $configRepository = new Repository($this->defaults);
        $this->assertEquals($this->defaults, $configRepository->get(null));
    }

    public function testReturnValueDefaultIfKeyNotExist()
    {
        $configRepository = new Repository($this->defaults);
        $this->assertEquals('Marvin', $configRepository->get('package', 'Marvin'));
    }

    public function testShouldChangeDefaultValues()
    {
        $defaults = [
            'host' => 'Apache',
            'name' => 'Trillian',
        ];

        $new              = [
            'host' => 'Ngnix',
            'name' => 'Zooei'
        ];
        $configRepository = new Repository($defaults);

        $configRepository->set('host', $new['host']);
        $configRepository->set('name', $new['name']);

        $this->assertNotEquals($defaults['host'], $configRepository->get('host'));
        $this->assertEquals($new['host'], $configRepository->get('host'));
        $this->assertNotEquals($defaults['name'], $configRepository->get('name'));
        $this->assertEquals($new['name'], $configRepository->get('name'));

    }

    public function testReturnCompleteConfigurationList()
    {
        $configRepository = new Repository($this->defaults);

        $this->assertEquals($this->defaults, $configRepository->all());
    }

}
