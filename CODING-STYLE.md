# Coding style

PHP follows the [WordPress PHP coding standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/) through [WordPressCS](https://github.com/WordPress/WordPress-Coding-Standards) 3. The ruleset is `phpcs.xml.dist`. Markdown files are not part of that standard.

Plugin code lives in `src/` (`Nominal_AIPO_*`). The PHP AI Client SDK is the Composer package in `vendor/` during development, and WordPress core at runtime. Do not restyle `vendor/`.

## Checks

```bash
composer lint      # phpcs, then phpmd, then phpstan
composer phpcs     # report only
composer phpcbf    # fix what PHP_CodeSniffer can fix
composer phpmd     # PHPMD, rules in phpmd.xml.dist
composer phpstan   # PHPStan level 5
```

`composer phpmd` hides deprecation notices from PHPMD 2.15 itself on PHP 8.5. The plugin code is still checked. Running `vendor/bin/phpmd` directly prints those notices. They are not violations in this plugin.

`composer lint` must exit 0.

## What we changed from stock WordPressCS

WordPressCS already aligns `=` and `=>`, but only on tab stops (`Universal.WhiteSpace.PrecisionAlignment`). That sniff is turned off so columns can line up on any character.

`Squiz.Commenting.FunctionComment.SpacingAfterParamType` is turned off so `@param` types, names, and descriptions can share columns. WordPressCS already allows extra space after the variable name.

Short arrays (`[]`) are allowed. `Universal.Arrays.DisallowShortArraySyntax` is turned off. WordPress itself uses `array()`.

A call that would pass 120 characters is split like an array: the opening parenthesis stays on the first line, each argument gets its own line, and the closing parenthesis is on its own line. Nested calls stay on one line when that argument is short.

```php
load_plugin_textdomain(
	'nominal-ai-provider-for-opencode',
	false,
	dirname( plugin_basename( __FILE__ ) ) . '/languages'
);
```

WordPressCS enforces one argument per line once a call is multiline. `SlevomatCodingStandard.Functions.RequireMultiLineCall` (`minLineLength` 120) is what forces the split. Slevomat is pinned to 8.22.1 because later releases need PHP_CodeSniffer 4, and WordPressCS 3.4.1 needs PHP_CodeSniffer `>=3.13.6 <4`.

The line length in `phpcs.xml.dist` is 120, not the 80-character handbook suggestion, so aligned lines still fit.

Class files keep PSR-4 names (`Nominal_AIPO_OpenCodeProvider.php`). `WordPress.Files.FileName` is off. Methods that override the SDK stay camelCase. WordPressCS allows that on a class that extends another class. New functions and local variables stay snake_case.

## Change it in PhpStorm

Open **Settings → PHP → Quality Tools**.

- **PHP_CodeSniffer:** local binary `vendor/bin/phpcs`, phpcbf `vendor/bin/phpcbf`. Coding standard: Custom, `phpcs.xml.dist`.
- **Mess Detector:** binary `vendor/bin/phpmd`. Custom ruleset: `phpmd.xml.dist`.

Open **Settings → Editor → Code Style → PHP**. The project scheme is `.idea/codeStyles/Project.xml` (per-project settings are on). Set the right margin (hard wrap) to the same number as `lineLimit` and `minLineLength` in `phpcs.xml.dist`. That number is 120. A PHP margin of 200 makes Reformat Code join `load_plugin_textdomain()` back onto one line, because 130 characters still fits. Keep line breaks must stay on, or the same reformat deletes a break you added by hand.

| What you want | PhpStorm | Ruleset |
| --- | --- | --- |
| Tabs, size 4 | Tabs and Indents → Tab character, size 4 | already in WordPressCS |
| Space inside parentheses | Spaces → Within → function declaration, function call, `if` / `while` / `for` / `foreach` / `switch` / `catch`, array initializer | already in WordPressCS |
| Align `=` | Other → Align consecutive assignments | `Generic.Formatting.MultipleStatementAlignment`, property `maxPadding` |
| Align `=>` | Other → Align key-value pairs | `WordPress.Arrays.MultipleStatementAlignment`, property `maxColumn` |
| Align PHPDoc | PHPDoc → Align parameter/property names, and Align description | remove the `SpacingAfterParamType` exclude to go back to one space |
| Chop long calls | Wrapping and Braces → Function calls → Arguments wrapping: **Chop down if long**. Also check Align when multiline, New line after `(`, Place `)` on new line | `lineLimit` and `minLineLength`. Keep both equal to the right margin |

After you change a checkbox or a ruleset property, run `composer phpcbf`, then `composer lint`.

`wp-stubs.php` is excluded. `.editorconfig` sets `ij_formatter_enabled = false` for that file, and the file starts with `// @formatter:off`. Reformat Code will not realign its PHPDoc. Formatter markers must stay enabled under **Editor → Code Style → Formatter**.

PHPMD rules and the excludes (static SDK calls, camelCase overrides, short `usort` variables, class-name length 60) are in `phpmd.xml.dist`. Comment out an `<exclude>` to turn a rule back on, then run `composer phpmd`.
