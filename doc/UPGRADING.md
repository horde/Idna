# Upgrading from lib/ (Horde_Idna) to src/ (Horde\Idna)

## API changes

### Non-static usage

The legacy `Horde_Idna` class uses static methods. The modern `Horde\Idna\Idna`
class is instantiated with a backend via constructor injection or the `create()`
factory:

```php
// Legacy
$encoded = Horde_Idna::encode($domain);

// Modern
$idna = Horde\Idna\Idna::create();
$encoded = $idna->encode($domain);

// Or with explicit backend
$idna = new Horde\Idna\Idna(new Horde\Idna\Backend\Intl());
```

### Translation removed

Error messages from the `src/` API are plain English strings. The legacy
`Horde_Idna_Translation` class and gettext infrastructure are not carried
forward. Callers that need localized error messages should catch
`Horde\Idna\Exception` and translate the message themselves.
