# Bike Parking agent guidance

This is a Laravel 13 application using PHP 8.3, Blade, Livewire 3, MySQL 8, and Laravel Sail.

## Delegation

Optimize for total token use. The primary agent owns normal issue implementation, integration, and the final answer.

- Do not delegate a small single-file edit, one focused test command, formatting, or a simple documentation correction.
- Delegate to `laravel_architect` for read-only analysis of architecture, cross-layer bugs, migrations or data integrity, authorization, transaction boundaries, image compensation, session consistency, external API contracts, or high-impact reviews.
- Delegate to `routine_worker` only for bounded, low-risk, repetitive edits that follow an existing pattern and have clear acceptance criteria.
- Use an unnamed default subagent only for narrow code discovery, repetitive searches, or noisy log summarization.
- Keep at most one subagent active. Never run parallel write-heavy agents.
- The primary agent must review delegated changes and remains responsible for migration safety, final integration, and verification.

## Project workflow

- Preserve unrelated user changes and keep patches focused.
- Use `rg` or `rg --files` for repository searches.
- Prefer focused tests first. Run the full suite only when the change has broad impact or focused coverage is insufficient.
- Use Laravel Sail commands when the application, database, or project PHP environment is required.
- For parking-rate changes, run `./vendor/bin/sail test tests/Feature/ParkingSpotRateDisplayTest.php` first.
- Run Pint only on changed PHP files unless a repository-wide formatting change is requested.
