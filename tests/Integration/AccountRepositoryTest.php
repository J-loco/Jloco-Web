<?php

declare(strict_types=1);

namespace StarLoco\Web\Tests\Integration;

use StarLoco\Web\Repository\AccountRepository;

final class AccountRepositoryTest extends IntegrationTestCase
{
    private AccountRepository $accounts;

    protected function setUp(): void
    {
        parent::setUp();
        $this->accounts = new AccountRepository($this->database);
    }

    public function testCreateAndFind(): void
    {
        $id = $this->accounts->create('bob', 'hash', 'bob@example.com', 'Ville ?', 'Astrub');

        $account = $this->accounts->find($id);
        self::assertNotNull($account);
        self::assertSame('bob', $account->name);
        self::assertSame('bob@example.com', $account->email);
        self::assertSame('Ville ?', $account->question);
        self::assertNull($account->pseudo);
        self::assertTrue($account->visibleInArmory, 'migration 001 default');
        self::assertFalse($account->positionVisible, 'migration 001 default');

        self::assertSame($id, $this->accounts->findCredentials('BOB')?->accountId, 'latin1_swedish_ci is case-insensitive, like the login server');
        self::assertSame('hash', $this->accounts->findCredentials('bob')?->passwordHash);
        self::assertTrue($this->accounts->exists('bob'));
        self::assertTrue($this->accounts->answerMatches($id, 'Astrub'));
        self::assertFalse($this->accounts->answerMatches($id, 'Bonta'));
    }

    public function testValuesLatin1CannotRepresentNeverReachTheDatabase(): void
    {
        $id = $this->createAccount('alice');

        // Without the guard, MariaDB raises "Illegal mix of collations" on these comparisons.
        self::assertNull($this->accounts->findByName('日本'));
        self::assertNull($this->accounts->findCredentials('alice😀'));
        self::assertFalse($this->accounts->exists('日本'));
        self::assertFalse($this->accounts->answerMatches($id, 'bleu😀'));
        self::assertNotNull($this->accounts->findByName('alice'));
    }

    public function testAccentedLatin1TextRoundTrips(): void
    {
        $id = $this->accounts->create('eloise', 'hash', 'e@example.com', 'Épée préférée ?', 'Épée de Boisaille');
        self::assertSame('Épée préférée ?', $this->accounts->find($id)?->question);
        self::assertTrue($this->accounts->answerMatches($id, 'Épée de Boisaille'));
    }

    public function testDebitIsAtomicAndNeverNegative(): void
    {
        $id = $this->createAccount('alice', '', ['points' => 50]);

        self::assertTrue($this->accounts->debitPoints($id, 30));
        self::assertFalse($this->accounts->debitPoints($id, 30), 'only 20 left');
        self::assertSame(20, $this->accounts->find($id)?->points);

        $this->accounts->addPoints($id, 5);
        self::assertSame(25, $this->accounts->find($id)?->points);
    }

    public function testVoteCreditRespectsTheCooldown(): void
    {
        $id = $this->createAccount('alice');
        $now = 1_800_000_000;

        self::assertTrue($this->accounts->creditVote($id, 5, $now, 3600));
        self::assertFalse($this->accounts->creditVote($id, 5, $now + 10, 3600));
        self::assertTrue($this->accounts->creditVote($id, 5, $now + 3600, 3600));

        $account = $this->accounts->find($id);
        self::assertNotNull($account);
        self::assertSame(10, $account->points);
        self::assertSame(2, $account->votes);
        self::assertSame($now + 3600, $account->lastVoteAt);
    }

    public function testPrivacyToggles(): void
    {
        $id = $this->createAccount('alice');

        $this->accounts->togglePrivacy($id, 'position');
        self::assertTrue($this->accounts->find($id)?->positionVisible);
        $this->accounts->togglePrivacy($id, 'position');
        $this->accounts->togglePrivacy($id, 'armory');
        $account = $this->accounts->find($id);
        self::assertNotNull($account);
        self::assertFalse($account->positionVisible);
        self::assertFalse($account->visibleInArmory);
    }

    public function testTopVotersOnlyExposePseudos(): void
    {
        $this->createAccount('alice', '', ['votes' => 3]);
        $this->createAccount('bob', '', ['votes' => 7, 'pseudo' => null]);
        $this->createAccount('carol', '', ['votes' => 0]);

        $voters = $this->accounts->topVoters();
        self::assertCount(2, $voters);
        self::assertNull($voters[0]->pseudo);
        self::assertSame(7, $voters[0]->votes);
        self::assertSame('Alice', $voters[1]->pseudo);
        self::assertSame(3, $this->accounts->count());
    }
}
