<?php

declare(strict_types=1);

namespace Luxid\Haven\Tests;

use Luxid\Haven\PasswordHasher;
use Luxid\Haven\SessionGuard;
use Luxid\Haven\Tests\Fixtures\FakeSession;
use Luxid\Haven\Tests\Fixtures\FakeUser;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Behavioural tests for the session guard.
 *
 * @package Luxid\Haven\Tests
 */
final class SessionGuardTest extends TestCase
{
    /**
     * Session the guard writes to.
     */
    private FakeSession $session;

    /**
     * Hasher used to build and verify credentials.
     */
    private PasswordHasher $hasher;

    /**
     * The guard under test.
     */
    private SessionGuard $guard;

    protected function setUp(): void
    {
        parent::setUp();

        FakeUser::reset();
        $_COOKIE = [];

        $this->session = new FakeSession();
        // Cost 4 keeps the suite fast; production uses the configured default.
        $this->hasher = new PasswordHasher(['cost' => 4]);
        $this->guard = new SessionGuard($this->session, $this->hasher, FakeUser::class);
    }

    /**
     * Register a user with a hashed password.
     *
     * @param string $password Plaintext password
     */
    private function givenUser(string $password = 'correct-horse'): FakeUser
    {
        return FakeUser::add(new FakeUser(
            id: 1,
            email: 'jhay@luxid.dev',
            password: $this->hasher->hash($password)
        ));
    }

    #[Test]
    public function a_fresh_guard_reports_a_guest(): void
    {
        $this->assertTrue($this->guard->guest());
        $this->assertFalse($this->guard->check());
        $this->assertNull($this->guard->user());
        $this->assertNull($this->guard->id());
    }

    #[Test]
    public function it_signs_a_user_in_with_valid_credentials(): void
    {
        $this->givenUser();

        $this->assertTrue($this->guard->attempt([
            'email' => 'jhay@luxid.dev',
            'password' => 'correct-horse',
        ]));

        $this->assertTrue($this->guard->check());
        $this->assertSame(1, $this->guard->id());
    }

    #[Test]
    public function it_rejects_a_wrong_password(): void
    {
        $this->givenUser();

        $this->assertFalse($this->guard->attempt([
            'email' => 'jhay@luxid.dev',
            'password' => 'wrong',
        ]));

        $this->assertTrue($this->guard->guest());
    }

    #[Test]
    public function it_rejects_an_unknown_user(): void
    {
        $this->assertFalse($this->guard->attempt([
            'email' => 'nobody@luxid.dev',
            'password' => 'whatever',
        ]));
    }

    #[Test]
    public function it_regenerates_the_session_id_on_login(): void
    {
        // Regression: the guard wrote the user id into whatever session the
        // visitor arrived with, so a planted session id survived the privilege
        // change — textbook session fixation.
        $user = $this->givenUser();

        $this->guard->login($user);

        $this->assertSame(1, $this->session->regenerations);
    }

    #[Test]
    public function it_regenerates_the_session_id_on_logout(): void
    {
        $user = $this->givenUser();
        $this->guard->login($user);

        $this->guard->logout();

        $this->assertSame(2, $this->session->regenerations);
    }

    #[Test]
    public function it_never_looks_a_user_up_by_their_password(): void
    {
        $this->givenUser();

        $this->guard->attempt(['email' => 'jhay@luxid.dev', 'password' => 'correct-horse']);

        foreach (FakeUser::$lookups as $lookup) {
            $this->assertArrayNotHasKey('password', $lookup);
        }
    }

    #[Test]
    public function it_excludes_a_custom_password_field_from_the_lookup(): void
    {
        // Regression: the password key was hardcoded, so an entity naming its
        // field "secret" ended up querying the database by plaintext password.
        FakeUser::$passwordField = 'secret';
        FakeUser::add(new FakeUser(id: 1, email: 'jhay@luxid.dev', password: $this->hasher->hash('pw')));

        $this->guard->attempt(['email' => 'jhay@luxid.dev', 'secret' => 'pw']);

        foreach (FakeUser::$lookups as $lookup) {
            $this->assertArrayNotHasKey('secret', $lookup);
        }
    }

    #[Test]
    public function it_refuses_to_search_when_only_a_password_was_given(): void
    {
        $this->assertFalse($this->guard->attempt(['password' => 'correct-horse']));
        $this->assertSame([], FakeUser::$lookups);
    }

    #[Test]
    public function validate_checks_credentials_without_signing_in(): void
    {
        $this->givenUser();

        $this->assertTrue($this->guard->validate([
            'email' => 'jhay@luxid.dev',
            'password' => 'correct-horse',
        ]));

        $this->assertTrue($this->guard->guest());
        $this->assertSame(0, $this->session->regenerations);
    }

    #[Test]
    public function logout_clears_the_session_key(): void
    {
        $this->guard->login($this->givenUser());
        $this->guard->logout();

        $this->assertTrue($this->guard->guest());
        $this->assertNull($this->guard->user());
    }

    #[Test]
    public function it_signs_out_when_the_session_points_at_a_missing_user(): void
    {
        $this->session->set('haven_user_id', 999);

        $this->assertNull($this->guard->user());
        $this->assertTrue($this->guard->guest());
    }

    #[Test]
    public function it_resolves_a_user_already_named_by_the_session(): void
    {
        $this->givenUser();
        $this->session->set('haven_user_id', 1);

        $this->assertSame(1, $this->guard->id());
    }

    #[Test]
    public function it_stores_the_remember_token_on_the_user_record(): void
    {
        // Regression: the token was written only to the session, so it could not
        // outlive the session it was meant to survive.
        $user = $this->givenUser();

        $this->guard->login($user, true);

        $this->assertNotNull($user->getRememberToken());
        $this->assertSame(1, $user->saves);
    }

    #[Test]
    public function it_clears_the_remember_token_on_logout(): void
    {
        $user = $this->givenUser();
        $this->guard->login($user, true);

        $this->guard->logout();

        $this->assertNull($user->getRememberToken());
    }

    #[Test]
    public function it_reports_its_provider(): void
    {
        $this->assertSame(FakeUser::class, $this->guard->getProvider());
    }

    #[Test]
    public function login_using_id_signs_in_a_known_user(): void
    {
        $this->givenUser();

        $this->assertInstanceOf(FakeUser::class, $this->guard->loginUsingId(1));
        $this->assertTrue($this->guard->check());
    }

    #[Test]
    public function login_using_id_refuses_an_unknown_user(): void
    {
        $this->assertFalse($this->guard->loginUsingId(999));
    }
}
