# Phony
A phone number parser tailored for Slovene number format.

## Install
```bash
$ composer require zipavlin/phony
```

## Quick start
```php
use Zipavlin\Phony;
$phony = new Phony()

echo $phony->from('041 123-456')->toDisplay(); // +386 (0)41 123 456
echo $phony->from('041 123 456')->toFormat('00386 0{area}/{number:2}-{number:2}-{number}'); // 00386 041/12-34-56
echo $phony->from('01 1234-567')->toDisplay(); // +386 (0)1 12 34 567
```

## Methods
| method name | attribute            | description                                              |
|-------------|----------------------|----------------------------------------------------------|
| from        | $input               | Create new instance from unformatted phone number string |
| toParsed    |                      | Return phone number as parsed object                     |
| toNormal    |                      | Return phone number as short normalized string           |
| toHref      |                      | Return phone number as href link                         |
| toFull      |                      | Return phone number as full trimmed string               |
| toDisplay   |                      | Return phone number as fully formatted string            |
| toFormat    | $format              | Return phone number formatted with $format string        |
| toObject    | $format              | Return object with all phone number info                 |
| toRender    | $attributes, $format | Renders a attribute                                      |

## Format string
`$format` attribute can be either `string` or `array [string, string]`, where first format string is used for mobile numbers and second one is used for stationary phone numbers.

### Format tokens
- `{area}` - area code __without__ leading zero
- `{number}` - rest of phone number

Both tokens have an optional characters-count parameter: `{area:2}` and `{number:3}` respectively.

### Format example
 Working with phone number _041 123456_
`{area}/{number:5}-{number}` would return _41/12345-6_  
`{area:1}-{area:1} {number}` would return _4-1 123456_  
`0{area}/{number:3} {number:6}` would return _041/123 456_
