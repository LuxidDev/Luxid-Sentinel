# Changelog

## 0.2.0

Pre-release. Several fixes change behaviour on purpose; they are listed first.

### Breaking

- **`RequireAuth` throws instead of exiting.** It built a 401 body, discarded it
  and called `exit`, so the client received a blank 200. It now throws
  `Luxid\Exceptions\UnauthorizedException` and the kernel renders it in the
  right format. Its constructor takes an optional `AuthManager` only; the
  `Response` argument is gone.
- **The remember token lives in a cookie, not the session.** Storing it in the
  session meant it could not outlive the session it was meant to survive, so
  "remember me" did nothing. The token is now issued as an HttpOnly, SameSite
  Lax cookie and compared against the user record in constant time.
- **Services are held on `HavenServiceProvider`, not in `$GLOBALS`.** Anything
  reading `$GLOBALS['haven_auth_manager']` should use `Haven::getManager()`.
- **The post-install bridge generator was removed.** It wrote a file into the
  `luxid/engine` package; the engine discovers `haven:install` from
  `extra.luxid.commands` instead.
- **Requires `luxid/engine` ^0.8**, for `SessionInterface::regenerate()` and
  `UnauthorizedException`.
- **Minimum PHP is now 8.1.**

### Fixed

- **Session fixation.** `SessionGuard::login()` wrote the user id into whatever
  session the visitor arrived with. The session id is now rotated on login and
  on logout.
- The password field is excluded from the credential lookup by the name the
  entity reports, not a hardcoded `password`. An entity naming its field
  `secret` previously queried the database by plaintext password.
- A failed lookup now costs roughly what a real verification costs
  (`PasswordHasher::fake()`), so response time does not reveal which addresses
  have accounts.
- Guards resolve the session through `Application::getSession()`, which the
  kernel populates lazily; reading the property returned null during boot.
- `Sentinel.php`, a byte-identical second declaration of the `Haven` class, was
  removed. Loading it directly was a fatal error.

### Added

- `PasswordHasher::fake()`.
- `SessionGuard::getProvider()` and cookie-backed recall.
- `HavenServiceProvider::reset()` for tests.
- A PHPUnit suite covering fixation, credential lookup and remember tokens.
