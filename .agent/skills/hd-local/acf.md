# ACF Pro

## Naming

Pattern: `[prefix]_[context]_[description]`

- `page_hero_title`
- `block_testimonial_author_image`
- `opt_header_logo`

## Rules

1. Explicit ID: `get_field('name', $post_id)`
2. ALWAYS escape output
3. Use Clone fields for reusable groups
4. Enable ACF Local JSON
