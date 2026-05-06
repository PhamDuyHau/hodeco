# PHP 8.3 Standards

## Preferred Patterns

```php
// First-class callable
add_action('init', $this->init(...));

// Arrow functions
$filtered = array_filter($items, fn($i) => $i > 0);

// Null coalescing
$val = $arr['key'] ?? null;
$cache[$k] ??= $this->getData();

// Match
return match($type) {
    'post' => 'Post',
    default => 'Other',
};

// Constructor promotion
public function __construct(private string $name) {}
```

## Avoid

```php
// ❌ Old callback syntax
add_action('init', [$this, 'init']);

// ❌ Nested ternary
$val = $a ? ($b ? 'x' : 'y') : 'z';

// ❌ Return with assignment
return $cache[$key] = $value;
```

## Iteration

| Function       | Use          |
| -------------- | ------------ |
| `array_map`    | Transform    |
| `array_filter` | Filter       |
| `foreach`      | Side effects |
