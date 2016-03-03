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
            'other-config' => 'More configurations',
            'group'        => [
                'item' => 'Dot notation',
            ],
        ];
        $configRepository = new Repository($configs);
        $this->assertTrue($configRepository->has('name'));
        $this->assertTrue($configRepository->has('my-config'));
        $this->assertTrue($configRepository->has('other-config'));
        $this->assertTrue($configRepository->has('group.item'));
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

    public function testShouldReturnItemUsingDotNotation()
    {
        $items = [
            'app'     => [
                'name'      => 'Marvin',
                'my-config' => 'New Config'
            ],
            'default' => [
                'name' => 'Nothing',
            ],
            'last'    => 'item'
        ];

        $configRepository = new Repository($items);

        $this->assertEquals('Marvin', $configRepository->get('app.name'));
        $this->assertEquals('New Config', $configRepository->get('app.my-config'));
        $this->assertEquals('Nothing', $configRepository->get('default.name'));
        $this->assertEquals('item', $configRepository->get('last'));
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

    public function testShouldSetValuesCorrectly()
    {
        $items = [
            'app'    => 'Marvin',
            'config' => 'Configuration',
            'fixed' => 'Dont change'
        ];

        $expected = [
            'app' => [
                'name' => 'Marvin',
                'describe' => 'Manager Virtual Hosts',
            ],
            'config' => 'New Configurations',
            'fixed' => 'Dont change'
        ];

        $configRepository = new Repository($items);

        $configRepository->set('app.name', 'Marvin');
        $configRepository->set('app.describe', 'Manager Virtual Hosts');
        $configRepository->set('config', 'New Configurations');

        $this->assertEquals($expected, $configRepository->all());
    }

    public function testReturnCompleteConfigurationList()
    {
        $configRepository = new Repository($this->defaults);

        $this->assertEquals($this->defaults, $configRepository->all());
    }

    public function testShouldImplementsArrayAccessInterface()
    {
        $expected = [
            'app' => [
                'name' => 'Marvin',
                'describe' => 'Manager Virtual Hosts',
            ],
            'config' => 'New Configurations',
        ];

        $configRepository = new Repository();

        $configRepository['app.name'] = 'Marvin';
        $configRepository['app.describe'] = 'Manager Virtual Hosts';
        $configRepository['config'] = 'New Configurations';

        $configRepository->set('app.name', 'Marvin');
        $configRepository->set('app.describe', 'Manager Virtual Hosts');
        $configRepository->set('config', 'New Configurations');

        $this->assertInstanceOf('ArrayAccess', $configRepository);
        $this->assertEquals($expected, $configRepository->all());
    }

}
