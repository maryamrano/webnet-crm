# Third-Party Licenses & Notices

This project is built with open-source software. The compiled frontend bundle
in `public/build` contains code from the MIT-licensed libraries below; their
notices are collected here because the minified bundle does not embed them.
PHP dependencies are installed by Composer from `composer.lock`; every such
package ships its own license file inside `vendor/` after installation, and
the complete per-package license inventory is recorded in
`docs/ASSET_LICENSE_REVIEW.md`.

## Bundled in `public/build` (JavaScript/CSS)

### Chart.js — MIT License
Copyright (c) 2014–2024 Chart.js Contributors

### @kurkle/color — MIT License
Copyright (c) 2018–2024 Jukka Kurkela

### Alpine.js — MIT License
Copyright (c) 2019–2025 Caleb Porzio and contributors
(License declared in the package metadata; the installed npm package does
not ship a separate LICENSE file.)

### Tailwind CSS — MIT License
Copyright (c) Tailwind Labs, Inc.
(The compiled stylesheet includes Tailwind's generated utilities and its
MIT-licensed preflight base styles.)

The MIT License terms that apply to each of the above:

> Permission is hereby granted, free of charge, to any person obtaining a copy
> of this software and associated documentation files (the "Software"), to deal
> in the Software without restriction, including without limitation the rights
> to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
> copies of the Software, and to permit persons to whom the Software is
> furnished to do so, subject to the following conditions:
>
> The above copyright notice and this permission notice shall be included in
> all copies or substantial portions of the Software.
>
> THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
> IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
> FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
> AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
> LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
> FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
> IN THE SOFTWARE.

## Loaded at runtime (not distributed with this project)

### Figtree typeface — SIL Open Font License 1.1
Copyright (c) 2022 The Figtree Project Authors
(https://github.com/erikdkennedy/figtree)
Loaded from fonts.bunny.net in the page layouts; no font files are included
in this project. If the font is ever self-hosted, the full OFL 1.1 text must
accompany it.

## Framework (installed via Composer, not redistributed here)

### Laravel Framework & Laravel Breeze — MIT License
Copyright (c) Taylor Otwell
The application layout, authentication views and components originate from
the Laravel 13 application skeleton and the Breeze starter scaffolding, and
were adapted for this project. Their license files are present in `vendor/`
after `composer install`.
