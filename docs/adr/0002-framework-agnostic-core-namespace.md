# Core is framework-agnostic; Laravel integration lives in its own namespace within the same package

The 2.x package mixes Laravel-specific concerns (service container resolution, config) directly into the core CF logic, under the namespace `robertogallea\LaravelCodiceFiscale\`. 3.0 moves the framework-agnostic core to `Robertogallea\CodiceFiscale\...`, with all Laravel-specific classes (service provider, Eloquent models/repositories, validation rule, cast, artisan commands) under `Robertogallea\CodiceFiscale\Laravel\...`. The package stays a single Composer package for 3.0; splitting core/data/Laravel into separate packages is deferred until the API stabilizes.

This enforces "Core knows nothing about Laravel; Laravel knows about Core" as an actual dependency-direction constraint, not just a stated principle, while a single package avoids premature multi-package coordination overhead.
