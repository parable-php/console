# Parable Console

## 0.3.1

_Bugfixes_

- `set_error_handler` has been removed.

## 0.3.0

_Changes_

- Renamed `Option` and `Argument` to have `Parameter` suffix for clarity (`OptionParameter` and `ArgumentParameter`).
- Renamed `Help` command to `HelpCommand` for clarity.
- Renamed `Command` and `Parameter` namespaces to plural for consistency.
- Changed `Environment::TERMINAL_DEFAULT_HEIGHT` to 24.
- Added `InputTest` to prevent future breaking changes to that class as well.

## 0.2.0

_Changes_

- `App` has been renamed to `Application` for consistency reasons.
- Running a `Command` (or the `Application`) will no longer return any values. All are typed to return `void`.

## 0.1.3

_Changes_

- Upgrade `parable-php/di` to `0.2.3`.

## 0.1.2

_Changes_

- Merged PR #2, which stops unknown tags from throwing.
- All files are now enforcing strict types.

## 0.1.1

_Changes_

- Code style clean-up.

## 0.1.0

_Changes_
- First release.
