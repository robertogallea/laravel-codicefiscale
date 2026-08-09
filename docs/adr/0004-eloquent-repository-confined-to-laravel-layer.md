# BirthPlaceRepository's reference implementation is Eloquent-backed and lives only in the Laravel layer

The repository needs to answer validity-window queries ("was this code valid on this date") efficiently against ~20k historical rows. Core defines the `BirthPlaceRepository` contract and plain DTOs only. The Laravel layer provides `EloquentBirthPlaceRepository`, backed by two Eloquent models (`Municipality`, `ForeignCountry`) on a dedicated SQLite connection that the service provider registers at runtime — never the consuming app's primary database connection or migrations.

This preserves ADR-0002's Core/Laravel boundary while still using Eloquent's query builder for what are naturally range queries; an isolated connection means installing this package never touches the host application's own schema.
