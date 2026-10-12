<?php

declare(strict_types=1);

namespace Fusani\Blizzard;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ServiceFactory::class)]
class ServiceFactoryTest extends TestCase
{
    /**
     * Lists each factory method with the service class it should build.
     *
     * @return array<string, array{string, class-string}>
     */
    public static function serviceProvider(): array
    {
        return [
            'account' => ['createAccountService', WorldOfWarcraft\Account::class],
            'character media' => ['createCharacterMediaService', WorldOfWarcraft\CharacterMedia::class],
            'faction' => ['createFactionService', WorldOfWarcraft\Faction::class],
            'item' => ['createItemService', WorldOfWarcraft\Item::class],
            'mount' => ['createMountService', WorldOfWarcraft\Mount::class],
            'mythic plus' => ['createMythicPlusService', WorldOfWarcraft\MythicPlus::class],
            'pet' => ['createPetService', WorldOfWarcraft\Pet::class],
            'realm' => ['createRealmService', WorldOfWarcraft\Realm::class],
            'reputation' => ['createReputationService', WorldOfWarcraft\Reputation::class],
        ];
    }

    /**
     * Checks that each factory method builds the matching service.
     *
     * @param string $method the factory method to call
     * @param class-string $expectedClass the service class it should return
     * @return void
     */
    #[DataProvider('serviceProvider')]
    public function testCreatesService(string $method, string $expectedClass): void
    {
        $service = (new ServiceFactory('client-id', 'client-secret'))->$method();

        $this->assertInstanceOf($expectedClass, $service);
    }
}
