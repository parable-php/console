# Parable Console

## 0.6.1

_Changes_
- Small bugfix that crept up after splitting named commands and instantiated ones.

## 0.6.0

_Changes_
- Add static analysis using psalm.
- `Output::writelns(string ...$lines)` now takes multiple string values instead of an array of those.
- `Exception` has been renamed to `ConsoleException` for clarity.
- Multiple small code changes to make it more php8.

## 0.5.1

_Changes_
- Update parable-php/di dependency to 0.3.0

## 0.5.0

_Changes_
- Dropped support for php7, php8 only from now on.

## 0.4.1

_Bugfixes_

- When instantiating a command that was added with `addCommandByNameAndClass`, it was not prepared properly. Now it is.
- In addition, `Application::run()` will check whether a command is already prepared and do so if needed.

## 0.4.0

_Changes_

- It is now possible to add commands lazily, by calling `Application::addCommandByNameAndClass(string $commandName, string $className)`. When the command is requested (through `getCommand()` or `getCommands()`), it will be instantiated automatically.
- `Command::getUsage()` has been replaced with `Application::getCommandUsage($command)`.
- `Tags` class added, which is the only class that actually deals with tags.

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
