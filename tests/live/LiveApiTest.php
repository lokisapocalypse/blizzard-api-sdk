<?php

declare(strict_types=1);

namespace Fusani\Blizzard\Live;

use Fusani\Blizzard\Region;
use Fusani\Blizzard\ServiceFactory;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * Calls every endpoint on the live api and checks the response has the shape the services expect.
 *
 * Run with `composer test:live`. It needs `BLIZZARD_CLIENT_ID` and `BLIZZARD_CLIENT_SECRET`, and is skipped
 * without them. The character endpoints also need `BLIZZARD_TEST_REALM` (a realm slug) and
 * `BLIZZARD_TEST_CHARACTER` (a lowercased character name), and `BLIZZARD_TEST_REGION` picks the region (`us` by
 * default). The account endpoint needs a user's authorization code, so it isn't covered here.
 */
#[CoversNothing]
class LiveApiTest extends TestCase
{
    /** Thunderfury, an item that has existed since launch. */
    public const ITEM_ID = 19019;

    /** @var ServiceFactory builds the services with the live credentials */
    protected ServiceFactory $factory;

    /** @var Region the region to call */
    protected Region $region;

    /**
     * Build the factory from the live credentials, or skip the test if there are none.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $clientId = $this->env('BLIZZARD_CLIENT_ID');
        $clientSecret = $this->env('BLIZZARD_CLIENT_SECRET');

        if ($clientId === null || $clientSecret === null) {
            $this->markTestSkipped('Set BLIZZARD_CLIENT_ID and BLIZZARD_CLIENT_SECRET to run the live api tests.');
        }

        $this->factory = new ServiceFactory($clientId, $clientSecret, null, 'en_US');
        $this->region = Region::from($this->env('BLIZZARD_TEST_REGION') ?? 'us');
    }

    /**
     * Checks that the faction index lists factions, and the first one can be fetched by id.
     *
     * @return void
     */
    public function testFactions(): void
    {
        $service = $this->factory->createFactionService();

        $index = $service->allFactions($this->region);
        $id = $this->firstId($index, 'factions');

        $this->assertSame($id, $service->getFaction($id, $this->region)['id'] ?? null);
    }

    /**
     * Checks that an item and its icon can be fetched.
     *
     * @return void
     */
    public function testItem(): void
    {
        $service = $this->factory->createItemService();

        $this->assertSame(self::ITEM_ID, $service->getById(self::ITEM_ID, $this->region)['id'] ?? null);
        $this->assertArrayHasKey('assets', $service->getIcon(self::ITEM_ID, $this->region));
    }

    /**
     * Checks that the realm index lists realms.
     *
     * @return void
     */
    public function testRealms(): void
    {
        $index = $this->factory->createRealmService()->getAllRealmsByRegion($this->region);

        $this->firstId($index, 'realms');
    }

    /**
     * Checks that the reputation tier index lists tiers, and the first one can be fetched by id.
     *
     * @return void
     */
    public function testReputationTiers(): void
    {
        $service = $this->factory->createReputationService();

        $index = $service->allReputationTiers($this->region);
        $id = $this->firstId($index, 'reputation_tiers');

        $this->assertSame($id, $service->getReputationTier($id, $this->region)['id'] ?? null);
    }

    /**
     * Checks that a character's media, mounts, Mythic+ profile, pets and reputations can be fetched.
     *
     * @return void
     */
    public function testCharacterProfile(): void
    {
        $realm = $this->env('BLIZZARD_TEST_REALM');
        $character = $this->env('BLIZZARD_TEST_CHARACTER');

        if ($realm === null || $character === null) {
            $this->markTestSkipped('Set BLIZZARD_TEST_REALM and BLIZZARD_TEST_CHARACTER to check the character endpoints.');
        }

        $media = $this->factory->createCharacterMediaService()->getMedia($character, $realm, $this->region);
        $mounts = $this->factory->createMountService()->getMountsForCharacter($character, $realm, $this->region);
        $mythicPlus = $this->factory->createMythicPlusService()
            ->getProfileForCharacter($character, $realm, $this->region);
        $pets = $this->factory->createPetService()->getForCharacter($character, $realm, $this->region);
        $reputations = $this->factory->createReputationService()->getForCharacter($character, $realm, $this->region);

        $this->assertArrayHasKey('assets', $media);
        $this->assertArrayHasKey('mounts', $mounts);
        $this->assertArrayHasKey('character', $mythicPlus);
        $this->assertArrayHasKey('pets', $pets);
        $this->assertArrayHasKey('reputations', $reputations);
    }

    /**
     * Get a non-empty environment variable.
     *
     * @param string $name the variable's name
     * @return string|null the value, or null if it's unset or empty
     */
    protected function env(string $name): ?string
    {
        $value = getenv($name);

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * Check that an index response lists entries with ids, and get the first one's id.
     *
     * @param array<mixed> $index the index response
     * @param string $key the key the entries are under, e.g. `factions`
     * @return int the first entry's id
     */
    protected function firstId(array $index, string $key): int
    {
        $entries = $index[$key] ?? null;

        $this->assertIsArray($entries, "The index has no \"$key\" list.");
        $this->assertNotEmpty($entries, "The \"$key\" list is empty.");

        $first = reset($entries);

        $this->assertIsArray($first);
        $this->assertIsInt($first['id'] ?? null, "The first entry in \"$key\" has no id.");

        return $first['id'];
    }
}
